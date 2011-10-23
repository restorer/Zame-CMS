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

class Context
{
	protected $hooks = array();

	public $location = '';
	public $req = null;
	public $resp = null;
	public $modules = array();

	public $params = array();
	public $layout = 'main';

	public function __construct()
	{
		$this->req = new Request($this);
		$this->resp = new Response($this);
		$this->load_modules();
	}

	protected function load_modules()
	{
		$cached_list_path = conf('cache.path') . 'modules.list';

		if (@file_exists($cached_list_path))
		{
			$cached_list = unserialize(file_get_contents($cached_list_path));

			foreach ($cached_list as $name)
			{
				require_once(CMS . 'modules/' . $name . '/module.php');

				$class_name = Cms::capitalize_words($name) . 'Module';
				$this->modules[$name] = new $class_name($this);
			}

			return;
		}

		$avail_modules = array();

		if ($dh = opendir(CMS . 'modules/'))
		{
			while (($name = readdir($dh)) !== false)
			{
				if (is_dir(CMS . 'modules/' . $name))
				{
					if (@file_exists(CMS . 'modules/' . $name . '/module.php'))
					{
						require_once(CMS . 'modules/' . $name . '/module.php');

						$class_name = Cms::capitalize_words($name) . 'Module';
						$before = array();
						$after = array();

						if (is_callable(array($class_name, '_before')))
						{
							$before = call_user_func(array($class_name, '_before'));
							if (!is_array($before)) $before = array($before);
						}

						if (is_callable(array($class_name, '_after')))
						{
							$after = call_user_func(array($class_name, '_after'));
							if (!is_array($after)) $after = array($after);
						}

						$avail_modules[$name] = array(
							'class' => $class_name,
							'before' => $before,
							'after' => $after
						);
					}
				}
			}

			closedir($dh);
		}

		foreach ($avail_modules as $mod_name=>$item) {
			foreach ($item['after'] as $name) {
				if (!array_key_exists($name, $avail_modules)) {
					throw new Exception("\"$mod_name\" depends on \"$name\", but \"$name\" not found");
				}
			}
		}

		foreach ($avail_modules as $mod_name=>$item) {
			foreach ($item['before'] as $name) {
				if (array_key_exists($name, $avail_modules)) {
					if (!in_array($mod_name, $avail_modules[$name]['after'])) {
						$avail_modules[$name]['after'][] = $mod_name;
					}
				}
			}
		}

		$cached_list = array();

		for (;;)
		{
			$has_changes = false;
			$has_modules = false;

			foreach ($avail_modules as $mod_name=>$item)
			{
				if (array_key_exists($mod_name, $this->modules)) continue;

				$has_modules = true;
				$can_insert = true;

				foreach ($item['after'] as $name)
				{
					if (!array_key_exists($name, $this->modules))
					{
						$can_insert = false;
						break;
					}
				}

				if (!$can_insert) continue;

				$class_name = $item['class'];
				$this->modules[$mod_name] = new $class_name($this);

				$cached_list[] = $mod_name;
				$has_changes = true;
			}

			if (!$has_modules) break;
			if (!$has_changes) throw new Exception("Can't resolve modules dependencies");
		}

		if ($fp = @fopen($cached_list_path, 'wb'))
		{
			fwrite($fp, serialize($cached_list));
			fclose($fp);
			chmod($cached_list_path, 0555+0111);
		}

		$avail_modules = null;
	}

	public function hook($name, $sender=null)
	{
		if (array_key_exists($name, $this->hooks)) {
			return (call_user_func($this->hooks[$name], $this, $sender) === true);
		}
	}

	public function add_hook($name, $func)
	{
		$this->hooks[$name] = $func;
	}
}
