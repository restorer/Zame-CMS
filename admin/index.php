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

class AdminPage extends SAjaxPage
{
	public $modules = array();
	public $subtree_type_handlers = array();

	public function __construct()
	{
		parent::__construct();
		$this->design_page_name = '';

		$this->add_event(PAGE_INIT, 'check_login');
		$this->add_event(PAGE_INIT, 'on_init');
		$this->add_event(AJ_INIT, 'ajax_check_login');

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

			foreach ($this->modules as $module) {
				$module->register();
			}
		}
	}

	protected function ajax_check_login($method)
	{
		if ($method == 'logout' || User::is_empty_users()) return;

		if (!_SESSION('logged', false) || _SESSION('logged_ip')!=$_SERVER['REMOTE_ADDR'])
		{
			$this->break_flow();
			return Loc::get('cms/admin/not-logged');
		}
	}

	protected function check_login()
	{
		if (User::is_empty_users()) return;

		if (!_SESSION('logged', false) || _SESSION('logged_ip')!=$_SERVER['REMOTE_ADDR'])
		{
			$this->vars['error'] = '';

			if (_POST('enter'))
			{
				$username = strtolower(trim(_POST('data')));
				$pwd_hash = User::password_to_hash(trim(_POST('value')));

				$user = (strlen($username) ? Node::get_by_model_path('User', $username) : null);

				if ($user!=null && $user->pwd_hash==$pwd_hash)
				{
					$_SESSION['logged'] = true;
					$_SESSION['logged_ip'] = $_SERVER['REMOTE_ADDR'];

					$this->redirect($_SERVER['REQUEST_URI']);
					return;
				}
				else
				{
					$this->vars['error'] = Loc::get('cms/admin/invalid-login-or-password');
				}
			}

			$this->template_name = dirname(__FILE__) . '/login.tpl';
			$this->_flow = PAGE_FLOW_RENDER;
			return;
		}
	}

	protected function get_js_type($node)
	{
		if ($node->flags & Node::System) {
			return ($node->flags & Node::UserChilds ? 'c' : '');
		}

		if (array_key_exists($node->type, $this->modules))
		{
			$res = '';
			$module = $this->modules[$node->type];

			if ($module->can_has_childs($node)) $res .= 'c';
			if ($module->can_rename($node)) $res .= 'r';
			if (!strlen($module->can_delete($node))) $res .= 'd';
			if ($module->can_move($node)) $res .= 'g';

			return $res;
		}

		return 'crdg';
	}

	protected function get_node_custom_cls($node)
	{
		if (array_key_exists($node->type, $this->modules)) {
			return $this->modules[$node->type]->get_custom_cls($node);
		}

		return '';
	}

	protected function get_item_html($item, $js_type_rm, $deep, $opened_ids)
	{
		$res = '';

		if ($item instanceof Node)
		{
			$node = $item;

			$custom_cls = $this->get_node_custom_cls($node);
			if (strlen($custom_cls)) $custom_cls = ' ' . $custom_cls;

			$js_type = $this->get_js_type($node);
			if (strlen($js_type_rm)) $js_type = str_replace($js_type_rm, '', $js_type);

			if (array_key_exists($node->full_path, $this->subtree_type_handlers))
			{
				$module = $this->modules[$this->subtree_type_handlers[$node->full_path]];
				$sub_type = $module->get_subtree_type($node);
			}
			elseif (array_key_exists($node->type, $this->modules))
			{
				$module = $this->modules[$node->type];
				$sub_type = $module->get_subtree_type($node);
			}
			else
			{
				$sub_type = '';
			}

			$res .= '<li id="n_' . $node->id . '" rel="' . $js_type . '" sub_type="' . $sub_type . '"' . /* $new_node_params . */ '>';
			$res .= '<a href="#" class="n-' . htmlspecialchars($node->type) . $custom_cls . '">' . htmlspecialchars($node->title) . '</a>';

			if ($sub_type == '')
			{
				$res .= $this->get_nodes_html($node->childs, $deep, $opened_ids);
			}
			elseif ($sub_type == 'sub')
			{
				$sub_items = $module->get_subtree_items($node);

				if (count($sub_items))
				{
					$res .= '<ul>';
					foreach ($sub_items as $sub_item) $res .= $this->get_item_html($sub_item, 'g', $deep-1, $opened_ids);
					$res .= '</ul>';
				}
			}
		}
		else
		{
			throw new Exception('TODO');
		}

		return ($res . '</li>');
	}

	protected function get_nodes_html($nodes, $deep=2, $opened_ids=array())
	{
		if ($deep <= 0) {
			return (count($nodes) ? '<ul></ul>' : '');
		}

		$res = '';

		foreach ($nodes as $node)
		{
			if (!($node->flags & Node::Visible)) continue;
			$res .= $this->get_item_html($node, '', ($deep>1 ? $deep-1 : (array_key_exists($node->id, $opened_ids) ? 1 : 0)), $opened_ids);
		}

		return (strlen($res) ? "<ul>$res</ul>" : '');
	}

	protected function on_init()
	{
		$this->vars['nodes_html'] = $this->get_nodes_html(Node::get_root_nodes());
	}

	protected function can_put($parent_path, $name, $type, $parent_node=null, $check_names=true)
	{
		if ($parent_node == null)
		{
			$parent_node = Node::get_node($parent_path);
			if ($parent_node == null) return 'Parent node not found';
		}
		else
		{
			$parent_path = $parent_node->full_path;
		}

		if ($check_names)
		{
			if (array_key_exists($parent_node->type, $this->modules)) {
				if (!$this->modules[$parent_node->type]->can_has_childs($parent_node)) {
					return '@denied';
				}
			}

			foreach ($parent_node->childs as $nd) {
				if ($nd->name == $name) {
					return "Путь \"{$nd->full_path}\" уже существует";
				}
			}
		}

		$res = $this->modules[$type]->can_put($parent_path, $parent_node);
		return (($res===null || $res===false) ? '@denied' : $res);
	}

	protected function aj_delete_node($node_id)
	{
		$node = new Node();

		if (!$node->find_by_id($node_id)) throw new Exception('Node not found');
		if ($node->flags & Node::System) throw new Exception('Access denied');
		if ($node->type == '') throw new Exception("Empty node type (id={$node->id})");

		if (array_key_exists($node->type, $this->modules))
		{
			$res = $this->modules[$node->type]->can_delete($node);
			if (strlen($res)) throw new Exception($res);
		}

		$node->remove();
	}

	protected function aj_move_node($node_id, $ref_node_id, $move_type)
	{
		if ($move_type!='inside' && $move_type!='before' && $move_type!='after') throw new Exception('Invalid move_type: ' . $move_type);

		$node = new Node();
		if (!$node->find_by_id($node_id)) throw new Exception('Node not found');

		if (array_key_exists($node->type, $this->modules)) {
			if (!$this->modules[$node->type]->can_move($node)) {
				throw new Exception('@denied');
			}
		}

		$ref_node = new Node();
		if (!$ref_node->find_by_id($ref_node_id)) throw new Exception('RefNode not found');

		if ($move_type == 'inside')
		{
			$put_err = $this->can_put('', $node->name, $node->type, $ref_node);
			if (strlen($put_err)) throw new Exception($put_err);

			$node->move($ref_node->full_path . '/' . $node->name, 0);
		}
		else
		{
			$put_err = $this->can_put($ref_node->parent_path, $node->name, $node->type, null, ($ref_node->parent_path != $node->parent_path));
			if (strlen($put_err)) throw new Exception($put_err);

			$node->move($ref_node->path . $node->name, $ref_node->position + ($move_type=='before' ? 0 : 1));
		}
	}

	protected function aj_get_nodes($opened_arr=array())
	{
		$opened_ids = array();
		foreach ($opened_arr as $id) $opened_ids[$id] = true;

		return $this->get_nodes_html(Node::get_root_nodes(), 2, $opened_ids);
	}

	protected function aj_get_childs($node_id)
	{
		$node = new Node();
		if (!$node->find_by_id($node_id)) throw new Exception('Node not found');

		return $this->get_nodes_html($node->childs, 1);
	}

	protected function aj_logout()
	{
		$_SESSION['logged'] = false;
		$_SESSION['logged_ip'] = '';
	}

	protected function aj_get_nav_items($node_id)
	{
		$node = new Node();
		if (!$node->find_by_id($node_id)) throw new Exception('Node not found');

		if (array_key_exists($node->full_path, $this->subtree_type_handlers)) {
			$mod_name = $this->subtree_type_handlers[$node->full_path];
		} else if (array_key_exists($node->type, $this->modules)) {
			$mod_name = $node->type;
		} else {
			throw new Exception('Appropriate module not found');
		}

		$res = $this->modules[$mod_name]->get_navigator_items($node);
		$res['module'] = $mod_name;

		return $res;
	}
}

$page = new AdminPage();
$page->process();
