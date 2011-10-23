<?php

/*
 * MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * Copyright (c) 2007, Slava Tretyak (aka restorer)
 * Zame Software Development (http://zame-dev.org)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Zame CMS
 */

require_once(dirname(__FILE__) . '/base_migration.php');

class MigrateCommand extends BaseCommand
{
	const SCHEMA_NAME = 'schema_version';

	protected function get_migrations_dir()
	{
		return CMS . 'migrations/';
	}

	protected function ensure_schema_version_table()
	{
		$cmd = new SDBCommand("
			CREATE TABLE IF NOT EXISTS @schema_name (
				`id` varchar(255) NOT NULL,
				UNIQUE KEY `idx_schema_version_id` (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		$cmd->set('schema_name', self::SCHEMA_NAME, SDB::TableName);
		$cmd->execute();
	}

	protected function get_applied_migrations()
	{
		$this->ensure_schema_version_table();

		$cmd = new SDBCommand("SELECT `id` FROM @schema_name ORDER BY `id`");
		$cmd->set('schema_name', self::SCHEMA_NAME, SDB::TableName);
		$rows = $cmd->get_all();

		return array_map(create_function('$r','return $r["id"];'), $rows);
	}

	protected function get_available_migrations()
	{
		$dir = new DirectoryIterator($this->get_migrations_dir());
		$result = array();

		foreach ($dir as $fileInfo) {
			if (!$fileInfo->isDot() && !$fileInfo->isDir() && preg_match('/^m(\d{14})(?:_[0-9A-Za-z]+)?\.php$/', $fileInfo->getFileName(), $mt)) {
				$result[$mt[1]] = preg_replace('/\.php$/', '', $fileInfo->getFileName());
			}
		}

		ksort($result);
		return $result;
	}

	protected function apply_migration($id, $class_name, $method, $skip=false)
	{
		if ($skip)
		{
			$this->message("Skip {$method}: {$class_name}");
		}
		else
		{
			$this->message("Migrate {$method}: {$class_name}");

			require $this->get_migrations_dir() . "{$class_name}.php";
			$migration = new $class_name($this);

			$cmd = new SDBCommand("BEGIN");
			$cmd->execute();

			try
			{
				call_user_func(array($migration, $method));
			}
			catch (Exception $e)
			{
				if ($e->getPrevious()) {
					$e = $e->getPrevious();
				}

				if ($e instanceof PDOException) {
					$this->message($e->getMessage());
				} else {
					$this->message('Exception "' . get_class($e) . '" in ' . $e->getFile() . ':' . $e->getLine());
					$this->message($e->getMessage());
					$this->message($e->getTraceAsString());
				}

				return false;
			}

			$cmd = new SDBCommand("COMMIT");
			$cmd->execute();
		}

		if ($method == 'down') {
			$cmd = new SDBCommand("DELETE FROM @schema_name WHERE `id`=@id");
		} else {
			$cmd = new SDBCommand("INSERT INTO @schema_name (`id`) VALUES(@id)");
		}

		$cmd->set('schema_name', self::SCHEMA_NAME, SDB::TableName);
		$cmd->set('id', $id, SDB::String);
		$cmd->execute();

		return true;
	}

	protected function up($argument)
	{
		$applied = $this->get_applied_migrations();
		$available = $this->get_available_migrations();
		$skip = (strtolower(trim($argument)) == 'skip');

		foreach ($available as $id=>$path) {
			if (!in_array($id, $applied)) {
				$this->apply_migration($id, $path, 'up', $skip);
				break;
			}
		}
	}

	protected function down($argument)
	{
		$applied = $this->get_applied_migrations();
		$available = array_reverse($this->get_available_migrations(), true);
		$skip = (strtolower(trim($argument)) == 'skip');

		foreach ($available as $id=>$path) {
			if (in_array($id, $applied)) {
				$this->apply_migration($id, $path, 'down', $skip);
				break;
			}
		}
	}

	protected function migrate($argument)
	{
		$applied = $this->get_applied_migrations();
		$available = $this->get_available_migrations();
		$skip = (strtolower(trim($argument)) == 'skip');

		foreach ($available as $id=>$path) {
			if (!in_array($id, $applied)) {
				if (!$this->apply_migration($id, $path, 'up', $skip)) {
					return;
				}
			}
		}
	}

	protected function create($argument)
	{
		$argument = ucfirst(preg_replace('/[^0-9A-Za-z]/', '', $argument));
		$class_name = 'm' . date('YmdHis') . ($argument == '' ? '' : "_{$argument}");

		$template = <<<HERE
<?php

class {$class_name} extends BaseMigration
{
	public function up()
	{
	}

	public function down()
	{
	}
}

HERE;

		file_put_contents($this->get_migrations_dir() . "{$class_name}.php", $template);
		$this->message("Migration {$class_name} created");
	}

	public function run($params)
	{
		$command = isset($params[0]) ? $params[0] : 'migrate';
		$argument = isset($params[1]) ? $params[1] : '';

		switch ($command)
		{
			case 'migrate':
				$this->migrate($argument);
				break;

			case 'up':
				$this->up($argument);
				break;

			case 'down':
				$this->down($argument);
				break;

			case 'create':
				$this->create($argument);
				break;

			default:
				$this->message("Unknown command \"$command\"");
				break;
		}
	}
}
