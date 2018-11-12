<?php


class plugin_directive_snippets extends plugin_base
{
	var $module;
	var $form;
	var $tab;
	var $record_id;
	var $formdef;
	var $options;

	public function onShow()
	{
		global $app;

		$listTpl = new tpl;
		$listTpl->newTemplate('templates/web_directive_snippets.htm');

		//* Loading language file
		$lng_file = "lib/lang/".$app->functions->check_language($_SESSION["s"]["language"])."_web_directive_snippets.lng";

		include $lng_file;
		$listTpl->setVar($wb);

		$message = '';
		$error   = '';

		$server_type = $app->getconf->get_server_config($this->form->dataRecord['server_id'], 'web');
		$server_type = $server_type['server_type'];
		$records = $app->db->queryAllRecords("SELECT directive_snippets_id, name FROM directive_snippets WHERE customer_viewable = 'y' AND type = ? ORDER BY name ASC", $server_type);

		for ($i = 0, $c = count($records); $i < $c; $i++)
		{
			$records[$i]['is_selected'] = false;

			if ($this->form->dataRecord['directive_snippets_id'] === $records[$i]['directive_snippets_id'])
				$records[$i]['is_selected'] = true;
		}

		$listTpl->setLoop('records', $records);

		$list_name = 'directive_snippets_list';
		$_SESSION["s"]["list"][$list_name]["parent_id"] = $this->form->id;
		$_SESSION["s"]["list"][$list_name]["parent_name"] = $app->tform->formDef["name"];
		$_SESSION["s"]["list"][$list_name]["parent_tab"] = $_SESSION["s"]["form"]["tab"];
		$_SESSION["s"]["list"][$list_name]["parent_script"] = $app->tform->formDef["action"];
		$_SESSION["s"]["form"]["return_to"] = $list_name;

		return $listTpl->grab();
	}
	
	public function onUpdate()
	{
		global $app, $conf;

		if (isset($this->form->dataRecord['directive_snippets_id']) && $this->form->oldDataRecord['directive_snippets_id'] !== $this->form->dataRecord['directive_snippets_id']) {
			$app->db->query('UPDATE web_domain SET directive_snippets_id = ? WHERE domain_id = ?', $this->form->dataRecord['directive_snippets_id'], $this->form->id);
		}
	}

	public function onInsert()
	{
		global $app, $conf;

		if (isset($this->form->dataRecord['directive_snippets_id'])) {
			$app->db->query('UPDATE web_domain SET directive_snippets_id = ? WHERE domain_id = ?', $this->form->dataRecord['directive_snippets_id'], $this->form->id);
		}
	}

}
?>