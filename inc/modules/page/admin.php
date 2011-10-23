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

class PageAdminModule extends BaseAdminModule
{
	public static function module_install()
	{
		self::def_module_install('Page', Loc::get('page/root-title'));
	}

	public static function module_uninstall()
	{
		self::def_module_uninstall('Page');
	}

	public function get_tree_subpath()
	{
		return Page::Root;
	}

	public function get_new_node_name($node)
	{
		return Loc::get('page/new-page');
	}

	public function render_editor($node)
	{
		$page = dynamic_cast($node, 'Page');
		$data = array();

		if (_POST('action') == 'save') $this->def_save_node($data, $page);
		$this->fill_def_form_data($data, $page);

		$avail_types = array(
			'text' => Loc::get('page/type/text'),
			'transfer' => Loc::get('page/type/transfer'),
			'redirect' => Loc::get('page/type/redirect')
		);

		$data['rows'][] = array('id'=>'page_type', 'label'=>Loc::get('page/label/page-type'), 'type'=>'dropdown', 'value'=>$page->page_type, 'options'=>$avail_types);
		$data['auto_submit'] = array('page_type');

		switch ($page->page_type)
		{
			case 'transfer':
				$data['rows'][] = array('id'=>'transfer_path', 'label'=>Loc::get('page/label/transfer-path'), 'type'=>'text', 'value'=>$page->transfer_path);
				break;

			case 'redirect':
				$data['rows'][] = array('id'=>'redirect_url', 'label'=>'URL', 'type'=>'text', 'value'=>$page->redirect_url);
				break;

			default:
				$data['rows'][] = array('id'=>'use_html_editor', 'label'=>Loc::get('page/label/use-html-editor'), 'type'=>'checkbox', 'value'=>$page->use_html_editor);
				$data['rows'][] = array('id'=>'content', 'label'=>Loc::get('page/label/content'), 'type'=>($page->use_html_editor ? 'editor' : 'textarea'), 'value'=>$page->content);
				$data['auto_submit'][] = 'use_html_editor';
				break;
		}

		return $this->render_form($data);
	}
}
