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

require_once(dirname(__FILE__) . '/model.php');

class CustomListAdminModule extends BaseAdminModule
{
	public static function _after()
	{
		return 'page';
	}

	public static function module_install()
	{
		self::def_module_install('CustomList', Loc::get('custom-list/root-title'));
	}

	public static function module_uninstall()
	{
		self::def_module_uninstall('CustomList');
	}

	public function get_new_node_type($node, $mtype)
	{
		return ($node->type == Node::Folder ? 'custom_list' : 'custom_list_item');
	}

	public function can_put($parent_path, $parent_node)
	{
		return ($parent_path == CustomList::Root ? '' : false);
	}

	public function get_tree_subpath()
	{
		return CustomList::Root;
	}

	public function get_new_node_name($node)
	{
		return Loc::get('custom-list/new-list');
	}

	public function render_editor($node)
	{
		$node = dynamic_cast($node, 'CustomList');
		$data = array();

		if (_POST('action') == 'save') {
			$this->def_save_node($data, $node);
		}

		$this->fill_def_form_data($data, $node);
		return $this->render_form($data);
	}
}
