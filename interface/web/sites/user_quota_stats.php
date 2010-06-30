<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/user_quota_stats.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->load('listform_actions');

$tmp_rec = $app->db->queryOneRecord("SELECT data from monitor_data WHERE type = 'harddisk_quota' ORDER BY created DESC");
$monitor_data = unserialize($app->db->unquote($tmp_rec['data']));

class list_action extends listform_actions {
	
	function prepareDataRow($rec)
    {
		global $app,$monitor_data;
		
		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;
		$username = $rec['system_user'];
		
		$rec['used'] = $monitor_data['user'][$username]['used'];
		$rec['soft'] = $monitor_data['user'][$username]['soft'];
		$rec['hard'] = $monitor_data['user'][$username]['hard'];
		
		if($rec['soft'] == '0K') $rec['soft'] = $app->lng('unlimited');
		if($rec['hard'] == '0K') $rec['hard'] = $app->lng('unlimited');
		
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}
}

$list = new list_action;
$list->SQLExtWhere = "type = 'vhost'";

$list->onLoad();


?>