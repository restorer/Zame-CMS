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

class Request
{
	protected $ctx;

	public $form_posted = '';
	public $form_action = '';
	public $uploaded_files = array();
	public $params = array();

	public function __construct($ctx)
	{
		$this->ctx = $ctx;
		$this->process_post();
	}

	protected function process_post()
	{
		foreach ($_POST as $k=>$v)
		{
			if (substr($k, 0, 3)=='_s_' && substr($k, -7)=='_action') {
				$this->form_posted = substr($k, 3, -7);
				$this->form_action = $v;
			} else {
				$this->params[$k] = $v;
			}
		}

		foreach ($_FILES as $k=>$v)
		{
			$this->uploaded_files[$k] = array();

			if ($v['error'] == UPLOAD_ERR_OK)
			{
				if (is_uploaded_file($v['tmp_name']))
				{
					if ($v['size'] != 0)
					{
						$this->uploaded_files[$k]['stat'] = UPLOAD_ERR_OK;
						$this->uploaded_files[$k]['name'] = preg_replace('@[\\\\/\\*]@', '', $v['name']);
						$this->uploaded_files[$k]['type'] = $v['type'];
						$this->uploaded_files[$k]['size'] = $v['size'];
						$this->uploaded_files[$k]['tmp_name'] = $v['tmp_name'];
					}
					else { $this->uploaded_files[$k]['stat'] = UPLOAD_ERR_NO_FILE; }
				}
				else { $this->uploaded_files[$k]['stat'] = UPLOAD_ERR_PARTIAL; }
			}
			elseif ($v['error'] != UPLOAD_ERR_NO_FILE) {
				$this->uploaded_files[$k]['stat'] = $v['error'];
			}
		}
	}

	public function is_file_uploaded($name)
	{
		if (!array_key_exists($name, $this->uploaded_files)) return false;
		return ($this->uploaded_files[$name]['stat'] == UPLOAD_ERR_OK);
	}

	public function move_upl_file($name, $destination_path)
	{
		if (!$this->is_file_uploaded($name)) return;
		move_uploaded_file($this->uploaded_files[$name]['tmp_name'], $destination_path);
	}

	public function is_post_back($form_name='')
	{
		if (!strlen($form_name)) return strlen($this->form_posted);
		return ($this->form_posted == $form_name);
	}

	public function handle_ajax($obj)
	{
		if (!InPOST('__s_ajax_method')) return false;

		$method = 'aj_' . _POST('__s_ajax_method');

		if (!method_exists($obj, $method))
		{
			echo "fail:method $method not found";
			return true;
		}

		$args_array = SJson::deserialize(_POST('__s_ajax_args'));

		if (!is_array($args_array))
		{
			echo "fail:fail to deserialize arguments";
			return true;
		}

		try
		{
			$res = call_user_func_array(array($obj, $method), $args_array);
		}
		catch (Exception $ex)
		{
			echo 'fail:', $ex->getMessage();
			return true;
		}

		echo 'succ:';
		if (isset($res)) echo SJson::serialize($res);

		return true;
	}

	/*
	public function handle_forms($obj)
	{
		if (!strlen($this->form_posted)) return false;
		$method = 'on_'.$this->form_posted.'_submit';

		if (method_exists($obj, $method))
		{
			call_user_func(array($obj, $method), $this->form_action);
			return true;
		}

		return false;
	}
	*/

	public static function process($uri, $params=array())
	{
		$ctx = new Context();
		// if ($ctx->hook('Request.init')) return;

		$ctx->params = $params;
		$ctx->location = strtolower(substr($uri, strlen(conf('http.root'))));

		$ind = strpos($ctx->location, '?');
		if ($ind !== false) $ctx->location = substr($ctx->location, 0, $ind);

		if (substr($ctx->location, -1) == '/') $ctx->location = substr($ctx->location, 0, -1);

		// if ($ctx->hook('Request.process')) return;
		if (Route::process($ctx->location)) return;

		$ctx->resp->error_404();
	}
}
