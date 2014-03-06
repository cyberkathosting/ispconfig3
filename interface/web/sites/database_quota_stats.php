<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/database_quota_stats.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('mail');

$app->load('listform_actions','functions');

$tmp_rec =  $app->db->queryOneRecord("SELECT data from monitor_data WHERE type = 'database_size' ORDER BY created DESC");
$monitor_data = array();
$tmp_array = unserialize($tmp_rec['data']);

foreach($tmp_array as $database_name => $data) {
	$db_name = $data['database_name'];

	$temp = $app->db->queryOneRecord("SELECT client.username, web_database.database_quota FROM web_database, sys_group, client WHERE web_database.sys_groupid = sys_group.groupid AND sys_group.client_id = client.client_id AND web_database.database_name = ?'", $db_name);

	$monitor_data[$db_name]['database_name'] = $data['database_name'];
	$monitor_data[$db_name]['client']=$temp['username'];
	$monitor_data[$db_name]['used'] = $data['size'];
	$monitor_data[$db_name]['quota']=$temp['database_quota'];

	unset($temp);
}

class list_action extends listform_actions {

	function prepareDataRow($rec) {
		global $app, $monitor_data;

		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;

		$database_name = $rec['database_name'];

		$rec['database'] = isset($monitor_data[$database_name]['database_name']) ? $monitor_data[$database_name]['database_name'] : array(1 => 0);
		$rec['client'] = isset($monitor_data[$database_name]['client']) ? $monitor_data[$database_name]['client'] : array(1 => 0);
		$rec['used'] = isset($monitor_data[$database_name]['used']) ? $monitor_data[$database_name]['used'] : array(1 => 0);
		$rec['quota'] = isset($monitor_data[$database_name]['quota']) ? $monitor_data[$database_name]['quota'] : array(1 => 0);

		if (!is_numeric($rec['used'])) $rec['used']=$rec['used'][1];

		if($rec['quota'] == 0){
			$rec['quota'] = $app->lng('unlimited');
			$rec['percentage'] = '';
		} else {
			$rec['percentage'] = round(100 * $rec['used'] / ( $rec['quota']*1024*1024) ).'%';
			$rec['quota'] .= ' MB';
		}

		if ($rec['used'] > 0) $rec['used'] = $app->functions->formatBytes($rec['used']);

		$rec['id'] = $rec[$this->idx_key];
		return $rec;

	}

}

$list = new list_action;
$list->SQLExtWhere = "";

$list->onLoad();

?>
