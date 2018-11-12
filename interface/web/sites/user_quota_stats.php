<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/user_quota_stats.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->uses('functions');

$app->load('listform_actions');

$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'harddisk_quota' ORDER BY created DESC");
$monitor_data = array();
if(is_array($tmp_rec)) {
	foreach ($tmp_rec as $tmp_mon) {
		$monitor_data = array_merge_recursive($monitor_data, unserialize($app->db->unquote($tmp_mon['data'])));
	}
}


class list_action extends listform_actions {

	function prepareDataRow($rec)
	{
		global $app, $monitor_data;

		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;
		$username = $rec['system_user'];

		$server = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ?", $rec['server_id']);
		$rec['domain'] = $rec['domain'].($server['server_name'] != '' ? ' ('.$server['server_name'].')' : '');

		$rec['used'] = $monitor_data['user'][$username]['used'];
		$rec['used_sort'] = $rec['used'];
		$rec['soft'] = $monitor_data['user'][$username]['soft'];
		$rec['soft_sort'] = $rec['soft'];
		$rec['hard'] = $monitor_data['user'][$username]['hard'];
		$rec['hard_sort'] = $rec['hard'];
		$rec['files'] = $monitor_data['user'][$username]['files'];

		if (!is_numeric($rec['used'])){
			if ($rec['used'][0] > $rec['used'][1]){
				$rec['used'] = $rec['used'][0];
			} else {
				$rec['used'] = $rec['used'][1];
			}
		}
		$rec['percentage'] = $rec['soft'] != 0 ? round(($rec['used']/$rec['soft'])*100) : -1;
		$rec['progressbar'] = $rec['percentage'] > 100 ? 100 : $rec['percentage'];
		$rec['used_sort'] = $rec['used'];
		if (!is_numeric($rec['soft'])) $rec['soft']=$rec['soft'][1];
		if (!is_numeric($rec['hard'])) $rec['hard']=$rec['hard'][1];
		if (!is_numeric($rec['files'])) $rec['files']=$rec['files'][1];
		$rec['used']=$app->functions->formatBytes($rec['used']*1024);
		$rec['soft'] = $rec['soft'] == 0 ? $app->lng('unlimited') : $rec['soft']=$app->functions->formatBytes($rec['soft']*1024);
		$rec['hard'] = $rec['hard'] == 0 ? $app->lng('unlimited') : $rec['hard']=$app->functions->formatBytes($rec['hard']*1024);
		$rec['files'] = is_numeric($rec['files']) ? $rec['files'] : 0;
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}

}

$list = new list_action;
$list->SQLExtWhere = "web_domain.type = 'vhost'";
$list->SQLOrderBy = 'ORDER BY web_domain.domain';
$list->onLoad();
?>
