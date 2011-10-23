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

require_once('../../../s/s.php');
require_once(BASE . 'inc/core/core.php');
require_once(CMS . 'modules/user/model.php');

define('PUB_BASE', BASE . 'pub/');
define('PUB_ROOT', ROOT . 'pub/');

class BrowserPage extends SPage
{
	protected $type = 'file';

	public function __construct()
	{
		parent::__construct();
		$this->design_page_name = '';

		$this->add_event(PAGE_INIT, 'check_login');
		$this->add_event(PAGE_INIT, 'on_init');
		$this->add_event(PAGE_PRE_RENDER, 'on_pre_render');
	}

	protected function check_login()
	{
		if (User::is_empty_users()) return;

		if (!_SESSION('logged', false) || _SESSION('logged_ip')!=$_SERVER['REMOTE_ADDR'])
		{
			$this->template_name = dirname(__FILE__) . '/login-required.tpl';
			$this->_flow = PAGE_FLOW_RENDER;
			return;
		}
	}

	protected function on_init()
	{
		$this->type = _GET('type', 'file');
		if (!in_array($this->type, array('image', 'media', 'file'))) $this->type = 'file';

		$this->vars['type'] = $this->type;
	}

	protected function on_upload_submit($action)
	{
		if ($this->file_is_uploaded('file'))
		{
			$name = preg_replace("/[^A-Za-z0-9_\-\.]/", '', trim($this->vars['file:name']));

			if (strlen($name)) {
				$this->move_upl_file('file', PUB_BASE . $this->type . '/' . $name);
			}
		}
	}

	protected function on_remove_submit($action)
	{
		$name = preg_replace("/[^A-Za-z0-9_\-\.]/", '', trim(_POST('name')));

		if (strlen($name) && @file_exists(PUB_BASE . $this->type . '/' . $name)) {
			@unlink(PUB_BASE . $this->type . '/' . $name);
		}
	}

	protected function on_pre_render()
	{
		$files = array();

		if (!file_exists(PUB_BASE . $this->type))
		{
			mkdir(PUB_BASE . $this->type);
			chmod(PUB_BASE . $this->type, 0777);
		}

		if ($dh = opendir(PUB_BASE . $this->type))
		{
			while (($name = readdir($dh)) !== false) {
				if (!is_dir(PUB_BASE . $this->type . '/' . $name)) {
					$files[] = $name;
				}
			}

			closedir($dh);
		}

		sort($files);
		$this->vars['files'] = $files;
	}
}

$page = new BrowserPage();
$page->process();
