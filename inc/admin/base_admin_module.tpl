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

*?>

<form id="main-form" action="<?@h $action_url ?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="action" value="save" id="main-form-action" />
</form>

<script type="text/javascript">

Module = function()
{
	var form = null;

	var form_data = {
		title: '<?@j $data['title'] ?>',
		fields_width: <?= $data['fields_width'] ?>,
		rows: [
			<?!
				$editor_ids = array();
				$editor_themes = array();
			?>
			<? iterate $data['rows'], $row ?>
				<?! if ($row['type'] == 'editor') {
					$editor_ids[] = $row['id'];
					$editor_themes[$row['id']] = (array_key_exists('editor_theme', $row) ? $row['editor_theme'] : 'full');
				} ?>
				<?= $self->get_js_row($row) ?><? if $row_ind < $row_cnt-1 ?>,<? end ?><?= "\n" ?>
			<? end ?>
		],
		buttons: <?= SJson::serialize($data['buttons'] ? $data['buttons'] : array()) ?>
	};

	<? if count($editor_ids) ?>
		function init_editor(el_id, theme)
		{
			CKEDITOR.replace(el_id, {
				toolbar: theme,
				filebrowserBrowseUrl: ROOT + 'admin/lib/zame-browser/index.php?type=file',
				filebrowserImageBrowseUrl: ROOT + 'admin/lib/zame-browser/index.php?type=image',
				filebrowserFlashBrowseUrl: ROOT + 'admin/lib/zame-browser/index.php?type=media',
				filebrowserWindowWidth: 500,
				filebrowserWindowHeight: 400,
				on: {
					instanceReady: function(ev) {
						var dtd = CKEDITOR.dtd;

						for (var e in CKEDITOR.tools.extend({}, dtd.$nonBodyContent, dtd.$block, dtd.$listItem, dtd.$tableContent)) {
							this.dataProcessor.writer.setRules(e, {
								indent: false,
								breakAfterOpen: (e == 'br')
							});
						}
					}
				}
			});
		}
	<? end ?>

	<? if $data['buttons'] || array_key_exists('auto_submit', $data) ?>
		function on_save_pressed(action)
		{
			if (typeof(action)==$undef || action===null) {
				S.get('main-form-action').value = 'save';
			} else {
				S.get('main-form-action').value = action;
			}

			S.get('main-form').submit();
		}
	<? end ?>

	return {
		on_load: function()
		{
			form = $new(SFormView, form_data);
			form.render(S.get('main-form'));

			<? if array_key_exists('info', $data) ?>
				form.set_info('<?@j $data['info'] ?>');
			<? end ?>

			<? $ind = 1 ?>
			<? each $editor_ids as $el_id ?>
				form.get_row('<?@j $el_id ?>').get_element().dom().getElementsByTagName('textarea')[0].id = 'editor-<?= $ind ?>';
				init_editor('editor-<?= $ind ?>', '<?@j $editor_themes[$el_id] ?>');
				<? $ind++ ?>
			<? end ?>

			<? if $data['buttons'] ?>
				<? each $data['buttons'] as $button ?>
					form.get_button('<?@j $button['id'] ?>').set_handler(function(){ on_save_pressed('<?@j $button['id'] ?>') });
				<? end ?>
			<? end ?>

			<? if array_key_exists('errors', $data) && count($data['errors']) ?>
				<? each $data['errors'] as $k=>$v ?>
					form.add_error('<?@j $k ?>', '<?@j $v ?>');
				<? end ?>

				form.show_errors();
			<? end ?>

			<? if array_key_exists('auto_submit', $data) ?>
				<? each $data['auto_submit'] as $el_id ?>
					var tmp = form.get_row('<?@j $el_id ?>').get_element();
					tmp = tmp.dom_el ? tmp.dom_el() : tmp.dom();

					if (tmp.getAttribute('type')=='checkbox' && S.is_ie) {
						S.add_handler(tmp, 'click', function() {
							setTimeout(on_save_pressed, 1);
						});
					} else {
						S.add_handler(tmp, 'change', on_save_pressed);
					}
				<? end ?>
			<? end ?>

			<? if array_key_exists('extra', $data) ?>
				<? $select_node = array_key_exists('select_node', $data['extra']) ? $data['extra']['select_node'] : 0 ?>

				<? if array_key_exists('update_tree', $data['extra']) && $data['extra']['update_tree'] ?>
					<? if $select_node ?>
						window.parent.Main.update_tree(function(){
							window.parent.Main.select_node('<?@j $select_node ?>');
						});
					<? else ?>
						window.parent.Main.update_tree();
					<? end ?>
				<? else ?>
					<? if $select_node ?>
						window.parent.Main.select_node('<?@j $select_node ?>');
					<? end ?>
				<? end ?>

				<? if array_key_exists('update_nav', $data['extra']) && $data['extra']['update_nav'] ?>
					window.parent.Main.update_navigator();
				<? end ?>
			<? end ?>
		}
	};
}();

S.add_handler(window, 'load', Module.on_load);

</script>
