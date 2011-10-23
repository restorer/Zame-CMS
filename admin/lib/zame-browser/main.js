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

ZameFileBrowser = function()
{
	var upload_form = null;
	var upload_fld = null;
	var submit_btn = null;

	function on_file_changed()
	{
		if (upload_fld.get_value() != '') {
			submit_btn.set_enabled(true);
		}
	}

	function on_submit_pressed()
	{
		submit_btn.set_enabled(false);
		upload_form.dom().submit();
	}

	return {
		on_load: function()
		{
			upload_form = $new(SForm, 'upload', 'submit', '?type=' + TYPE);
			upload_form.render(S.get('upload-wrap'));

			submit_btn = $new(SButton, 'Upload', on_submit_pressed);
			submit_btn.set_enabled(false);
			submit_btn.render(upload_form.dom());

			S.append_childs(upload_form.dom(), S.build('&nbsp;'));

			upload_fld = $new(SInputFile, 'file');
			upload_fld.render(upload_form.dom());

			S.add_handler(upload_fld.dom_input(), 'change', on_file_changed);
		},

		rm: function(name)
		{
			if (confirm('Are you sure to remove "' + name + '"?'))
			{
				var remove_form = $new(SForm, 'remove', 'submit', '?type=' + TYPE);
				remove_form.render(S.get('upload-wrap'));
				remove_form.set_hidden_value('name', name);
				remove_form.dom().submit();
			}
		},

		sel: function(name)
		{
			var url = PUB_ROOT + TYPE + '/' + name;

			var mt = window.location.search.match(/[?&]CKEditorFuncNum=([^&]+)/i);
			var func_num = (mt ? mt[1] : '');

			window.opener.CKEDITOR.tools.callFunction(func_num, url);
			window.close();
		}
	};
}();

S.add_handler(window, 'load', ZameFileBrowser.on_load);
