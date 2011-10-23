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

class Node extends SRecord
{
	const PathSize = 1024;

	const Visible = 1;
	const System = 2;
	const UserChilds = 4;

	const Folder = 'folder';
	const Setting = 'setting';

	public $path = '';
	public $name = '';
	public $title = '';
	public $type = '';
	public $flags = 0;
	public $position = 0;
	public $accessors = array();
	public $editable_fields = array();

	protected $_attr = null;
	protected $_acl = null;
	protected $_childs = null;
	protected $_parent_node = false;
	protected $_parent_class = 'Node';
	protected $_child_class = 'Node';

	public function __construct()
	{
		$this->map_table('nodes');

		parent::__construct();

		$this->has_many('attributes', 'Attribute', ':id=node_id');
		$this->has_many('acl', 'Acl', ':id=node_id');

		$this->after_save_filter('on_after_save');
		$this->after_remove_filter('on_after_remove');

		if (method_exists($this, '__wakeup')) $this->__wakeup();
	}

	public function attr_accessors($fields)
	{
		foreach ($fields as $fld => $edit_opts)
		{
			if (is_numeric($fld)) {
				$fld = $edit_opts;
				$edit_opts = null;
			}

			$spl = explode('|', $fld);
			$opts = array('def'=>'', 'type'=>'str');

			for ($i = 1; $i < count($spl); $i++)
			{
				list($k, $v) = explode('=', $spl[$i]);
				if (isset($v)) $opts[trim($k)] = trim($v);
			}

			$fld = trim($spl[0]);
			$this->accessors[$fld] = $opts;

			if ($edit_opts) {
				$this->editable_fields[$fld] = $edit_opts;
			}
		}
	}

	public function _set_($name, $value)
	{
		if (array_key_exists($name, $this->accessors))
		{
			switch ($this->accessors[$name]['type'])
			{
				case 'bool':
					$value = (is_bool($value) ? ($value ? '1' : '0') : ($value=='1' ? '1' : '0'));
					break;
			}

			$this->set_attr($name, $value);
			return;
		}

		throw new Exception("Property $name not found");
	}

	public function _get_($name)
	{
		if (array_key_exists($name, $this->accessors)) {
			return $this->attr($name);
		}

		return parent::_get_($name);
	}

	##
	# = set parent_class($parent_class)
	# Use only in __wakeup and __construct
	##
	public function _set_parent_class($parent_class)
	{
		$this->_parent_class = $parent_class;
		$this->_parent_node = false;
	}

	##
	# = set child_class($child_class)
	# Use only in __wakeup and __construct
	##
	public function _set_child_class($child_class)
	{
		$this->_child_class = $child_class;
		$this->_childs = null;
	}

	public function _get_full_path()
	{
		return $this->path . $this->name;
	}

	public function get_childs_with_class($child_class=null)
	{
		return SRecord::find_all(($child_class ? $child_class : $this->_child_class), array('path=', $this->path . $this->name . '/'), 'position');
	}

	public function _get_childs()
	{
		if ($this->_childs === null) {
			$this->_childs = $this->get_childs_with_class();
		}

		return $this->_childs;
	}

	public function _get_visible_childs()
	{
		$res = array();

		foreach ($this->childs as $child) {
			if ($child->flags & self::Visible) {
				$res[] = $child;
			}
		}

		return $res;
	}

	public function _get_parent_path()
	{
		return substr($this->path, 0, -1);
	}

	public function _get_parent_node()
	{
		if ($this->_parent_node === false)
		{
			if ($this->path != '')
			{
				self::path_parts(substr($this->path, 0, -1), $path, $name);

				$class_name = $this->_parent_class;
				$this->_parent_node = new $class_name();

				if (!$this->_parent_node->find(array(array('path=', $path), array('name=', $name)))) {
					$this->_parent_node = null;
				}
			}
			else
			{
				$this->_parent_node = null;
			}
		}

		return $this->_parent_node;
	}

	// функция **не** апдейтит позицию у текущей ноды, только у других
	protected function update_positions($position=0)
	{
		$parent_childs = ($this->parent_node==null ? self::get_root_nodes() : $this->parent_node->childs);
		$replace_data = array();
		$pos = 1;

		foreach ($parent_childs as $nd)
		{
			if ($nd->id != $this->id)
			{
				if ($pos == $position) $pos++;

				if ($nd->position != $pos) {
					$replace_data[] = sprintf(
						"(%s,%s,%s,%s,%s,%s,%s)",
						SDB::quote($nd->id),
						SDB::quote($nd->path),
						SDB::quote($nd->name),
						SDB::quote($nd->title),
						SDB::quote($nd->type),
						SDB::quote($nd->flags),
						SDB::quote($pos)
					);
				}

				$pos++;
			}
		}

		if (count($replace_data))
		{
			$cmd = new SDBCommand("REPLACE INTO `nodes` (`id`,`path`,`name`,`title`,`type`,`flags`,`position`) VALUES " . join(',', $replace_data));
			$cmd->execute();
		}

		return $pos;
	}

	protected function on_after_remove()
	{
		$this->update_positions();

		$cmd = new SDBCommand("SELECT id FROM nodes WHERE path LIKE @path");
		$cmd->set('path', $this->path . $this->name . '/%', SDB::String, self::PathSize);
		$arr = $cmd->get_all();

		$ids = array();
		foreach ($arr as $row) $ids[] = $row['id'];

		if (count($ids))
		{
			$cmd = new SDBCommand("DELETE FROM nodes WHERE id IN (@ids)");
			$cmd->set('ids', $ids, SDB::IntsList);
			$cmd->execute();
		}

		$ids[] = $this->id;

		$cmd = new SDBCommand("DELETE FROM attributes WHERE node_id IN (@ids)");
		$cmd->set('ids', $ids, SDB::IntsList);
		$cmd->execute();

		$cmd = new SDBCommand("DELETE FROM acl WHERE node_id IN (@ids)");
		$cmd->set('ids', $ids, SDB::IntsList);
		$cmd->execute();

		$path = CMS . "modules/{$this->type}/model.php";

		if (is_readable($path))
		{
			require_once($path);
			$class_name = Cms::capitalize_words($this->type);

			if (is_callable(array($class_name, '_cleanup'))) {
				foreach ($ids as $id) {
					call_user_func(array($class_name, '_cleanup'), $id);
				}
			}
		}
	}

	public function move($full_path, $position)
	{
		if (($full_path == $this->path.$this->name) && ($position == $this->position)) {
			return;
		}

		self::lock();
		$prev_path = $this->path;

		if ($full_path != $this->path.$this->name)
		{
			// Проапдейтить позицию в папке **откуда** была нода
			$this->update_positions();

			$cmd = new SDBCommand("SELECT id,path FROM nodes WHERE path LIKE @path");
			$cmd->set('path', $this->path . $this->name . '/%', SDB::String, self::PathSize);
			$arr = $cmd->get_all();

			$len = strlen($this->path . $this->name);

			$cmd = new SDBCommand("UPDATE nodes SET path=@path WHERE id=@id");
			$cmd->set('id', null, SDB::Int);
			$cmd->set('path', null, SDB::String, self::PathSize);

			foreach ($arr as $row)
			{
				$cmd->set('id', $row['id']);
				$cmd->set('path', $full_path . substr($row['path'], $len));
				$cmd->execute();
			}

			self::path_parts($full_path, $path, $name);

			$this->path = $path;
			$this->name = $name;

			$this->_childs = null;
			$this->_parent_node = false;
		}

		if ($prev_path!=$this->path || $position!=$this->position)
		{
			// Проапдейтить позицию в папке **куда** переместилась нода
			$pos = $this->update_positions($position);
			$this->position = (($position==0 || $position>$pos) ? $pos : $position);
		}

		$this->save();
		self::unlock();
	}

	public function rename($name)
	{
		$this->move($this->path . $name, $this->position);
	}

	protected function ensure_attr()
	{
		if ($this->_attr !== null) return;

		$this->_attr = array();

		if (!$this->is_new())
		{
			foreach ($this->attributes as $attr)
			{
				if (!array_key_exists('n'.$attr->name, $this->_attr)) {
					$this->_attr['n'.$attr->name] = array();
				}

				$this->_attr['n'.$attr->name]['n'.$attr->lang] = array('i'=>$attr->id, 'v'=>$attr);
			}
		}
	}

	public function has_attr($name, $lang='')
	{
		$this->ensure_attr();
		return (array_key_exists('n'.$name, $this->_attr) && array_key_exists('n'.$lang, $this->_attr['n'.$name]));
	}

	public function attr($name, $def=null, $lang='')
	{
		$this->ensure_attr();

		if ($def === null) {
			if (array_key_exists($name, $this->accessors)) {
				$def = $this->accessors[$name]['def'];
			} else {
				$def = '';
			}
		}

		$value = ((
			array_key_exists('n'.$name, $this->_attr) &&
			array_key_exists('n'.$lang, $this->_attr['n'.$name]) &&
			$this->_attr['n'.$name]['n'.$lang] !== null
		) ? $this->_attr['n'.$name]['n'.$lang]['v']->value : $def);

		if (array_key_exists($name, $this->accessors))
		{
			switch ($this->accessors[$name]['type'])
			{
				case 'bool':
					$value = ($value == '1');
					break;
			}
		}

		return $value;
	}

	public function set_attr($name, $value, $lang='')
	{
		$this->ensure_attr();

		if (!array_key_exists('n'.$name, $this->_attr)) {
			$this->_attr['n'.$name] = array();
		}

		if (!array_key_exists('n'.$lang, $this->_attr['n'.$name]))
		{
			$attr = new Attribute();
			$attr->name = $name;
			$attr->lang = $lang;
			$attr->value = $value;

			$this->_attr['n'.$name]['n'.$lang] = array('i'=>0, 'v'=>$attr);
		}
		else
		{
			$this->_attr['n'.$name]['n'.$lang]['v']->value = $value;
		}
	}

	public function rm_attr($name, $lang='')
	{
		$this->ensure_attr();

		if (array_key_exists('n'.$name, $this->_attr) && array_key_exists('n'.$lang, $this->_attr['n'.$name])) {
			$this->_attr['n'.$name]['n'.$lang]['v'] = null;
		}
	}

	protected function ensure_acl()
	{
		if ($this->_acl === null)
		{
			$this->_acl = array();

			if (!$this->is_new())
			{
				foreach ($this->acl as $acl)
				{
					if (!array_key_exists($acl->action, $this->_acl)) {
						$this->_acl[$acl->action] = array();
					}

					$this->_acl[$acl->action][$acl->ident] = true;
				}
			}
		}
	}

	public function has_rights($idents, $actions)
	{
		foreach ($idents as $ident)
		{
			$has = true;

			foreach ($actions as $action)
			{
				if (!array_key_exists($action, $this->_acl) ||
					!array_key_exists($ident, $this->_acl[$action]) ||
					!$this->_acl[$action][$ident])
				{
					$has = false;
					break;
				}
			}

			if ($has) return true;
		}

		return false;
	}

	public function set_rights($idents, $actions)
	{
		foreach ($actions as $action)
		{
			if (!array_key_exists($action, $this->_acl)) {
				$this->_acl[$action] = array();
			}

			foreach ($idents as $ident) {
				$this->_acl[$action][$ident] = true;
			}
		}
	}

	public function rm_rights($idents, $actions)
	{
		foreach ($actions as $action) {
			if (array_key_exists($action, $this->_acl)) {
				foreach ($idents as $ident) {
					if (array_key_exists($ident, $this->_acl[$action])) {
						$this->_acl[$action][$ident] = false;
					}
				}
			}
		}
	}

	protected function on_after_save($was_new)
	{
		if ($this->_attr !== null)
		{
			$res_attr = array();

			foreach ($this->_attr as $attrs)
			{
				foreach ($attrs as $attr)
				{
					if ($attr['v'] === null)
					{
						if ($attr['i'] != 0) {
							SRecord::remove_by_id('Attribute', $attr['i']);
						}
					}
					else
					{
						$attr['v']->node_id = $this->id;

						if ($attr['v']->is_dirty()) {
							$attr['v']->save();
						}

						if (!array_key_exists('n'.$attr['v']->name, $res_attr)) {
							$res_attr['n'.$attr['v']->name] = array();
						}

						$res_attr['n'.$attr['v']->name]['n'.$attr['v']->lang] = array('i'=>$attr['v']->id, 'v'=>$attr['v']);
					}
				}
			}

			$this->_attr = $res_attr;
			$res_attr = null;
		}

		if ($this->_acl !== null)
		{
			$existing_acl = array();

			foreach ($this->acl as $acl)
			{
				$existing_acl[urlencode($acl->action) . ':' . urlencode($acl->ident)] = true;

				if (!array_key_exists($acl->action, $this->_acl) ||
					!array_key_exists($this->_acl[$acl->action], $acl->ident) ||
					!$this->_acl[$acl->action][$acl->ident])
				{
					$acl->remove();
				}
			}

			foreach ($this->_acl as $action)
			{
				foreach ($this->_acl[$action] as $ident)
				{
					if ($this->_acl[$action][$ident] && !array_key_exists(urlencode($action) . ':' . urlencode($ident)))
					{
						$acl = new Acl();
						$acl->node_id = $this->id;
						$acl->action = $action;
						$acl->ident = $ident;
						$acl->save();
					}
				}
			}
		}

		$path = CMS . "modules/{$this->type}/model.php";

		if (is_readable($path))
		{
			require_once($path);
			$class_name = Cms::capitalize_words($this->type);

			if (is_callable(array($class_name, '_saved'))) {
				call_user_func(array($class_name, '_saved'), $this, $was_new);
			}
		}
	}

	public static function path_parts($full_path, &$path, &$name)
	{
		$ind = strrpos($full_path, '/');

		if ($ind === false)
		{
			$path = '';
			$name = $full_path;
		}
		else
		{
			$path = substr($full_path, 0, $ind+1);
			$name = substr($full_path, $ind+1);
		}
	}

	public static function lock()
	{
		$cmd = new SDBCommand("START TRANSACTION");
		$cmd->execute();
	}

	public static function unlock()
	{
		$cmd = new SDBCommand("COMMIT");
		$cmd->execute();
	}

	public static function get_root_nodes()
	{
		return SRecord::find_all('Node', array('path=', ''), 'position');
	}

	public static function get_max_position($path)
	{
		$cmd = new SDBCommand("SELECT MAX(position) FROM nodes WHERE path=@path");
		$cmd->set('path', $path, SDB::String, self::PathSize);
		$pos = $cmd->get_one();

		return ($pos === null ? 0 : $pos);
	}

	public static function get_node($full_path, $class_name='Node')
	{
		self::path_parts($full_path, $path, $name);
		$node = new $class_name();

		if (!$node->find(array(array('path=', $path), array('name=', $name)))) {
		    return null;
		} else {
		    return $node;
		}
	}

	public static function get_node_by_id($id, $class_name='Node')
	{
		$node = new $class_name();

		if (!$node->find_by_id($id)) {
			return null;
		} else {
			return $node;
		}
	}

	public static function get_node_childs($full_path, $class_name='Node', $child_class_name=null)
	{
		$node = self::get_node($full_path, $class_name);
		return ($node ? $node->get_childs_with_class($child_class_name) : array());
	}

	public static function create_node($type, $full_path, $title, $flags=-1, $save=true)
	{
		if ($save) {
			self::lock();
		}

		self::path_parts($full_path, $path, $name);

		$node = new Node();
		$node->path = $path;
		$node->name = $name;
		$node->title = $title;
		$node->type = $type;
		$node->flags = ($flags<0 ? (self::Visible | self::UserChilds) : $flags);
		$node->position = self::get_max_position($path) + 1;

		if ($save)
		{
			$node->save();
			self::unlock();
		}

		return $node;
	}

	public function create_child($type, $name, $title, $flags)
	{
	    return self::create_node($type, "{$this->full_path}/{$name}", $title, $flags);
	}

	// *********************************************************************************************

	public function _get_model_path()
	{
		return substr($this->full_path, strlen(constant(get_class($this).'::Root')) + 1);
	}

	public function model_path_start_with($search)
	{
		$mp = $this->model_path . '/';
		return (($mp == $search) || (strpos($mp, $search . '/') === 0));
	}

	##
	# = public string get_field(string $field)
	# Returns non-empty field. If field in this node is empty, returns field from parent node (if field in parent node is empty ... etc)
	##
	public function get_field($field)
	{
		$node = $this;

		while ($node)
		{
			$ret = $node->attr($field);
			if (strlen($ret)) return $ret;

			$node = $node->parent_node;
		}

		return '';
	}

	// *********************************************************************************************

	public static function get_by_model_path($model, $path)
	{
		$node = self::get_node(constant("{$model}::Root") . '/' . $path);
		return ($node==null ? null : dynamic_cast($node, $model));
	}

	public function get_child($name)
	{
	    return self::get_node("{$this->full_path}/{$name}", $this->_child_class);
	}
}
