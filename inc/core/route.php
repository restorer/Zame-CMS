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

class Route
{
	static $direct_map = array();
	static $regex_map = array();

	static function exec_handler($handler, $params=array())
	{
		if (is_array($handler))
		{
			$result = call_user_func($handler, $params);
			return ($result === false ? false : true);
		}

		throw new Exception('Unknown handler type');
	}

	public static function process($location)
	{
		if (array_key_exists($location, self::$direct_map)) {
			return self::exec_handler(self::$direct_map[$location]);
		}

		foreach (self::$regex_map as $item)
		{
			if (preg_match($item['regex'], $location, $mt))
			{
				$params = array();
				foreach ($item['params'] as $name=>$ind) $params[$name] = $mt[$ind];

				$result = self::exec_handler($item['handler'], $params);

				if ($result !== false) {
					return $result;
				}
			}
		}

		return false;
	}

	public static function add($uri, $handler)
	{
		if (strpos($uri, ':') === false)
		{
			if (!array_key_exists($uri, self::$direct_map)) {
				self::$direct_map[$uri] = $handler;
			}

			return;
		}

		$spl = explode('/', $uri);
		$res = array();
		$params = array();

		foreach ($spl as $part)
		{
			if (!strlen($part)) continue;

			if ($part{0} == ':')
			{
				$part = substr($part, 1);
				if (!strlen($part)) continue;

				if ($part{0} == ':')
				{
					$part = substr($part, 1);
					if (!strlen($part)) continue;

					if ($part{0} == ':')
					{
						$part = substr($part, 1);
						if (!strlen($part)) continue;

						$res[] = '(.*)';
						$params[$part] = count($params) + 1;
						break;
					}
					else
					{
						$res[] = '(.+)';
						$params[$part] = count($params) + 1;
						break;
					}
				}
				else
				{
					$ind = strpos($part, '.');

					if ($ind === false)
					{
						$res[] = '([^/]+)';
						$params[$part] = count($params) + 1;
					}
					else
					{
						$res[] = '([^/\.]+)\.' . preg_quote(substr($part, $ind + 1), '@');
						$params[substr($part, 0, $ind)] = count($params) + 1;
					}
				}
			}
			else
			{
				$res[] = preg_quote($part, '@');
			}
		}

		self::$regex_map[] = array('regex' => '@^' . implode('/', $res) . '$@', 'handler' => $handler, 'params' => $params);
	}
}
