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

		$rec['active'] = "Yes";
		if ($rec['backup_interval'] === 'none') {
			$rec['active']        = "No";
			$rec['backup_copies'] = 0;
		}

		$recBackup = $app->db->queryOneRecord('SELECT COUNT(backup_id) AS backup_count FROM web_backup WHERE parent_domain_id = ?', $rec['domain_id']);
		$rec['backup_copies_exists'] = $recBackup['backup_count'];
		unset($recBackup);
		$recBackup = $app->db->queryOneRecord('SELECT SUM(filesize) AS backup_size FROM web_backup WHERE parent_domain_id = ?', $rec['domain_id']);
		$rec['backup_size'] = $app->functions->formatBytes($recBackup['backup_size']);

		return $rec;
	}
}

$list = new list_action;
$list->SQLExtWhere = "web_domain.type = 'vhost'";
$list->onLoad();
