<?php
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

$list_def_file = 'list/backup_stats.list.php';

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->load('listform_actions','functions');

class list_action extends listform_actions {

	public function prepareDataRow($rec)
	{
		global $app;
		$app->uses('functions');

		$rec = parent::prepareDataRow($rec);

		$rec['active'] = $app->lng('yes_txt');
		if ($rec['backup_interval'] === 'none' || $rec['backup_interval'] === '') {
			$rec['active'] = $app->lng('no_txt');
			$rec['backup_copies'] = 0;
			$rec['backup_interval'] = 'none';
		}
		$rec['interval_sort'] = $rec['type'] . $rec['backup_interval']; 
		$recBackup = $app->db->queryOneRecord('SELECT COUNT(backup_id) AS backup_count FROM web_backup WHERE parent_domain_id = ?', $rec['domain_id']);
		$rec['backup_copies_exists'] = $recBackup['backup_count'];
		unset($recBackup);
		$recBackup = $app->db->queryOneRecord('SELECT SUM(filesize) AS backup_size FROM web_backup WHERE parent_domain_id = ?', $rec['domain_id']);
		$rec['backup_size_sort'] = $recBackup['backup_size'];
		$rec['backup_size'] = $app->functions->formatBytes($recBackup['backup_size']);

		return $rec;
	}
}

$list = new list_action;
$list->SQLExtWhere = "web_domain.type = 'vhost'";
$list->onLoad();
