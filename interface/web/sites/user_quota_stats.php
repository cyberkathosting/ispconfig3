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

// $tmp_rec = $app->db->queryOneRecord("SELECT data from monitor_data WHERE type = 'harddisk_quota' ORDER BY created DESC");
// $monitor_data = unserialize($app->db->unquote($tmp_rec['data']));
$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'harddisk_quota' ORDER BY created DESC");
$monitor_data = array();
if(is_array($tmp_rec)) {
	foreach ($tmp_rec as $tmp_mon) {
		$monitor_data = array_merge_recursive($monitor_data,unserialize($app->db->unquote($tmp_mon['data'])));
	}
}


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
		$rec['files'] = $monitor_data['user'][$username]['files'];
		
		if (!is_numeric($rec['used'])) $rec['used']=$rec['used'][1];
		if (!is_numeric($rec['soft'])) $rec['soft']=$rec['soft'][1];
		if (!is_numeric($rec['hard'])) $rec['hard']=$rec['hard'][1];
		if (!is_numeric($rec['files'])) $rec['files']=$rec['files'][1];
		
		if($rec['used'] > 1024) {
			$rec['used'] = round($rec['used'] / 1024,2).' MB';
		} else {
			$rec['used'] .= ' KB';
		}
		
		if($rec['soft'] > 1024) {
			$rec['soft'] = round($rec['soft'] / 1024,2).' MB';
		} else {
			$rec['soft'] .= ' KB';
		}
		
		if($rec['hard'] > 1024) {
			$rec['hard'] = round($rec['hard'] / 1024,2).' MB';
		} else {
			$rec['hard'] .= ' KB';
		}
		
		
		
		/*
		if(!strstr($rec['used'],'M') && !strstr($rec['used'],'K')) $rec['used'].= ' B';
		if(!strstr($rec['soft'],'M') && !strstr($rec['soft'],'K')) $rec['soft'].= ' B';
		if(!strstr($rec['hard'],'M') && !strstr($rec['hard'],'K')) $rec['hard'].= ' B';
		*/
		
		if($rec['soft'] == '0 B' || $rec['soft'] == '0 KB' || $rec['soft'] == '0') $rec['soft'] = $app->lng('unlimited');
		if($rec['hard'] == '0 B' || $rec['hard'] == '0 KB' || $rec['hard'] == '0') $rec['hard'] = $app->lng('unlimited');
		
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}
}

$list = new list_action;
$list->SQLExtWhere = "type = 'vhost'";

$list->onLoad();


?>