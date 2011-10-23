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

class Loc
{
	public static $lang = null;
	public static $data = null;

	public static function init()
	{
		if (self::$lang === null) {
			self::$lang = conf_get('cms.lang', 'en');
		}

		if (self::$data === null)
		{
			self::$data = array();
			$dir = new DirectoryIterator(CMS . 'locales');

			foreach ($dir as $fileInfo)
			{
				if (!$fileInfo->isDot() && !$fileInfo->isDir() && preg_match('/^(?:[a-zA-Z0-9_\-]+)\.([a-z]+)\.php$/', $fileInfo->getFileName(), $mt))
				{
					if (!array_key_exists($mt[1], self::$data)) {
						self::$data[$mt[1]] = array();
					}

					self::$data[$mt[1]] += require_once($fileInfo->getPathName());
				}
			}
		}
	}

	public static function get_lang()
	{
		self::init();
		return self::$lang;
	}

	public static function get($loc, $params=array())
	{
		self::init();
		$spl = explode('|', $loc, 2);

		$key = $spl[0];
		$def = ((count($spl) > 1) ? $spl[1] : '');

		if (array_key_exists(self::$lang, self::$data) && array_key_exists($key, self::$data[self::$lang])) {
			$res = self::$data[self::$lang][$key];
		} else {
			$res = (strlen($def) ? $def : $key);
		}

		for ($i = 0; $i < count($params); $i++) {
			$res = str_replace('{' . $i . '}', $params[$i], $res);
		}

		return $res;
	}
}
