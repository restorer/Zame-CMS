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

class Settings extends Node
{
	const Root = 'settings';

	private static $_loaded_settings = null;

	public function __construct()
	{
		parent::__construct();
	}

	public function __wakeup()
	{
		$this->child_class = __CLASS__;

		$this->attr_accessors(array(
			'data' => array('type' => 'keyvalue', 'trim' => true, 'label' => Loc::get('settings/root-title')),
		));
	}

	public static function get_setting($name, $def='')
	{
		if (!self::$_loaded_settings)
		{
			$settings_node = Node::get_node(self::Root, __CLASS__);

			if (!$settings_node) {
				self::$_loaded_settings = array();
			} else {
				self::$_loaded_settings = SJson::deserialize($settings_node->data);
			}
		}

		return (array_key_exists($name, self::$_loaded_settings) ? self::$_loaded_settings[$name] : $def);
	}
}
