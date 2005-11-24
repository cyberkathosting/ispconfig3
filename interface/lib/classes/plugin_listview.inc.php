<?php

class plugin_listview extends plugin_base {
	
	var $module;
	var $form;
	var $tab;
	var $record_id;
	var $formdef;
	var $options;
	
	function onShow() {
		
		global $app;
		
		$app->uses('listform');
		$app->listform->loadListDef($this->options["listdef"]);
		
		//$app->listform->SQLExtWhere = "type = 'alias'";
		
		$listTpl = new tpl;
		$listTpl->newTemplate('templates/'.$app->listform->listDef["name"].'_list.htm');
		
		// Changing some of the list values to reflect that the list is called within a tform page
		$app->listform->listDef["file"] = $app->tform->formDef["action"];
		$app->listform->listDef["page_params"] = "&id=".$app->tform_actions->id."&next_tab=".$_SESSION["s"]["form"]["tab"];
		
		
		// Generate the SQL for searching
		if($app->listform->listDef["auth"] != 'no') {
			if($_SESSION["s"]["user"]["typ"] == "admin") {
				$sql_where = "";
			} else {
				$sql_where = $app->tform->getAuthSQL('r')." and";
			}
		}
		
		if($this->options["sqlextwhere"] != '') {
			$sql_where .= " ".$this->options["sqlextwhere"]." and";
		}

		$sql_where = $app->listform->getSearchSQL($sql_where);
		$listTpl->setVar($app->listform->searchValues);
		
		// Generate SQL for paging
		$limit_sql = $app->listform->getPagingSQL($sql_where);
		$listTpl->setVar("paging",$app->listform->pagingHTML);

		// Get the data
		$records = $app->db->queryAllRecords("SELECT * FROM ".$app->listform->listDef["table"]." WHERE $sql_where $limit_sql");
		
		$bgcolor = "#FFFFFF";
		if(is_array($records)) {
			$idx_key = $app->listform->listDef["table_idx"]; 
			foreach($records as $rec) {
	
				$rec = $app->listform->decode($rec);

				// Change of color
				$bgcolor = ($bgcolor == "#FFFFFF")?"#EEEEEE":"#FFFFFF";
				$rec["bgcolor"] = $bgcolor;
		
				// The variable "id" contains always the index field
				$rec["id"] = $rec[$idx_key];

				$records_new[] = $rec;
			}
		}

		$listTpl->setLoop('records',$records_new);

		// Loading language field
		$lng_file = "lib/lang/".$_SESSION["s"]["language"]."_".$app->listform->listDef['name']."_list.lng";
		include($lng_file);
		$listTpl->setVar($wb);
		
		// Setting Returnto information in the session
		$list_name = $app->listform->listDef["name"];
		$_SESSION["s"]["list"][$list_name]["parent_id"] = $app->tform_actions->id;
		$_SESSION["s"]["list"][$list_name]["parent_tab"] = $_SESSION["s"]["form"]["tab"];
		$_SESSION["s"]["list"][$list_name]["parent_script"] = $app->tform->formDef["action"];
		$_SESSION["s"]["form"]["return_to"] = $list_name;

		return $listTpl->grab();
		
	}
}

?>