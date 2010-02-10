<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/web_sites_stats.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('sites');

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
		$tmp_year = date('Y');
        $tmp_month = date('m');
		$tmp_rec = $app->db->queryOneRecord("SELECT SUM(traffic_bytes) as t FROM web_traffic WHERE hostname = '".$rec['domain']."' AND YEAR(traffic_date) = '$tmp_year' AND MONTH(traffic_date) = '$tmp_month'");
		$rec['this_month'] = number_format($tmp_rec['t']/1024, 0, '.', ' ');
		
		//** Traffic of the current year
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = '".$rec['domain']."' AND YEAR(traffic_date) = '$tmp_year'");
		$rec['this_year'] = number_format($tmp_rec['t']/1024, 0, '.', ' ');
		
		//** Traffic of the last month
        $tmp_year = date('Y',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
        $tmp_month = date('m',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = '".$rec['domain']."' AND YEAR(traffic_date) = '$tmp_year' AND MONTH(traffic_date) = '$tmp_month'");
		$rec['last_month'] = number_format($tmp_rec['t']/1024, 0, '.', ' ');
		
		//** Traffic of the last year
		$tmp_year = date('Y',mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = '".$rec['domain']."' AND YEAR(traffic_date) = '$tmp_year'");
		$rec['last_year'] = number_format($tmp_rec['t']/1024, 0, '.', ' ');
		
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}
}

$list = new list_action;
$list->onLoad();


?>