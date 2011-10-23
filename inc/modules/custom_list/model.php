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

require_once(CMS . 'modules/custom_list_item/model.php');

class CustomList extends Node
{
	const Root = 'site/custom-lists';

	public function __construct()
	{
		parent::__construct();
	}

	public function __wakeup()
	{
		$this->child_class = 'CustomListItem';

		$this->attr_accessors(array(
			'fields' => array('type' => 'keyvalue', 'trim' => true, 'label' => Loc::get('custom-list/fields')),
		));
	}

	public function get_avail_fields()
	{
		return ($this->fields == '' ? array() : SJson::deserialize($this->fields));
	}

	public static function get_list($name)
	{
		return Node::get_node_childs(self::Root . "/{$name}", __CLASS__);
	}
}
