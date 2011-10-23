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

class PageModule
{
	public $ctx = null;
	protected $already_processed = array();
	protected $render_layout = true;
	protected $in_404 = false;
	public $layout_params = null;
	public $avail_macro = array();

	public function __construct($ctx)
	{
		$this->ctx = $ctx;

		Route::add('', array($this, 'process_index'));
		Route::add('::page', array($this, 'process_page'));

		$this->ctx->add_hook('Response.404', array($this, 'process_404'));

		$this->avail_macro['root'] = array($this, 'macro_root');
		$this->avail_macro['location'] = array($this, 'macro_location');
		$this->avail_macro['conf'] = array($this, 'macro_conf');
	}

	public function macro_root($params)
	{
		return ROOT;
	}

	public function macro_location($params)
	{
		return $this->ctx->location;
	}

	public function macro_conf($params)
	{
		return (count($params) ? conf_get(first_value($params)) : '');
	}

	public function render_macro($mt)
	{
		$spl = explode('|', trim($mt[1]));
		$macro = trim($spl[0]);
		$params = array();

		for ($i = 1; $i < count($spl); $i++)
		{
			$param_spl = explode('=', $spl[$i], 2);

			if (count($param_spl) == 2) {
				$params[trim($param_spl[0])] = trim($param_spl[1]);
			} else {
				$params[] = trim($param_spl[0]);
			}
		}

		return (array_key_exists($macro, $this->avail_macro) ? call_user_func($this->avail_macro[$macro], $params) : $mt[0]);
	}

	public function render_content($page)
	{
		return preg_replace_callback("/\[\[:(.+?):\]\]/", array($this, 'render_macro'), $page->content);
	}

	public function render_page($subpath)
	{
		$subpath = trim($subpath);
		if (substr($subpath, -1) == '/') $subpath = substr($subpath, 0, -1);

		if (array_key_exists($subpath, $this->already_processed)) return "[$subpath : Internal redirect loop]";
		$this->already_processed[$subpath] = true;

		$node = Node::get_node(Page::Root . '/' . $subpath);

		if (!$node)
		{
			if ($this->layout_params === null)
			{
				$this->ctx->resp->error_404();
				exit();
			}

			return "[$subpath : Not found]";
		}

		$page = dynamic_cast($node, 'Page');

		switch ($page->page_type)
		{
			case 'redirect':
				$this->render_layout = false;
				header('Location: ' . $page->redirect_url);
				return ' ';

			case 'transfer':
				return $this->render_page($page->transfer_path);
		}

		if ($this->layout_params === null)
		{
			$sub_menu = array();
			$top_page = (Cms::path_parts_count($page->model_path)==2 ? $page : Node::get_by_model_path('Page', Cms::slice_path($page->model_path, 2)));

			if ($top_page != null) {
				foreach ($top_page->childs as $child) {
					$sub_menu[$child->model_path] = $child->title;
				}
			}

			$this->layout_params = array(
				'page' => $page,
				'path' => $page->model_path,
				'sub_menu' => $sub_menu,
			);
		}

		return $this->render_content($page);
	}

	public function process_page($params)
	{
		$cont = $this->render_page($params['page']);

		if ($this->render_layout)
		{
			$cont = str_replace('<!-- pagebreak --></p>', '</p><div class="clear"></div>', $cont);
			$cont = str_replace('<!-- pagebreak -->', '<div class="clear"></div>', $cont);

			$cont = Cms::render_layout($this->ctx, $cont, $this->layout_params);
		}

		$this->ctx->resp->write($cont);
	}

	public function process_index($params)
	{
		$pages = Node::get_node(Page::Root);

		if ($pages===null || !count($pages->childs))
		{
			$this->ctx->resp->error_404();
			return;
		}

		$this->process_page(array('page' => $pages->childs[0]->name));
	}

	public function process_404()
	{
		if ($this->in_404)
		{
			$this->ctx->resp->error();
		}
		else
		{
			$this->in_404 = true;
			$page = Node::get_by_model_path('Page', '404');

			if ($page != null)
			{
				$res = $this->render_content($page);

				if ($this->render_layout)
				{
					$this->layout_params = array(
						'page' => $page,
						'path' => '404',
						'sub_menu' => array(),
					);

					$res = Cms::render_layout($this->ctx, $res, $this->layout_params);
				}

				$this->ctx->resp->write($res);
			}
			else
			{
				$this->ctx->resp->error();
			}
		}

		return true;
	}
}
