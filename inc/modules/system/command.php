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

require_once(CMS . 'admin/core.php');

class SystemCommand extends BaseCommand
{
	protected function call_module_method($module, $method, $message, $show_errors=true)
	{
		if (!is_dir(CMS . 'modules/' . $module))
		{
			if ($show_errors) {
				$this->message('"' . CMS . 'modules/' . $module . '" not found');
			}

			return;
		}

		if (!is_readable(CMS . "modules/{$module}/admin.php"))
		{
			if ($show_errors) {
				$this->message('"' . CMS . 'modules/' . $module . '/admin.php" not found');
			}

			return;
		}

		require_once(CMS . "modules/{$module}/admin.php");
		$class_name = Cms::capitalize_words($module) . 'AdminModule';

		if (!is_callable(array($class_name, $method))) {
			if ($show_errors) {
				$this->message("{$class_name} has no method {$method}");
			}

			return;
		}

		$this->message("{$message} {$module} module");
		call_user_func(array($class_name, $method));
	}

	protected function install($module)
	{
		$node = Node::get_node('site');

		if (!$node)
		{
			$this->message('Creating root node');
			Node::create_node(Node::Folder, 'site', conf_get('sitename'), Node::Visible | Node::System);
		}
		else
		{
			$this->message('Updating root node');
			$node->title = conf_get('sitename');
			$node->save();
		}

		if ($module)
		{
			$this->call_module_method($module, 'module_install', 'Installing');
			return;
		}

		if (!($dh = opendir(CMS . 'modules/')))
		{
			$this->message('"' . CMS . 'modules/" not found');
			return;
		}

		$avail_modules = array();

		while (($module = readdir($dh)) !== false)
		{
			if (!is_dir(CMS . "modules/{$module}") || !is_readable(CMS . "modules/{$module}/admin.php")) {
				continue;
			}

			require_once(CMS . "modules/{$module}/admin.php");
			$class_name = Cms::capitalize_words($module) . 'AdminModule';

			if (!is_callable(array($class_name, 'module_install'))) {
				continue;
			}

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

			$avail_modules[$module] = array(
				'class' => $class_name,
				'before' => $before,
				'after' => $after
			);
		}

		closedir($dh);

		foreach ($avail_modules as $mod_name=>$item) {
			foreach ($item['after'] as $name) {
				if (!array_key_exists($name, $avail_modules)) {
					$this->message("\"$mod_name\" depends on \"$name\", but \"$name\" not found");
					return;
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

		$modules_list = array();
		$modules_hash = array();

		for (;;)
		{
			$has_changes = false;
			$has_modules = false;

			foreach ($avail_modules as $mod_name=>$item)
			{
				if (array_key_exists($mod_name, $modules_hash)) continue;

				$has_modules = true;
				$can_insert = true;

				foreach ($item['after'] as $name)
				{
					if (!array_key_exists($name, $modules_hash))
					{
						$can_insert = false;
						break;
					}
				}

				if (!$can_insert) continue;

				$class_name = $item['class'];
				$modules_hash[$mod_name] = true;

				$modules_list[] = $mod_name;
				$has_changes = true;
			}

			if (!$has_modules) {
				break;
			}

			if (!$has_changes) {
				$this->message("Can't resolve modules dependencies");
				return;
			}
		}

		foreach ($modules_list as $module) {
			$this->call_module_method($module, 'module_install', 'Installing');
		}
	}

	protected function uninstall($module)
	{
		if (!$module) {
			$this->message('Please specify module to uninstall');
		} else {
			$this->call_module_method($module, 'module_uninstall', 'UnInstalling');
		}
	}

	public function run($params)
	{
		$command = isset($params[0]) ? $params[0] : '';
		$argument = isset($params[1]) ? $params[1] : '';

		switch ($command)
		{
			case '':
				$this->message('Available commands: install, uninstall');
				break;

			case 'install':
				$this->install($argument);
				break;

			case 'uninstall':
				$this->uninstall($argument);
				break;

			default:
				$this->message("Unknown command \"$command\"");
				break;
		}
	}
}
