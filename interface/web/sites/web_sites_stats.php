<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/web_sites_stats.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->uses('functions');

$app->load('listform_actions');

class list_action extends listform_actions {

	private $sum_this_month = 0;
	private $sum_this_year = 0;
	private $sum_last_month = 0;
	private $sum_last_year = 0;

	function prepareDataRow($rec)
	{
		global $app;

		$domain = $rec['domain'];
		$rec = $app->listform->decode($rec);
		
		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;

		//* Set the statistics colums
		//** Traffic of the current month
		$tmp_year = date('Y');
		$tmp_month = date('m');
		$tmp_rec = $app->db->queryOneRecord("SELECT SUM(traffic_bytes) as t FROM web_traffic WHERE hostname = ? AND YEAR(traffic_date) = ? AND MONTH(traffic_date) = ?", $domain, $tmp_year, $tmp_month);
		$rec['this_month'] = $app->functions->formatBytes($tmp_rec['t']);
		$rec['this_month_sort'] = $tmp_rec['t'];
		$this->sum_this_month += $tmp_rec['t'];


		//** Traffic of the current year
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = ? AND YEAR(traffic_date) = ?", $domain, $tmp_year);
		$rec['this_year'] = $app->functions->formatBytes($tmp_rec['t']);
		$rec['this_year_sort'] = $tmp_rec['t'];
		$this->sum_this_year += $tmp_rec['t'];

		//** Traffic of the last month
		$tmp_year = date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$tmp_month = date('m', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = ? AND YEAR(traffic_date) = ? AND MONTH(traffic_date) = ?", $domain, $tmp_year, $tmp_month);
		$rec['last_month'] = $app->functions->formatBytes($tmp_rec['t']);
		$rec['last_month_sort'] = $tmp_rec['t'];
		$this->sum_last_month += $tmp_rec['t'];

		//** Traffic of the last year
		$tmp_year = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic_bytes) as t FROM web_traffic WHERE hostname = ? AND YEAR(traffic_date) = ?", $domain, $tmp_year);
		$rec['last_year'] = $app->functions->formatBytes($tmp_rec['t']);
		$rec['last_year_sort'] = $tmp_rec['t'];
		$this->sum_last_year += $tmp_rec['t'];
		$rec['percentage'] = $rec['traffic_quota'] == '-1' ? -1 : round((($rec['this_month_sort']/($rec['traffic_quota']*1024*1024))*100));
		$rec['progressbar'] = $rec['percentage'] > 100 ? 100 : $rec['percentage'];
		$rec['quota_sort'] = $rec['traffic_quota'];
		$rec['quota'] = $rec['traffic_quota'] == '-1' ? 'unlimited' : $app->functions->formatBytes($rec['traffic_quota']*1024*1024);
		//echo 'quota: ' . $rec['traffic_quota']*1024*1024 . ' - traffic: ' . $rec['this_month_sort'] . ' - percentage: ' . $rec['percentage'] . ' - progressbar: ' . $rec['progressbar'] . '<br/>';

		//var_dump($rec);
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];

		return $rec;
	}

	function onShowEnd()
	{
		global $app;

		$app->tpl->setVar('sum_this_month', $app->functions->formatBytes($this->sum_this_month));
		$app->tpl->setVar('sum_this_year', $app->functions->formatBytes($this->sum_this_year));
		$app->tpl->setVar('sum_last_month', $app->functions->formatBytes($this->sum_last_month));
		$app->tpl->setVar('sum_last_year', $app->functions->formatBytes($this->sum_last_year));
		$app->tpl->setVar('sum_txt', $app->listform->lng('sum_txt'));

		$app->tpl_defaults();
		$app->tpl->pparse();
	}

}

$list = new list_action;
$list->SQLExtWhere = "(web_domain.type = 'vhost' or web_domain.type = 'vhostsubdomain' or web_domain.type = 'vhostalias')";
$list->SQLOrderBy = 'ORDER BY web_domain.domain';
$list->onLoad();


?>

