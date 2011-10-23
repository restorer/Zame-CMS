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

class UserAdminModule extends BaseAdminModule
{
	public static function _after()
	{
		return 'page';
	}

	public static function module_install()
	{
		self::def_module_install('User', Loc::get('user/root-title'));
	}

	public static function module_uninstall()
	{
		self::def_module_uninstall('User');
	}

	public function can_has_childs($node)
	{
		return false;
	}

	public function get_tree_subpath()
	{
		return User::Root;
	}

	public function get_new_node_name($node)
	{
		return Loc::get('user/new-user');
	}

	public function get_custom_cls($node)
	{
		$user = dynamic_cast($node, 'User');
		return (strlen($user->pwd_hash) ? '' : 'err-item');
	}

	public function render_editor($node)
	{
		$node = dynamic_cast($node, 'User');
		$data = array();

		if (_POST('action') == 'save')
		{
			// TODO: сделать астоматическую чекалку на основе полей 'validate' в $data['rows']
			if (strlen(_POST('_img_data')) && strlen(_POST('_chb_data')) && _POST('_img_data')!=_POST('_chb_data')) {
				$data['errors']['_chb_data'] = Loc::get('user/should-be-same-as-password');
			}

			if ((!isset($data['errors']) || !count($data['errors'])) && strlen(_POST('_img_data')))
			{
				$node->pwd_hash = User::password_to_hash(_POST('_img_data'));
				$data['extra']['update_tree'] = true;
			}

			$this->def_save_node($data, $node);
		}

		$this->fill_def_form_data($data, $node, Loc::get('user/label/login'), BaseAdminModule::PATH_TYPE_NAME);

		if (!strlen($node->pwd_hash)) {
			$data['rows'][] = array('type'=>'html', 'value'=>'<span class="err-row">'.Loc::get('user/empty-password').'</span>');
		}

		$data['rows'][] = array('id'=>'_img_data', 'label'=>Loc::get('user/label/password'), 'type'=>'password');
		$data['rows'][] = array('id'=>'_chb_data', 'label'=>Loc::get('user/label/confirm-password'), 'type'=>'password', 'validate'=>array(array('SValidators.compare', '_img_data')));

		return $this->render_form($data);
	}
}
