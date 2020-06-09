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

			//$temp = $app->db->queryOneRecord("SELECT client.username, web_database.database_quota FROM web_database, sys_group, client WHERE sys_group.groupid = web_database.sys_groupid AND sys_group.client_id = client.client_id AND web_database.database_name = ?", $db_name);
			$temp = $app->db->queryOneRecord("SELECT sys_groupid, database_quota FROM web_database WHERE web_database.database_name = ?", $db_name);
			if($temp['sys_groupid'] > 0) {
				$client = $app->db->queryOneRecord("SELECT client.username FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.groupid = ?", $temp['sys_groupid']);
				$temp['username'] = $client['username'];
			} else {
				$temp['username'] = 'admin';
			}

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
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ?", $rec['server_id']);
			$rec['server_name'] = $tmp['server_name'];
			unset($tmp);
			$rec['used'] = $monitor_data[$rec['server_id'].'.'.$database_name]['used'];
			$rec['quota'] = $monitor_data[$rec['server_id'].'.'.$database_name]['quota'];

			if($rec['quota'] <= 0){
				$rec['quota'] = $app->lng('unlimited_txt');
				$rec['percentage'] = '';
			} else {
				if ($rec['used'] > 0 ) $rec['percentage'] = round(100 * intval($rec['used']) / ( intval($rec['quota'])*1024*1024) ).'%';
				$rec['quota'] .= ' MB';
			}

			if ($rec['used'] > 0) $rec['used'] = $app->functions->formatBytes($rec['used']);
		} else {
			$web_database = $app->db->queryOneRecord("SELECT * FROM web_database WHERE database_id = ?", $rec[$this->idx_key]);
			$rec['database'] = $rec['database_name'];
			$temp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ?", $web_database['server_id']);
			$rec['server_name'] = $temp['server_name'];
			$sys_group = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE groupid = ?", $web_database['sys_groupid']);
			$client = $app->db->queryOneRecord("SELECT * FROM client WHERE client_id = ?", $sys_group['client_id']);
			$rec['client'] = $client['username'];
			$rec['used'] = 'n/a';
			if (empty($rec['database_quota'])) $rec['database_quota'] = 0;
			$rec['quota'] = $rec['database_quota'];
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
