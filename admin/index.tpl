<?*

MIT License (http://www.opensource.org/licenses/mit-license.php)

Copyright (c) 2007, Slava Tretyak (aka restorer)
Zame Software Development (http://zame-dev.org)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

Zame CMS

*?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=8" />
	<title><?@h conf('sitename') ?></title>
	<link rel="stylesheet" href="<?= ROOT ?>admin/css/main.css" />
	<link rel="stylesheet" href="<?= ROOT ?>s-js/s.css" />
	<link rel="stylesheet" href="<?= ROOT ?>admin/lib/jquery-tree/tree.css" />
	<script type="text/javascript">
		ROOT = '<?@j ROOT ?>';
	</script>
	<script type="text/javascript" src="<?= ROOT ?>s-js/s.js"></script>
	<script type="text/javascript">
		<? $lang = Loc::get_lang() ?>
		<? each Loc::$data[$lang] as $key => $value ?>
			SL.set('<?@j $key ?>','<?@j $value ?>','<?@j $lang ?>');
		<? end ?>
		SL.set_locale('<?@j $lang ?>');
	</script>
	<script type="text/javascript" src="<?= ROOT ?>s-js/modules/interface.js"></script>
	<script type="text/javascript" src="<?= ROOT ?>admin/js/jquery.js"></script>
	<script type="text/javascript" src="<?= ROOT ?>admin/lib/jquery-tree/tree.js"></script>
	<script type="text/javascript" src="<?= ROOT ?>admin/js/main.js"></script>
</head>
<body>
	<table id="wrap" class="wrap" cellspacing="0">
		<tr valign="top">
			<td colspan="2" id="top-menu"></td>
		</tr>
		<tr valign="top">
			<td class="navigation" id="navigation">
				<div id="nodes-wrap" class="nodes-wrap">
					<div id="nodes-cont">
						<div id="nodes">
							<?= $nodes_html ?>
						</div>
						<div class="nodes-last"></div>
					</div>
					<div id="nav-cont" style="display:none;">
						<a id="nav-back" href="javascript:void(0)">&laquo; <?@h Loc::get('cms/admin/back') ?></a>
						<div id="nav-title"></div>
						<div id="nav"></div>
					</div>
				</div>
			</td>
			<td class="content" id="content">
				<iframe id="module-editor" src="about:blank" frameborder="no"></iframe>
			</td>
		</tr>
	</table>
</body>
</html>