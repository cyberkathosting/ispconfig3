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
$app->auth->check_module_permissions('mail');

$app->load('listform_actions');

// $tmp_rec = $app->db->queryOneRecord("SELECT data from monitor_data WHERE type = 'harddisk_quota' ORDER BY created DESC");
// $monitor_data = unserialize($app->db->unquote($tmp_rec['data']));
$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'email_quota' ORDER BY created DESC");
$monitor_data = array();
if(is_array($tmp_rec)) {
	foreach ($tmp_rec as $tmp_mon) {
		//$monitor_data = array_merge_recursive($monitor_data,unserialize($app->db->unquote($tmp_mon['data'])));
		$tmp_array = unserialize($app->db->unquote($tmp_mon['data']));
		if(is_array($tmp_array)) {
			foreach($tmp_array as $username => $data) {
				$monitor_data[$username]['used'] += $data['used'];
			}
		}
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
		$email = $rec['email'];
		
		$rec['used'] = isset($monitor_data[$email]['used']) ? $monitor_data[$email]['used'] : array(1 => 0);
		
		if (!is_numeric($rec['used'])) $rec['used']=$rec['used'][1];

        $rec['quota'] = round($rec['quota'] / 1048576,2).' MB';
		if($rec['quota'] == "0 MB") $rec['quota'] = $app->lng('unlimited');


        if($rec['used'] < 1544000) {
            $rec['used'] = round($rec['used'] / 1024,2).' KB';
        } else {
            $rec['used'] = round($rec['used'] / 1048576,2).' MB';
        }   

		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}
}

$list = new list_action;
$list->SQLExtWhere = "";

$list->onLoad();


?>