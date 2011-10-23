<?php

require_once(dirname(__FILE__) . '/model.php');

class CustomListItemAdminModule extends BaseAdminModule
{
	public function get_new_node_name($node)
	{
		return Loc::get('custom-list/new-list-item');
	}

	public function can_has_childs($node)
	{
		return false;
	}

	public function can_put($parent_path, $parent_node)
	{
		return ($parent_node->type == 'custom_list' ? '' : false);
	}

	public function render_editor($node)
	{
		$node = dynamic_cast($node, 'CustomListItem');
		$avail_fields = $node->parent_node->get_avail_fields();
		$data = array();

		if (_POST('action') == 'save') {
			foreach ($avail_fields as $name => $label) {
				$node->set_attr("f_{$name}", _POST("f_{$name}"));
			}

			$this->def_save_node($data, $node);
		}

		$this->fill_def_form_data($data, $node, null, BaseAdminModule::PATH_TYPE_NAME);

		foreach ($avail_fields as $name => $label)
		{
			$data['rows'][] = array(
				'id' => "f_{$name}",
				'label' => $label,
				'type' => 'textarea',
				'rows' => 4,
				'value' => $node->attr("f_{$name}"),
			);
		}

		return $this->render_form($data);
	}
}
