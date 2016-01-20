<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/ftp_sites_stats.list.php";

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

		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;

		//* Set the statistics colums
		//** Traffic of the current month
		$tmp_year = date('Y');
		$tmp_month = date('m');
		$tmp_rec = $app->db->queryOneRecord("SELECT SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE hostname = ? AND YEAR(traffic_date) = ? AND MONTH(traffic_date) = ?", $rec['domain'], $tmp_year, $tmp_month);
		$rec['this_month_in'] = $app->functions->formatBytes($tmp_rec['ftp_in']);
		$rec['this_month_out'] = $app->functions->formatBytes($tmp_rec['ftp_out']);
		$this->sum_this_month += $tmp_rec['ftp_in']+$tmp_rec['ftp_out'];
		
		//** Traffic of the current year
		$tmp_rec = $app->db->queryOneRecord("SELECT SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE hostname = ? AND YEAR(traffic_date) = ?", $rec['domain'], $tmp_year);
		$rec['this_year_in'] = $app->functions->formatBytes($tmp_rec['ftp_in']);
		$rec['this_year_out'] = $app->functions->formatBytes($tmp_rec['ftp_out']);
		$this->sum_this_year += $tmp_rec['ftp_in']+$tmp_rec['ftp_out'];

		//** Traffic of the last month
		$tmp_year = date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$tmp_month = date('m', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));		
		$tmp_rec = $app->db->queryOneRecord("SELECT SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE hostname = ? AND YEAR(traffic_date) = ? AND MONTH(traffic_date) = ?", $rec['domain'], $tmp_year, $tmp_month);
		$rec['last_month_in'] = $app->functions->formatBytes($tmp_rec['ftp_in']);
		$rec['last_month_out'] = $app->functions->formatBytes($tmp_rec['ftp_out']);
		$this->sum_last_month += $tmp_rec['ftp_in']+$tmp_rec['ftp_out'];

		//** Traffic of the last year
		$tmp_year = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
		$tmp_rec = $app->db->queryOneRecord("SELECT SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE hostname = ? AND YEAR(traffic_date) = ?", $rec['domain'], $tmp_year);
		$rec['last_year_in'] = $app->functions->formatBytes($tmp_rec['ftp_in']);
		$rec['last_year_out'] = $app->functions->formatBytes($tmp_rec['ftp_out']);
		$this->sum_last_year += $tmp_rec['ftp_in']+$tmp_rec['ftp_out'];

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

	function getQueryString($no_limit = false) {
		global $app;
		$sql_where = '';

		//* Generate the search sql
		if($app->listform->listDef['auth'] != 'no') {
			if($_SESSION['s']['user']['typ'] == "admin") {
				$sql_where = '';
			} else {
				$sql_where = $app->tform->getAuthSQL('r', $app->listform->listDef['table']).' and';
				//$sql_where = $app->tform->getAuthSQL('r').' and';
			}
		}
		if($this->SQLExtWhere != '') {
			$sql_where .= ' '.$this->SQLExtWhere.' and';
		}

		$sql_where = $app->listform->getSearchSQL($sql_where);
		if($app->listform->listDef['join_sql']) $sql_where .= ' AND '.$app->listform->listDef['join_sql'];
		$app->tpl->setVar($app->listform->searchValues);

		$order_by_sql = $this->SQLOrderBy;

		//* Generate SQL for paging
		$limit_sql = $app->listform->getPagingSQL($sql_where);
		$app->tpl->setVar('paging', $app->listform->pagingHTML);

		$extselect = '';
		$join = '';

		if(!empty($_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'])){
			$order = str_replace(' DESC', '', $_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order']);
			list($tmp_table, $order) = explode('.', $order);
			if($order == 'ftp_traffic_last_month'){
				$tmp_year = date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
				$tmp_month = date('m', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
				$extselect .= ', SUM(ft.in_bytes+out_bytes) as calctraffic';
				$join .= ' INNER JOIN ftp_traffic as ft ON '.$app->listform->listDef['table'].'.domain = ft.hostname ';
				$sql_where .= " AND YEAR(ft.traffic_date) = '$tmp_year' AND MONTH(ft.traffic_date) = '$tmp_month'";
				$order_by_sql = str_replace($app->listform->listDef['table'].'.ftp_traffic_last_month', 'calctraffic', $order_by_sql);
				$order_by_sql = "GROUP BY domain ".$order_by_sql;
			} elseif($order == 'ftp_traffic_this_month'){
				$tmp_year = date('Y');
				$tmp_month = date('m');
				$extselect .= ', SUM(ft.in_bytes+out_bytes) as calctraffic';
				$join .= ' INNER JOIN ftp_traffic as ft ON '.$app->listform->listDef['table'].'.domain = ft.hostname ';
				$sql_where .= " AND YEAR(ft.traffic_date) = '$tmp_year' AND MONTH(ft.traffic_date) = '$tmp_month'";
				$order_by_sql = str_replace($app->listform->listDef['table'].'.ftp_traffic_this_month', 'calctraffic', $order_by_sql);
				$order_by_sql = "GROUP BY domain ".$order_by_sql;
			} elseif($order == 'ftp_traffic_last_year'){
				$tmp_year = date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
				$extselect .= ', SUM(ft.in_bytes+out_bytes) as calctraffic';
				$join .= ' INNER JOIN ftp_traffic as ft ON '.$app->listform->listDef['table'].'.domain = ft.hostname ';
				$sql_where .= " AND YEAR(ft.traffic_date) = '$tmp_year'";
				$order_by_sql = str_replace($app->listform->listDef['table'].'.ftp_traffic_last_year', 'calctraffic', $order_by_sql);
				$order_by_sql = "GROUP BY domain ".$order_by_sql;
			} elseif($order == 'ftp_traffic_this_year'){
				$tmp_year = date('Y');
				$extselect .= ', SUM(ft.in_bytes+out_bytes) as calctraffic';
				$join .= ' INNER JOIN ftp_traffic as ft ON '.$app->listform->listDef['table'].'.domain = ft.hostname ';
				$sql_where .= " AND YEAR(ft.traffic_date) = '$tmp_year'";
				$order_by_sql = str_replace($app->listform->listDef['table'].'.ftp_traffic_this_year', 'calctraffic', $order_by_sql);
				$order_by_sql = "GROUP BY domain ".$order_by_sql;
			}
		}

		if($this->SQLExtSelect != '') { 
			if(substr($this->SQLExtSelect, 0, 1) != ',') $this->SQLExtSelect = ','.$this->SQLExtSelect;
			$extselect .= $this->SQLExtSelect;
		}

		$table_selects = array();
		$table_selects[] = trim($app->listform->listDef['table']).'.*';
		$app->listform->listDef['additional_tables'] = trim($app->listform->listDef['additional_tables']);
		if($app->listform->listDef['additional_tables'] != ''){
			$additional_tables = explode(',', $app->listform->listDef['additional_tables']);
			foreach($additional_tables as $additional_table){
				$table_selects[] = trim($additional_table).'.*';
			}
		}
		$select = implode(', ', $table_selects);

		$sql = 'SELECT '.$select.$extselect.' FROM '.$app->listform->listDef['table'].($app->listform->listDef['additional_tables'] != ''? ','.$app->listform->listDef['additional_tables'] : '')."$join WHERE $sql_where $order_by_sql $limit_sql";
		return $sql;
	}
}

$list = new list_action;
$list->SQLExtWhere = "(web_domain.type = 'vhost' or web_domain.type = 'vhostsubdomain')";
$list->SQLOrderBy = 'ORDER BY web_domain.domain';
$list->onLoad();

?>