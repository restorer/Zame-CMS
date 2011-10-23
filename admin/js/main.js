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

Main = function()
{
	var h_resize = 0;

	function compare(a, b)
	{
		if (typeof(a)!='number' || typeof(b)!='number')
		{
			a = String(a).toLowerCase();
			b = String(b).toLowerCase();

			var ma = a.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
			var mb = b.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);

			if (ma && mb)
			{
				a = Number(ma[3]).format('02d') + Number(ma[1]).format('02d') + Number(ma[2]).format('02d');
				b = Number(mb[3]).format('02d') + Number(mb[1]).format('02d') + Number(mb[2]).format('02d');
			}
		}

		return (a == b ? 0 : (a > b ? 1 : -1));
	}

	return {
		loaded_ids: [],
		loaded_ids_hash: {},
		nav_node_id: 0,
		nav_module: '',
		nav: null,

		on_resize_ie: function()
		{
			if (h_resize > 0) clearTimeout(h_resize);
			h_resize = setTimeout(Main.on_resize, 10);
		},

		on_resize: function()
		{
			var st_wrap = S.get('wrap').style;
			var st_navigation = S.get('navigation').style;
			var st_content = S.get('content').style;
			var st_nodes_wrap = S.get('nodes-wrap').style;

			st_wrap.height = '50px';
			st_navigation.height = '24px';
			st_content.height = '24px';
			st_nodes_wrap.height = '24px';

			var hgt = S.info.page.height() - 1;
			var hgt_cnt = Math.max(1, hgt - 26);

			st_wrap.height = hgt + 'px';
			st_navigation.height = hgt_cnt + 'px';
			st_content.height = hgt_cnt + 'px';
			st_nodes_wrap.height = hgt_cnt + 'px';

			if (S.is_ie) {
				h_resize = 0;
			}
		},

		select_node: function(node_id)
		{
			var tree = $.tree_reference('nodes');
			if (tree.locked) return;

			var node = S.get('n_' + node_id);
			if (!node) return;

			node.setAttribute('__disable_load_once', 'yes');
			node.setAttribute('__disable_editor_once', 'yes');
			tree.select_branch('#n_' + node_id);
		},

		update_tree: function(callback)
		{
			if (typeof(callback)=='undefined') callback = null;

			var tree = $.tree_reference('nodes');
			if (tree.locked) return;

			tree.lock(true);

			if (tree.selected)
			{
				var node_id = tree.selected.attr('id').substr(2);

				if (typeof(Main.loaded_ids_hash['_' + node_id]) == $undef)
				{
					Main.loaded_ids_hash['_' + node_id] = true;
					Main.loaded_ids.push(node_id);
				}
			}

			S.call('index.php|get_nodes', {
				args: [Main.loaded_ids],
				succ: function(res)
				{
					var opened = [];
					$('#nodes li.open').each(function(){ opened.push(this.id); });

					var selected = (tree.selected ? tree.selected.attr('id') : null);

					if (tree.inp) tree.inp.val('').blur();
					tree.container.find('.dragged').removeClass('dragged').end().find('div.context').remove();

					tree.opened = false;
					tree.settings.opened = false;
					tree.selected = false;

					tree.container.html(res);
					tree.lock(false);

					tree.refresh();
					tree.context_menu();

					for (var i = 0; i < opened.length; i++) {
						if (S.get(opened[i])) {
							tree.open_branch(S.get(opened[i]));
						}
					}

					if (selected)
					{
						var prev_callback = tree.settings.callback;

						tree.settings.callback = {
							beforechange: function(node, sender) {},
							onselect: function(node, sender) {},
							ondeselect: function(node, sender) {},
							onchange: function(node, sender) {},
							error: function(text, sender) {}
						};

						tree.select_branch('#' + selected);
						tree.settings.callback = prev_callback;
					}

					if (callback) {
						callback();
					}
				},
				fail: function(err)
				{
					S.error(err);
					tree.lock(false);
				}
			});
		},

		update_navigator: function(callback, mask)
		{
			if (mask) {
				S.mask();
			}

			S.call('index.php|get_nav_items', {
				args: [Main.nav_node_id],
				succ: function(res)
				{
					Main.nav_module = res.module;
					var active_id = (Main.nav ? Main.nav.get_active_id() : '');

					S.get('nav').innerHTML = '';
					Main.nav = null;

					S.style('nodes-cont').display = 'none';
					S.style('nav-cont').display = '';

					var title = ((typeof(res.title)!=$undef && res.title) ? res.title : '');
					S.get('nav-title').innerHTML = title;
					S.style('nav-title').display = (title ? '' : 'none');

					Main.nav = $new(SNavigator, res.header, Main.on_nav_click, 450);
					Main.nav.append_rows(res.data);

					Main.nav.set_sort_handler(function(sort) {
						var data = Main.nav.get_rows();
						var selected = Main.nav.get_selected_ids();
						var current = Main.nav.get_active_id();

						Main.sort_dataset(data, sort);
						Main.nav.clear();
						Main.nav.append_rows(data);

						Main.nav.set_active_id(current);
						Main.nav.set_selected_ids(selected);
					});

					Main.nav.render(S.get('nav'));

					if (active_id) {
						Main.nav.set_active_id(active_id);
					}

					if (callback) {
						callback(true);
					}
				},
				fail: function(err)
				{
					S.error(err);

					if (callback) {
						callback(false);
					}
				},
				last: function()
				{
					if (mask) {
						S.unmask();
					}
				}
			});
		},

		sort_dataset: function(data, sort) {
			data.sort(function(a, b) {
				return (compare(a[sort.field], b[sort.field]) * (sort.dir ? 1 : -1));
			});
		},

		on_nav_click: function(id)
		{
			Main.nav.set_active_id(id);
			Main.show_editor({'module': Main.nav_module, 'item_id': id});
		},

		show_editor: function(opts)
		{
			if (opts == null)
			{
				S.get('module-editor').src = 'about:blank';
				return;
			}

			var params = [];
			for (var k in opts) params.push(escape(k) + '=' + escape(opts[k]));

			S.get('module-editor').src = ROOT + 'admin/module.php?' + params.join('&');
		}
	};
}();

$(function()
{
	var add_item_btn = null;
	var rm_item_btn = null;

	function get_node_id(node)
	{
		return node.id.substr(2);
	}

	function get_node_value(node)
	{
		return $(node).find('a:first').get(0).innerHTML;
	}

	function set_node_value(node, value)
	{
		$(node).find('a:first').get(0).innerHTML = value;
	}

	/*
	function get_new_node_params(node)
	{
		while (node && node.id!='nodes')
		{
			if (node.getAttribute('new_node'))
			{
				var spl = node.getAttribute('new_node').split(':', 2);
				return { 'class':spl[0], 'name':spl[1] };
			}

			node = node.parentNode;
		}

		return 'Новый элемент';
	}
	*/

	function show_navigator(sender, node_id)
	{
		sender.lock(true);
		Main.nav_node_id = node_id;

		Main.update_navigator(function() {
			sender.lock(false);
		});
	}

	function on_nav_back_click()
	{
		S.style('nodes-cont').display = '';
		S.style('nav-cont').display = 'none';

		S.get('nav').innerHTML = '';
		Main.nav = null;

		Main.show_editor(null);
	}

	function process_select(node, sender)
	{
		var node_id = get_node_id(node);

		sender.open_branch(node);

		add_item_btn.set_enabled(sender.check('creatable', node));
		rm_item_btn.set_enabled(sender.check('deletable', node));

		if (node.getAttribute('__disable_editor_once') == 'yes') {
			node.setAttribute('__disable_editor_once', '');
		} else {
			Main.show_editor({'node_id': node_id, 'nav_opened': (node.getAttribute('sub_type') == 'nav') ? '1' : '0'});
		}

		if (node.getAttribute('sub_type') == 'nav') {
			show_navigator(sender, node_id);
		}
	}

	function is_loaded(node)
	{
		if (node.id == '') return true;
		var node_id = get_node_id(node);

		return (!((typeof(Main.loaded_ids_hash['_'+node_id]) == $undef) &&
			(!S.has_class(node, 'leaf')) &&
			($(node).find('ul li').length == 0)));
	}

	function ensure_loaded(node, sender, callback)
	{
		if (node.id == '') return;

		if (node.getAttribute('__disable_load_once') == 'yes')
		{
			node.setAttribute('__disable_load_once', '');
			if (callback) callback();
			return;
		}

		if (S.send_in_process())
		{
			setTimeout(function(){ ensure_loaded(node, sender, callback); }, 1);
			return;
		}

		if (typeof(callback) == $undef) callback = null;
		var node_id = get_node_id(node);

		if ((typeof(Main.loaded_ids_hash['_'+node_id]) == $undef) &&
			(!S.has_class(node, 'leaf')) &&
			($(node).find('ul li').length == 0))
		{
			sender.lock(true);

			S.call('index.php|get_childs', {
				args: [node_id],
				succ: function(res)
				{
					var ul = $(node).find('ul');
					$(res).insertBefore(ul);
					ul.remove();

					Main.loaded_ids_hash['_'+node_id] = true;
					Main.loaded_ids.push(node_id);

					if (sender.inp) sender.inp.val('').blur();

					sender.container.find('.dragged').removeClass('dragged').end().find('div.context').remove();
					sender.lock(false);

					sender.refresh();
					sender.context_menu();

					if (callback) callback();
				},
				fail: function(err)
				{
					S.error(err);
					sender.lock(false);
				}
			});
		}
		else
		{
			if (callback) callback();
		}
	}

	$('#nodes').tree({
		ui: {
			theme_path: ROOT + 'admin/lib/jquery-tree/themes/',
			dots: false,
			context: false
		},

		rules: {
			creatable:	['c', 'cr', 'cd', 'cg', 'crd', 'crg', 'cdg', 'crdg'],
			renameable: ['r', 'cr', 'rd', 'rg', 'crd', 'crg', 'rdg', 'crdg'],
			deletable:	['d', 'cd', 'rd', 'dg', 'crd', 'cdg', 'rdg', 'crdg'],
			draggable:	['g', 'cg', 'rg', 'dg', 'crg', 'cdg', 'rdg', 'crdg'],
			dragrules:	[
				'* inside c', '* inside cr', '* inside cd', '* inside cg', '* inside crd', '* inside crg', '* inside cdg', '* inside crdg',
				'* * g', '* * cg', '* * rg', '* * dg', '* * crg', '* * cdg', '* * rdg', '* * crdg'
			]
		},

		callback: {
			onselect: function(node, sender)
			{
				ensure_loaded(node, sender, function(){ process_select(node, sender); });
			},

			beforeopen: function(node, sender)
			{
				if (is_loaded(node)) return true;
				ensure_loaded(node, sender, function(){ sender.open_branch(node); });
			},

			onmove: function(node, ref_node, type, sender, rollback)
			{
				sender.lock(true);

				S.call('index.php|move_node', {
					args: [get_node_id(node), get_node_id(ref_node), type],
					fail: function(res)
					{
						$.tree_rollback(rollback);
						if (res.indexOf('@') != 0) alert(res);
					},
					last: function()
					{
						sender.lock(false);
					}
				});
			}
		}
	});

	function open_root_nodes()
	{
		var tree = $.tree_reference('nodes');

		$('#nodes>ul>li:first').each(function(){
			tree.open_branch(this);
		});
	}

	function on_logout_click()
	{
		S.call('index.php|logout', {
			succ: function(res)
			{
				window.location = window.location;
			}
		});
	}

	function on_add_item_pressed()
	{
		var tree = $.tree_reference('nodes');
		if (!tree.selected) return;

		var node = tree.selected.get(0);
		if (!tree.check('creatable', node)) return;

		Main.show_editor({'parent_id': get_node_id(node)});
	}

	function on_rm_item_pressed()
	{
		var tree = $.tree_reference('nodes');
		if (!tree.selected) return;

		$.each(tree.selected, function() {
			if (!tree.check('deletable', this)) {
				return;
			}
		});

		if (!confirm(SL.get('cms/admin/are-you-sure'))) return;

		tree.lock(true);
		add_item_btn.set_enabled(false);
		rm_item_btn.set_enabled(false);

		var node = tree.selected.get(0);

		S.call('index.php|delete_node', {
			args: [get_node_id(node)],
			succ: function(res)
			{
				Main.show_editor(null);
				tree.lock(false);
				tree.remove(node);
			},
			fail: function(res)
			{
				tree.lock(false);
				if (res.indexOf('@') != 0) alert(res);
			}
		});
	}

	(S.is_ie ? Main.on_resize_ie() : Main.on_resize());

	add_item_btn = $new(SToolbarButton, SL.get('cms/admin/add'), on_add_item_pressed, null, false);
	rm_item_btn = $new(SToolbarButton, SL.get('cms/admin/delete'), on_rm_item_pressed, null, false);
	var logout_btn = $new(SToolbarButton, SL.get('cms/admin/logout'), on_logout_click);

	var toolbar = $new(SToolbar, [add_item_btn, rm_item_btn], [logout_btn]);
	toolbar.render(S.get('top-menu'));

	open_root_nodes();
	S.add_handler(S.get('nav-back'), 'click', on_nav_back_click);
});

S.add_handler(window, 'resize', (S.is_ie ? Main.on_resize_ie : Main.on_resize));
