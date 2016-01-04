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
$app->auth->check_module_permissions('sites');

$app->uses('functions');

$app->load('listform_actions');

$tmp_rec =  $app->db->queryAllRecords("SELECT server_id, data from monitor_data WHERE type = 'database_size' ORDER BY created DESC");
$monitor_data = array();
if(is_array($tmp_rec)) {
	for($i = 0; $i < count($tmp_rec); $i++) {
		$tmp_array = unserialize($tmp_rec[$i]['data']);
		$server_id = $tmp_rec[$i]['server_id'];

		foreach($tmp_array as $database_name => $data) {
			$db_name = $data['database_name'];

			$temp = $app->db->queryOneRecord("SELECT client.username, web_database.database_quota FROM web_database, sys_group, client WHERE web_database.sys_groupid = sys_group.groupid AND sys_group.client_id = client.client_id AND web_database.database_name = ?", $db_name);
			if(is_array($temp) && !empty($temp)) {
				$monitor_data[$server_id.'.'.$db_name]['database_name'] = $data['database_name'];
				$monitor_data[$server_id.'.'.$db_name]['client'] = isset($temp['username']) ? $temp['username'] : '';
				$monitor_data[$server_id.'.'.$db_name]['used'] = isset($data['size']) ? $data['size'] : 0;
				$monitor_data[$server_id.'.'.$db_name]['quota'] = isset($temp['database_quota']) ? $temp['database_quota'] : 0;
			}
			unset($temp);
		}
	}
}

class list_action extends listform_actions {

	function prepareDataRow($rec) {
		global $app, $monitor_data;

		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;

		$database_name = $rec['database_name'];
		
		if(!empty($monitor_data[$rec['server_id'].'.'.$database_name])){
			$rec['database'] = $monitor_data[$rec['server_id'].'.'.$database_name]['database_name'];
			$rec['client'] = $monitor_data[$rec['server_id'].'.'.$database_name]['client'];
			$rec['server_name'] = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ?", $rec['server_id'])['server_name'];
			$rec['used'] = $monitor_data[$rec['server_id'].'.'.$database_name]['used'];
			$rec['quota'] = $monitor_data[$rec['server_id'].'.'.$database_name]['quota'];

			if($rec['quota'] == 0){
				$rec['quota'] = $app->lng('unlimited');
				$rec['percentage'] = '';
			} else {
				if ($rec['used'] > 0 ) $rec['percentage'] = round(100 * intval($rec['used']) / ( intval($rec['quota'])*1024*1024) ).'%';
				$rec['quota'] .= ' MB';
			}

			if ($rec['used'] > 0) $rec['used'] = $app->functions->formatBytes($rec['used']);
		} else {
			$web_database = $app->db->queryOneRecord("SELECT * FROM web_database WHERE database_id = ".$rec[$this->idx_key]);
			$rec['database'] = $rec['database_name'];
			$rec['server_name'] = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ?", $web_database['server_id'])['server_name'];
			$sys_group = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE groupid = ".$web_database['sys_groupid']);
			$client = $app->db->queryOneRecord("SELECT * FROM client WHERE client_id = ".$sys_group['client_id']);
			$rec['client'] = $client['username'];
			$rec['used'] = 'n/a';
			$rec['quota'] = 'n/a';
		}
		$rec['id'] = $rec[$this->idx_key];

		return $rec;
	}

}

$list = new list_action;
$list->SQLExtWhere = "";
$list->SQLOrderBy = "";
$list->onLoad();

?>
