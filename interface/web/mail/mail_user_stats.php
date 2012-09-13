<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/mail_user_stats.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('mail');

$app->load('listform_actions');

class list_action extends listform_actions {
	
	function prepareDataRow($rec)
    {
		global $app;
		
		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;
		
		//* Set the statistics colums
		//** Traffic of the current month
		$tmp_date = date('Y-m');
		$tmp_rec = $app->db->queryOneRecord("SELECT traffic as t FROM mail_traffic WHERE mailuser_id = ".$rec['mailuser_id']." AND month = '$tmp_date'");
		$rec['this_month'] = number_format($app->functions->intval($tmp_rec['t'])/1024/1024, 0, '.', ' ');
		
		//** Traffic of the current year
		$tmp_date = date('Y');
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic) as t FROM mail_traffic WHERE mailuser_id = ".$rec['mailuser_id']." AND month like '$tmp_date%'");
		$rec['this_year'] = number_format($app->functions->intval($tmp_rec['t'])/1024/1024, 0, '.', ' ');
		
		//** Traffic of the last month
		$tmp_date = date('Y-m',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$tmp_rec = $app->db->queryOneRecord("SELECT traffic as t FROM mail_traffic WHERE mailuser_id = ".$rec['mailuser_id']." AND month = '$tmp_date'");
		$rec['last_month'] = number_format($app->functions->intval($tmp_rec['t'])/1024/1024, 0, '.', ' ');
		
		//** Traffic of the last year
		$tmp_date = date('Y',mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic) as t FROM mail_traffic WHERE mailuser_id = ".$rec['mailuser_id']." AND month like '$tmp_date%'");
		$rec['last_year'] = number_format($app->functions->intval($tmp_rec['t'])/1024/1024, 0, '.', ' ');
		
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}
}

$list = new list_action;
$list->onLoad();


?>