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

require_once(dirname(__FILE__) . '/../../s/s.php');
require_once(BASE . 'inc/core/core.php');
require_once(CMS . 'core/base_command.php');

if (!isset($argc) || $argc < 2) {
    echo "Usage: php run.php <command> [command-arguments]\n";
    exit();
}

$command_path = CMS . "modules/{$argv[1]}/command.php";

if (!is_readable($command_path)) {
    echo "{$command_path} not found\n";
    exit();
}

require_once($command_path);
$class_name = Cms::capitalize_words($argv[1]) . 'Command';

$command = new $class_name();

if (! $command instanceof BaseCommand) {
    echo "{$class_name} does not extend BaseCommand\n";
    exit();
}

echo "[+ {$class_name}]\n";
$command->run(array_slice($argv, 2));
echo "[- {$class_name}]\n";
