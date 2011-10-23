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

class Response
{
	protected $ctx;

	public function __construct($ctx)
	{
		$this->ctx = $ctx;
	}

	public function error($message='')
	{
		if ($message == '') {
			$message = conf('cms.sys-error', 'System error');
		}

		$this->write('<html><head><link rel="stylesheet" type="text/css" href="'
			. conf('http.root') . 'css/err.css" /><title>'
			. strip_tags($message)
			. '</title></head><body><div class="sys-error">'
			. $message . '</div></body></html>'
		);
	}

	public function error_404($message='')
	{
		if ($this->ctx->hook('Response.404')) return;
		$this->error($message == '' ? conf('cms.sys-error-404', 'Page not found') : $message);
	}

	public function error_500($message='')
	{
		if ($this->ctx->hook('Response.500')) return;
		$this->error($message);
	}

	public function header($name, $value)
	{
		header("{$name}: {$value}");
	}

	public function write($content)
	{
		echo $content;
	}
}
