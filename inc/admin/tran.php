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

 $_tran = array(
	'Й' => 'J',
	'Ц' => 'C',
	'У' => 'U',
	'К' => 'K',
	'Е' => 'E',
	'Н' => 'N',
	'Г' => 'G',
	'Ш' => 'Sh',
	'Щ' => 'Shch',
	'З' => 'Z',
	'Х' => 'H',
	'Ъ' => '',
	'Ф' => 'F',
	'Ы' => 'Y',
	'В' => 'V',
	'А' => 'A',
	'П' => 'P',
	'Р' => 'R',
	'О' => 'O',
	'Л' => 'L',
	'Д' => 'D',
	'Ж' => 'Zh',
	'Э' => 'Je',
	'Я' => 'Ja',
	'Ч' => 'Ch',
	'С' => 'S',
	'М' => 'M',
	'И' => 'I',
	'Т' => 'T',
	'Ь' => '',
	'Б' => 'B',
	'Ю' => 'Ju',
	'Ё' => 'Jo',
	'й' => 'j',
	'ц' => 'c',
	'у' => 'u',
	'к' => 'k',
	'е' => 'e',
	'н' => 'n',
	'г' => 'g',
	'ш' => 'sh',
	'щ' => 'shch',
	'з' => 'z',
	'х' => 'h',
	'ъ' => '',
	'ф' => 'f',
	'ы' => 'y',
	'в' => 'v',
	'а' => 'a',
	'п' => 'p',
	'р' => 'r',
	'о' => 'o',
	'л' => 'l',
	'д' => 'd',
	'ж' => 'zh',
	'э' => 'je',
	'я' => 'ja',
	'ч' => 'ch',
	'с' => 's',
	'м' => 'm',
	'и' => 'i',
	'т' => 't',
	'ь' => '',
	'б' => 'b',
	'ю' => 'ju',
	'ё' => 'jo'
);

function tran($str)
{
	global $_tran, $__tran;

	mb_internal_encoding('UTF-8');
	$res = '';

	for ($i = 0; $i < mb_strlen($str); $i++)
	{
		$ch = mb_substr($str, $i, 1);
		$res .= (array_key_exists($ch, $_tran) ? $_tran[$ch] : $ch);
	}

	return $res;
}
