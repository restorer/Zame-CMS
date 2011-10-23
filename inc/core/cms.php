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

class Cms
{
	public static function path_start_with($path, $search, $use_wildcards=false)
	{
		if ($use_wildcards)
		{
			$path_spl = explode('/', $path);
			$search_spl = explode('/', $search);

			if (count($search_spl) > count($path_spl)) {
				return false;
			}

			for ($i = 0; $i < count($search_spl); $i++) {
				if ($search_spl[$i]!='*' && $search_spl[$i]!=$path_spl[$i]) {
					return false;
				}
			}

			return true;
		}

		return (($path == $search) || (strpos($path, $search . '/') === 0));
	}

	public static function path_parts_count($path)
	{
		return count(explode('/', $path));
	}

	public static function slice_path($path, $count)
	{
		return implode('/', array_slice(explode('/', $path), 0, $count));
	}

	public static function capitalize_words($str, $join='')
	{
		$spl = preg_split('/[^A-Za-z0-9]/', $str);

		$res = array();
		foreach ($spl as $part) $res[] = capitalize($part);

		return join($join, $res);
	}

	public static function split_dots($str, $max)
	{
		if (strlen($str) <= $max) return $str;
		return (substr($str, 0, $max - 3) . '...');
	}

	public static function get_extension($name)
	{
		$ind = strrpos($name, '.');
		return ($ind === false ? '' : substr($name, $ind + 1));
	}

	public static function render_layout($ctx, $content, $params)
	{
		$tpl = new STemplate();
		$tpl->vars['__content__'] = $content;

		if ($params === null) {
			$params = array('path' => '');
		}

		if (array_key_exists('controller', $ctx->params)) {
			$ctx->params['controller']->process($ctx, $params, $tpl->vars);
		}

		$path = BASE . 'layouts/' . $ctx->layout . '.php';

		if (@file_exists($path))
		{
			require_once($path);
			$class_name = self::capitalize_words($ctx->layout) . 'Layout';
			$layout_inst = new $class_name;

			$layout_inst->process($ctx, $params, $tpl->vars);
		}

		return $tpl->process(BASE . 'layouts/' . $ctx->layout . '.tpl');
	}

	public static function resize_thumb($src, $size, $width, $height)
	{
		$src_x = 0;
		$src_y = 0;
		$src_width = $size[0];
		$src_height = $size[1];

		if (($src_width > $width) || ($src_height > $height))
		{
			$ratio = $src_width / $width;
			if ($height * $ratio > $src_height) $ratio = $src_height / $height;

			$src_width = round($width * $ratio);
			$src_height = round($height * $ratio);

			$src_x = round(($size[0] - $src_width) / 2);
			$src_y = round(($size[1] - $src_height) / 2);
		}
		else
		{
			$src_width = $size[0];
			$src_height = $size[1];
		}

		$dst = imageCreateTrueColor($width, $height);
		imageCopyResampled($dst, $src, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height);

		return $dst;
	}

	public static function resize_full_image($src, $size, $width, $height)
	{
		if (($size[0] > $width) || ($size[1] > $height))
		{
			$ratio = (($size[0] > $size[1]) ? ($size[0] / $width) : ($size[1] / $height));
			$dst_width = $size[0] / $ratio;
			$dst_height = $size[1] / $ratio;
		}
		else
		{
			$dst_width = $size[0];
			$dst_height = $size[1];
		}

		$dst = imageCreateTrueColor($dst_width, $dst_height);
		imageCopyResampled($dst, $src, 0, 0, 0, 0, $dst_width, $dst_height, $size[0], $size[1]);

		return $dst;
	}

	public static function ensure_image($page, $name, $path)
	{
		if (@file_exists($path)) {
			@unlink($path);
		}

		$page->move_upl_file($name, $path);
		chmod($path, 0555 + 0111);

		$size = getImageSize($path);

		if (!$size || $size[0]==0 || $size[1]==0 || !($size[2]==1 || $size[2]==2 || $size[2]==3))
		{
			@unlink($path);
			return null;
		}

		return $size;
	}

	public static function load_image($page, $name, $tmp_path)
	{
		$size = self::ensure_image($page, $name, $tmp_path);

		if (!$size) {
			return null;
		}

		switch ($size[2])
		{
			case 1:
				$img = imageCreateFromGIF($tmp_path);
				break;

			case 2:
				$img = imageCreateFromJPEG($tmp_path);
				break;

			case 3:
				$img = imageCreateFromPNG($tmp_path);
				break;
		}

		@unlink($tmp_path);
		return array($img, $size);
	}

	public static function node_name_is_valid($name)
	{
		return preg_match("/^[a-z0-9\-]+$/", $name);
	}

	public static function title_to_name($title)
	{
		$name = preg_replace("/[^a-z0-9\-]/", '-', strtolower(tran($title)));
		$name = preg_replace("/^\-\-*/", '', $name);
		$name = preg_replace("/\-\-*$/", '', $name);

		for (;;)
		{
			$prev = $name;
			$name = str_replace('--', '-', $name);
			if ($name == $prev) break;
		}

		return $name;
	}
}
