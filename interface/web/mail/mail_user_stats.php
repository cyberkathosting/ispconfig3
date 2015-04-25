<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/mail_user_stats.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('mail');

$app->uses('functions');

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
		$tmp_rec = $app->db->queryOneRecord("SELECT traffic as t FROM mail_traffic WHERE mailuser_id = ? AND month = ?", $rec['mailuser_id'], $tmp_date);
//		$rec['this_month'] = number_format($app->functions->intval($tmp_rec['t'])/1024/1024, 0, '.', ' ');
		$rec['this_month'] = $app->functions->formatBytes($tmp_rec['t']);
		if ($rec['this_month'] == 'NAN') $rec['this_month'] = '0 KB';

		//** Traffic of the current year
		$tmp_date = date('Y');
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic) as t FROM mail_traffic WHERE mailuser_id = ? AND month like ?", $rec['mailuser_id'], $tmp_date . '%');
//		$rec['this_year'] = number_format($app->functions->intval($tmp_rec['t'])/1024/1024, 0, '.', ' ');
		$rec['this_year'] = $app->functions->formatBytes($tmp_rec['t']);
		if ($rec['this_year'] == 'NAN') $rec['this_year'] = '0 KB';

		//** Traffic of the last month
		$tmp_date = date('Y-m', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$tmp_rec = $app->db->queryOneRecord("SELECT traffic as t FROM mail_traffic WHERE mailuser_id = ? AND month = ?", $rec['mailuser_id'], $tmp_date);
//		$rec['last_month'] = number_format($app->functions->intval($tmp_rec['t'])/1024/1024, 0, '.', ' ');
		$rec['last_month'] = $app->functions->formatBytes($tmp_rec['t']);
		if ($rec['last_month'] == 'NAN') $rec['last_month'] = '0 KB';

		//** Traffic of the last year
		$tmp_date = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
		$tmp_rec = $app->db->queryOneRecord("SELECT sum(traffic) as t FROM mail_traffic WHERE mailuser_id = ? AND month like ?", $rec['mailuser_id'], $tmp_date . '%');
//		$rec['last_year'] = number_format($app->functions->intval($tmp_rec['t'])/1024/1024, 0, '.', ' ');
		$rec['last_year'] = $app->functions->formatBytes($tmp_rec['t']);
		if ($rec['last_year'] == 'NAN') $rec['last_year'] = '0 KB';

		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
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
			if($order == 'mail_traffic_last_month'){
				$tmp_date = date('Y-m', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
				$join .= ' INNER JOIN mail_traffic as mt ON '.$app->listform->listDef['table'].'.mailuser_id = mt.mailuser_id ';
				$sql_where .= " AND mt.month like '$tmp_date%'";
				$order_by_sql = str_replace($app->listform->listDef['table'].'.mail_traffic_last_month', 'traffic', $order_by_sql);
			} elseif($order == 'mail_traffic_this_month'){
				$tmp_date = date('Y-m');
				$join .= ' INNER JOIN mail_traffic as mt ON '.$app->listform->listDef['table'].'.mailuser_id = mt.mailuser_id ';
				$sql_where .= " AND mt.month like '$tmp_date%'";
				$order_by_sql = str_replace($app->listform->listDef['table'].'.mail_traffic_this_month', 'traffic', $order_by_sql);
			} elseif($order == 'mail_traffic_last_year'){
				$tmp_date = date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
				$extselect .= ', SUM(mt.traffic) as calctraffic';
				$join .= ' INNER JOIN mail_traffic as mt ON '.$app->listform->listDef['table'].'.mailuser_id = mt.mailuser_id ';
				$sql_where .= " AND mt.month like '$tmp_date%'";;
				$order_by_sql = str_replace($app->listform->listDef['table'].'.mail_traffic_last_year', 'calctraffic', $order_by_sql);
				$order_by_sql = "GROUP BY mailuser_id ".$order_by_sql;
			} elseif($order == 'mail_traffic_this_year'){
				$tmp_date = date('Y');
				$extselect .= ', SUM(mt.traffic) as calctraffic';
				$join .= ' INNER JOIN mail_traffic as mt ON '.$app->listform->listDef['table'].'.mailuser_id = mt.mailuser_id ';
				$sql_where .= " AND mt.month like '$tmp_date%'";
				$order_by_sql = str_replace($app->listform->listDef['table'].'.mail_traffic_this_year', 'calctraffic', $order_by_sql);
				$order_by_sql = "GROUP BY mailuser_id ".$order_by_sql;
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
$list->onLoad();


?>
