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

require_once('../s/s.php');
require_once(BASE . 'inc/core/core.php');
require_once(CMS . 'admin/core.php');

require_once(CMS . 'modules/user/model.php');

class ModulePage extends SAjaxPage
{
	public $modules = array();

	public function __construct()
	{
		parent::__construct();
		$this->design_page_name = '';

		$this->add_event(PAGE_INIT, 'check_login');
		$this->add_event(PAGE_INIT, 'on_init');

		if ($dh = opendir(CMS . 'modules/'))
		{
			while (($name = readdir($dh)) !== false)
			{
				if (is_dir(CMS . 'modules/' . $name))
				{
					if (@file_exists(CMS . 'modules/' . $name . '/admin.php'))
					{
						require_once(CMS . 'modules/' . $name . '/admin.php');
						$class_name = Cms::capitalize_words($name) . 'AdminModule';

						$this->modules[$name] = new $class_name;
						$this->modules[$name]->owner = $this;
					}
				}
			}

			closedir($dh);
		}

		uasort($this->modules, create_function('$a,$b','return Cms::path_parts_count($b->get_tree_subpath())-Cms::path_parts_count($a->get_tree_subpath());'));
	}

	protected function check_login()
	{
		if (User::is_empty_users()) return;

		if (!_SESSION('logged', false) || _SESSION('logged_ip')!=$_SERVER['REMOTE_ADDR'])
		{
			echo '<html><head><title></title><body onload="window.parent.location=window.parent.location;"></body></html>';
			$this->break_flow();
			return;
		}
	}

	protected function on_init()
	{
		$this->vars['content'] = '';

		if (strlen(_GET('node_id')))
		{
			$node = new Node();
			if (!$node->find_by_id(_GET('node_id'))) return;
			if (!array_key_exists($node->type, $this->modules)) return;

			$this->vars['content'] = $this->modules[$node->type]->render_editor($node);
		}
		elseif (strlen(_GET('parent_id')))
		{
			$parent_node = new Node();
			if (!$parent_node->find_by_id(_GET('parent_id'))) return;

			if (array_key_exists($parent_node->type, $this->modules))
			{
				if (!$this->modules[$parent_node->type]->can_has_childs($parent_node)) {
					return;
				}

				$type = $this->modules[$parent_node->type]->get_new_node_type($parent_node, $parent_node->type);
			}
			else
			{
				$type = '';

				foreach ($this->modules as $mtype=>$module) {
					if ($module->get_tree_subpath()!='' && Cms::path_start_with($parent_node->full_path, $module->get_tree_subpath(), true)) {
						$type = $module->get_new_node_type($parent_node, $mtype);
						break;
					}
				}
			}

			if ($type == '') {
				return;
			}

			$node = new Node();
			$node->type = $type;
			$node->flags = (Node::Visible | Node::UserChilds);
			$node->path = $parent_node->full_path . '/';

			$this->vars['content'] = $this->modules[$type]->render_editor($node);
		}
		elseif (strlen(_GET('module')) && strlen(_GET('item_id')))
		{
			if (!array_key_exists(_GET('module'), $this->modules)) return;

			require_once(CMS . 'modules/' . _GET('module') . '/admin.php');
			$class_name = $this->modules[_GET('module')];

			$module = new $class_name;
			$module->owner = $this;
			$this->vars['content'] = $module->render_nav_editor(_GET('item_id'));
		}
	}
}

$page = new ModulePage();
$page->process();
