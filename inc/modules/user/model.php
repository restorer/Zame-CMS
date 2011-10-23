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

class User extends Node
{
	const Root = 'users';

	public function __construct()
	{
		parent::__construct();
	}

	public function __wakeup()
	{
		$this->child_class = __CLASS__;
	}

	protected function _get_pwd_hash()
	{
		return $this->attr('pwd_hash');
	}

	protected function _set_pwd_hash($pwd_hash)
	{
		$this->set_attr('pwd_hash', $pwd_hash);
	}

	public static function password_to_hash($pwd)
	{
		return md5($pwd . conf('cms.salt', ''));
	}

	public static function is_empty_users()
	{
		$users = Node::get_node('users');

		if (!$users) return true;					// пустить, если модуль не активен
		if (!count($users->childs)) return true;	// пустить если нет ни одного пользователя

		$has_pwd = false;

		foreach ($users->childs as $child) {
			if (strlen($child->attr('pwd_hash'))) {
				$has_pwd = true;
				break;
			}
		}

		return (!$has_pwd);		// пустить если не установлено ни одного пароля
	}
}
