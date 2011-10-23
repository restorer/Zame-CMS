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

class SettingsAdminModule extends BaseAdminModule
{
	public static function _before()
	{
		return 'user';
	}

	public static function module_install()
	{
		self::def_module_install('Settings', Loc::get('settings/root-title'), 'settings', Node::Visible | Node::System);
	}

	public static function module_uninstall()
	{
		self::def_module_uninstall('Settings');
	}

	public function get_tree_subpath()
	{
		return Settings::Root;
	}

	public function render_editor($node)
	{
		$node = dynamic_cast($node, 'Settings');
		$data = array();

		if (_POST('action') == 'save') {
			$this->def_save_node($data, $node, BaseAdminModule::UPDATE_TYPE_NONE);
		}

		$this->fill_def_form_data($data, $node, null, BaseAdminModule::PATH_TYPE_HIDDEN_ALL);
		return $this->render_form($data);
	}
}
