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
	
	private $sum_this_month = 0;
	private $sum_this_year = 0;
	private $sum_last_month = 0;
	private $sum_last_year = 0;
	
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
		$rec['this_month'] = number_format($tmp_rec['t']/1024/1024, 0, '.', ' ');
		$this->sum_this_month += ($tmp_rec['t']/1024/1024);
		
		//** Traffic of the current year
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = '".$rec['domain']."' AND YEAR(traffic_date) = '$tmp_year'");
		$rec['this_year'] = number_format($tmp_rec['t']/1024/1024, 0, '.', ' ');
		$this->sum_this_year += ($tmp_rec['t']/1024/1024);
		
		//** Traffic of the last month
        $tmp_year = date('Y',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
        $tmp_month = date('m',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = '".$rec['domain']."' AND YEAR(traffic_date) = '$tmp_year' AND MONTH(traffic_date) = '$tmp_month'");
		$rec['last_month'] = number_format($tmp_rec['t']/1024/1024, 0, '.', ' ');
		$this->sum_last_month += ($tmp_rec['t']/1024/1024);
		
		//** Traffic of the last year
		$tmp_year = date('Y',mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = '".$rec['domain']."' AND YEAR(traffic_date) = '$tmp_year'");
		$rec['last_year'] = number_format($tmp_rec['t']/1024/1024, 0, '.', ' ');
		$this->sum_last_year += ($tmp_rec['t']/1024/1024);
		
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		
		return $rec;
	}
	
	function onShowEnd()
    {
		global $app;
		
		$app->tpl->setVar('sum_this_month',number_format($app->functions->intval($this->sum_this_month), 0, '.', ' '));
		$app->tpl->setVar('sum_this_year',number_format($app->functions->intval($this->sum_this_year), 0, '.', ' '));
		$app->tpl->setVar('sum_last_month',number_format($app->functions->intval($this->sum_last_month), 0, '.', ' '));
		$app->tpl->setVar('sum_last_year',number_format($app->functions->intval($this->sum_last_year), 0, '.', ' '));
		$app->tpl->setVar('sum_txt',$app->listform->lng('sum_txt'));
		
		$app->tpl_defaults();
		$app->tpl->pparse();
	}
}

$list = new list_action;
$list->SQLExtWhere = "(type = 'vhost' or type = 'vhostsubdomain')";
$list->SQLOrderBy = 'ORDER BY domain';
$list->onLoad();


?>