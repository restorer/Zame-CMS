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

class BaseAdminModule
{
	const PATH_TYPE_EDITABLE = 0;
	const PATH_TYPE_READONLY = 1;
	const PATH_TYPE_NAME = 2;
	const PATH_TYPE_HIDDEN = 3;
	const PATH_TYPE_HIDDEN_ALL = 4;

	const UPDATE_TYPE_ALL = 0;
	const UPDATE_TYPE_TITLE = 1;
	const UPDATE_TYPE_NONE = 2;

	public $owner = null;

	public static function def_module_install($model, $title, $node_type=false, $flags=0)
	{
		$root = constant("{$model}::Root");

		if (Node::get_node($root) == null) {
			Node::create_node(($node_type ? $node_type : Node::Folder), $root, $title, ($flags ? $flags : (Node::Visible | Node::System | Node::UserChilds)));
		} else {
			$node = Node::get_node($root);
			$node->title = $title;
			$node->save();
		}
	}

	public static function def_module_uninstall($model)
	{
		$node = Node::get_node(constant("{$model}::Root"));

		if ($node) {
			$node->remove();
		}
	}

	public function register()
	{
	}

	public function can_has_childs($node)
	{
		return true;
	}

	public function can_rename($node)
	{
		return true;
	}

	##
	# = public string can_delete(object $node)
	# Returns empty string is node is deletable or error text, if node isn't deletable
	##
	public function can_delete($node)	// '
	{
		return '';
	}

	public function can_move($node)
	{
		return true;
	}

	public function can_put($parent_path, $parent_node)
	{
		$subpath = $this->get_tree_subpath();
		return ((strlen($subpath) && Cms::path_start_with($parent_path, $subpath)) ? '' : false);
	}

	public function get_custom_cls($node)
	{
		return '';
	}

	##
	# = public string get_tree_subpath()
	# This function is used in pair with get_new_node_type, when new nodes created in node with type "folder"
	##
	public function get_tree_subpath()
	{
		return '';
	}

	##
	# = public string get_new_node_type(object $node, string $mtype)
	# [$node] New node created in this node
	# [$mtype] Just module type
	#
	# **WARN:** function must return new type for it **child** nodes
	# This function can give more control of node creation, but usually it is not necessary
	#
	# If this function is called in two cases:
	# 1) When node created in node with type "folder" ($node->type, not $mtype)
	# 2) When node created in node with registered type
	##
	public function get_new_node_type($node, $mtype)
	{
		return $mtype;
	}

	##
	# = public function get_new_node_name($node)
	# **WARN:** function must return new name for it **child** nodes
	##
	public function get_new_node_name($node)
	{
		return Loc::get('cms/admin/new-element');
	}

	##
	# = public string get_subtree_type(object $node)
	# Return value can be empty string (no custom subtree type), 'sub' (subtree displayed in main tree) or 'nav' (navigator used to display items)
	##
	public function get_subtree_type($node)
	{
		return '';
	}

	public function get_subtree_items($node)
	{
		return array();
	}

	public function get_navigator_items($node)
	{
		return array();
	}

	public function get_js_row($row)
	{
		$id = (isset($row['id']) ? js_escape($row['id']) : '');
		$title = ((isset($row['label']) && $row['label']!==false) ? js_escape($row['label']) : '');
		$type = $row['type'];
		$def = (isset($row['value']) ? js_escape((string)$row['value']) : '');
		$params = '';
		$options = '';

		switch ($type)
		{
			case 'checkbox':
				$params = "{title:'$title'}";
				$title = '';
				$def = (strlen($def) ? '1' : '0');		// (string)true=='1' ; (string)false==''
				break;

			case 'editor':
				$type = 'textarea';
				// go down to 'textarea' case

			case 'textarea':
				$params = "{rows:" . (isset($row['rows']) ? $row['rows'] : 16) . ",cols:80}";
				break;

			case 'dropdown':
				$opts = array();
				foreach ($row['options'] as $k=>$v) $opts[] = "['" . js_escape($k) . "','" . js_escape($v) . "']";
				$options = '[' . join(',', $opts) . ']';
				break;

			case 'keyvalue':
				$type = 'html';
				$list = ($def == '' ? array() : SJson::deserialize($def));
				$rows = array();

				foreach ($list as $k=>$v) {
					$rows[] = join('', array(
						'<table cellspacing="0" cellpadding="0" width="100%" class="s-form-e"><tr valign="top">',
						'<td width="50%"><input class="s-inp" type="text" name="', $id, '_k_', count($rows), '" value="', htmlspecialchars($k), '" /></td>',
						'<td width="10">&nbsp;</td>',
						'<td width="50%">', (
							(isset($row['rows']) && $row['rows'] > 1) ?
							'<textarea class="s-inp s-inp-textarea" name="'.$id.'_v_'.count($rows).'" rows="'.$row['rows'].'">'.htmlspecialchars($v).'</textarea>' :
							'<input class="s-inp" type="text" name="'.$id.'_v_'.count($rows).'" value="'.htmlspecialchars($v).'" />'
						), '</td>',
						'<td width="10">&nbsp;</td>',
						'<td><input type="submit" name="', $id, '_rm_', count($rows), '" value="&ndash;" /></td>',
						'</tr></table>'
					));
				}

				$rows[] = join('', array(
					'<table cellspacing="0" cellpadding="0" width="100%" class="s-form-e"><tr valign="top">',
					'<td width="50%"><input class="s-inp" type="text" name="', $id, '_nk" value="" /></td>',
					'<td width="10">&nbsp;</td>',
						'<td width="50%">', (
							(isset($row['rows']) && $row['rows'] > 1) ?
							'<textarea class="s-inp s-inp-textarea" name="'.$id.'_nv" rows="'.$row['rows'].'"></textarea>' :
							'<input class="s-inp" type="text" name="'.$id.'_nv" value="" />'
						), '</td>',
					'<td width="10">&nbsp;</td>',
					'<td><input type="submit" name="', $id, '_add" value="+" /></td>',
					'</tr></table>'
				));

				$def = join('', $rows);
				break;

			default:
				$type = js_escape($type);
				break;
		}

		$res = array("id:'$id'", "type:'$type'", "def:'$def'");

		if (isset($row['label']) && $row['label'] === false) {
			$res[] = 'render_title:false';
		} elseif ($title != '') {
			$res[] = "title:'$title'";
		}

		if ($params != '') $res[] = "params:$params";
		if ($options != '') $res[] = "options:$options";

		if (array_key_exists('validate', $row))
		{
			$validate_arr = array();

			if (is_array($row['validate']))
			{
				foreach ($row['validate'] as $vr)
				{
					if (is_array($vr))
					{
						$tmp = array($vr[0]);

						for ($i = 1; $i < count($vr); $i++)
						{
							if (is_string($vr[$i])) $tmp[] = "'" . js_escape($vr[$i]) . "'";
							else $tmp[] = $vr[$i];
						}

						$validate_arr[] = '[' . join(',', $tmp) . ']';
					}
					else
					{
						$validate_arr[] = $vr;
					}
				}
			}
			else
			{
				$validate_arr[] = $row['validate'];
			}

			$res[] = 'validate:[' . join(',', $validate_arr) . ']';
		}

		return '{' . join(',', $res) . '}';
	}

	public function get_action_url()
	{
		$params = array();

		foreach ($_GET as $k=>$v) {
			if (!is_array($v) && !is_object($v)) {
				$params[] = urlencode($k) . '=' . urlencode($v);
			}
		}

		return '?' . join('&', $params);
	}

	public function render_form($data)
	{
		$tpl = new STemplate();

		if (!array_key_exists('buttons', $data)) {
			$data['buttons'] = array(
				array(
					'id' => 'save',
					'title' => Loc::get('cms/admin/save'),
					'validate' => true,
				)
			);
		}

		if (!array_key_exists('fields_width', $data)) {
			$data['fields_width'] = 480;
		}

		$tpl->vars['action_url'] = $this->get_action_url();
		$tpl->vars['data'] = $data;
		$tpl->vars['self'] = $this;

		return $tpl->process(dirname(__FILE__) . '/base_admin_module.tpl');
	}

	protected function get_new_name_for_node($node)
	{
		if (array_key_exists($node->type, $this->owner->modules)) {
			return $this->owner->modules[$node->type]->get_new_node_name($node);
		}

		$type = '';

		foreach ($this->owner->modules as $mtype=>$module)
		{
			if (Cms::path_start_with($node->parent_node->full_path, $module->get_tree_subpath(), true)) {
				$type = $module->get_new_node_type($node->parent_node, $mtype);
				break;
			}
		}

		if (strlen($type) && array_key_exists($type, $this->owner->modules)) {
			return $this->owner->modules[$type]->get_new_node_name($node);
		}

		return Loc::get('cms/admin/new-element');
	}

	public function fill_def_form_data(&$data, $node, $title_label=null, $path_type=0, $separate_title=false)
	{
		$data['title'] = (strlen($node->title) ? htmlspecialchars($node->title) : ('<em>' . $this->get_new_name_for_node($node) . '</em>'));
		$data['rows'] = array();

		if ($path_type != self::PATH_TYPE_HIDDEN_ALL)
		{
			$data['rows'][] = array(
				'id' => '_title',
				'label' => ($title_label === null ? Loc::get('cms/admin/title-label') : $title_label),
				'type' => 'text',
				'value' => $node->title,
				'validate' => 'SValidators.required'
			);
		}

		if ($path_type == self::PATH_TYPE_EDITABLE)
		{
			$data['rows'][] = array(
				'id' => '_name',
				'label' => Loc::get('cms/admin/path'),
				'type' => 'html',
				'value' => join('', array(
					'<table cellspacing="0" cellpadding="0" width="100%" class="s-form-e"><tr>',
					'<td nowrap="nowrap"><strong>', htmlspecialchars($node->parent_path), '/</strong></td>',
					'<td width="100%"><input class="s-inp" type="text" name="_name" value="', htmlspecialchars($node->name) ,'" /></td>',
					'</tr></table>'
				)),
			);
		}
		elseif ($path_type == self::PATH_TYPE_READONLY)
		{
			if ($node->name != '')
			{
				$data['rows'][] = array(
					'id' => '_name',
					'label' => Loc::get('cms/admin/path'),
					'type' => 'html',
					'value' => htmlspecialchars($node->parent_path) . '/<strong>' . htmlspecialchars($node->name) . '</strong>',
				);
			}
		}
		elseif ($path_type == self::PATH_TYPE_NAME)
		{
			if ($node->name != '')
			{
				$data['rows'][] = array(
					'id' => '_name',
					'label' => Loc::get('cms/admin/system-name'),
					'type' => 'html',
					'value' => htmlspecialchars($node->name),
				);
			}
		}

		if ($path_type != self::PATH_TYPE_HIDDEN_ALL && $separate_title)
		{
			$data['rows'][] = array(
				'id' => '_title_separator',
				'label' => false,
				'type' => 'html',
				'value' => '<hr />',
			);
		}

		foreach ($node->editable_fields as $field => $options)
		{
			if (!is_array($options)) {
				$options = array(
					'type' => $options,
				);
			}

			$options = $options + array(
				'id' => $field,
				'value' => $node->attr($field),
				'label' => Cms::capitalize_words($field, ' '),
			);

			$data['rows'][] = $options;
		}

		if (_SESSION('s.cms.admin.just-saved'))
		{
			$_SESSION['s.cms.admin.just-saved'] = false;
			$data['info'] = Loc::get('cms/admin/saved');
			$data['extra']['update_tree'] = true;
			$data['extra']['select_node'] = $node->id;
		}
	}

	public function def_save_node(&$data, $node, $update_type=0)
	{
		$prev_title = $node->title;
		$new_node_name = $node->name;

		if ($update_type != self::UPDATE_TYPE_NONE)
		{
			$node->title = trim(_POST('_title'));

			if ($node->title == '') {
				$data['errors']['_title'] = Loc::get('forms/validators/required');
			}
		}

		if ($update_type == self::UPDATE_TYPE_ALL)
		{
			$new_node_name = strtolower(trim(_POST('_name')));

			if ($new_node_name == '') {
				$new_node_name = Cms::title_to_name($node->title);
			}

			if ($node->title!='' && $new_node_name=='') {
				$data['errors']['_name'] = Loc::get('forms/validators/required');
			}

			if ($new_node_name != '')
			{
				if (!Cms::node_name_is_valid($new_node_name)) {
					$data['errors']['_name'] = Loc::get('cms/admin/node/invalid_name');
				} elseif (!$node->parent_node) {
					$data['errors']['_name'] = Loc::get('cms/admin/node/not_found_dunno', array($path_prepend));
				} else {
					foreach ($node->parent_node->childs as $nd) {
						if (($nd->name == $new_node_name) && ($nd->id != $node->id)) {
							$data['errors']['_name'] = Loc::get('cms/admin/node/exists');
							break;
						}
					}
				}
			}
		}

		foreach ($node->accessors as $name=>$opts) {
			if (isset($node->editable_fields[$name]['type']) && $node->editable_fields[$name]['type']=='keyvalue') {
				$do_trim = (isset($node->editable_fields[$name]['trim']) && $node->editable_fields[$name]['trim']);
				$list = ($node->$name == '' ? array() : SJson::deserialize($node->$name));
				$new_list = array();

				for ($i = 0; $i < count($list); $i++) {
					if (!inPOST("{$name}_rm_{$i}")) {
						$key = trim(_POST("{$name}_k_{$i}"));

						if ($key != '') {
							$value = _POST("{$name}_v_{$i}");

							if ($do_trim) {
								$value = trim($value);
							}

							$new_list[$key] = $value;
						}
					}
				}

				$key = trim(_POST("{$name}_nk"));

				if ($key != '') {
					$value = _POST("{$name}_nv");

					if ($do_trim) {
						$value = trim($value);
					}

					$new_list[$key] = $value;
				}

				$node->$name = SJson::serialize($new_list);
			} elseif (inPOST($name)) {
				$node->$name = _POST($name);
			}
		}

		if (!isset($data['errors']) || !count($data['errors']))
		{
			Node::lock();
			$was_new_node = $node->is_new();

			if (!$node->position) {
				$node->position = Node::get_max_position($node->path) + 1;
			}

			if ($update_type==self::UPDATE_TYPE_ALL && $new_node_name!=$node->name) {
				$node->rename($new_node_name);
				$name_updated = true;
			} else {
				$node->save();
				$name_updated = false;
			}

			Node::unlock();
			$data['info'] = Loc::get('cms/admin/saved');

			if ($node->title!=$prev_title || $name_updated)
			{
				if ($was_new_node)
				{
					$_SESSION['s.cms.admin.just-saved'] = true;
					header('Location: ' . ROOT . "admin/module.php?node_id={$node->id}");
					exit();
				}

				$data['extra']['update_tree'] = true;
			}

			$data['extra']['select_node'] = $node->id;
		}
	}

	public function render_def_editor($node, $class_name, $path_type=0, $update_type=0, $separate_title=false)
	{
		$node = dynamic_cast($node, $class_name);
		$data = array();

		if (_POST('action') == 'save') {
			$this->def_save_node($data, $node, $update_type);
		}

		$this->fill_def_form_data($data, $node, null, $path_type, $separate_title);
		return $this->render_form($data);
	}
}
