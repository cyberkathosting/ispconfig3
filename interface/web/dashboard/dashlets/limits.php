<?php

class dashlet_limits {

	function show() {
		global $app, $conf;

		$limits = array();

		/* Limits to be shown*/
		
		$limits[] = array('field' => 'limit_mailquota',
			'db_table' => 'mail_user',
			'db_where' => 'quota > 0',  /* Count only posive value of quota, negative value -1 is unlimited */
			'q_type' => 'quota');

		$limits[] = array('field' => 'limit_maildomain',
			'db_table' => 'mail_domain',
			'db_where' => '');

		$limits[] = array('field' => 'limit_mailmailinglist',
			'db_table' => 'mail_mailinglist',
			'db_where' => '');

		$limits[] = array('field' => 'limit_mailbox',
			'db_table' => 'mail_user',
			'db_where' => '');

		$limits[] = array('field' => 'limit_mailalias',
			'db_table' => 'mail_forwarding',
			'db_where' => "type = 'alias'");

		$limits[] = array('field' => 'limit_mailaliasdomain',
			'db_table' => 'mail_forwarding',
			'db_where' => "type = 'aliasdomain'");

		$limits[] = array('field' => 'limit_mailforward',
			'db_table' => 'mail_forwarding',
			'db_where' => "type = 'forward'");

		$limits[] = array('field' => 'limit_mailcatchall',
			'db_table' => 'mail_forwarding',
			'db_where' => "type = 'catchall'");

		$limits[] = array('field' => 'limit_mailrouting',
			'db_table' => 'mail_transport',
			'db_where' => "");

		$limits[] = array('field' => 'limit_mailfilter',
			'db_table' => 'mail_user_filter',
			'db_where' => "");

		$limits[] = array('field' => 'limit_fetchmail',
			'db_table' => 'mail_get',
			'db_where' => "");

		$limits[] = array('field' => 'limit_spamfilter_wblist',
			'db_table' => 'spamfilter_wblist',
			'db_where' => "");

		$limits[] = array('field' => 'limit_spamfilter_user',
			'db_table' => 'spamfilter_users',
			'db_where' => "");

		$limits[] = array('field' => 'limit_spamfilter_policy',
			'db_table' => 'spamfilter_policy',
			'db_where' => "");

		$limits[] = array('field' => 'limit_web_quota',
			'db_table' => 'web_domain',
			'db_where' => 'hd_quota > 0', /* Count only posive value of quota, negative value -1 is unlimited */
			'q_type' => 'hd_quota');
			
		$limits[] = array('field' => 'limit_web_domain',
			'db_table' => 'web_domain',
			'db_where' => "type = 'vhost'");

		$limits[] = array('field' => 'limit_web_subdomain',
			'db_table' => 'web_domain',
			'db_where' => "(type = 'subdomain' OR type = 'vhostsubdomain')");

		$limits[] = array('field' => 'limit_web_aliasdomain',
			'db_table' => 'web_domain',
						  'db_where' => "(type = 'alias' OR type = 'vhostalias')");

		$limits[] = array('field' => 'limit_ftp_user',
			'db_table' => 'ftp_user',
			'db_where' => "");

		$limits[] = array('field' => 'limit_shell_user',
			'db_table' => 'shell_user',
			'db_where' => "");

		$limits[] = array('field' => 'limit_dns_zone',
			'db_table' => 'dns_soa',
			'db_where' => "");

		$limits[] = array('field' => 'limit_dns_slave_zone',
			'db_table' => 'dns_slave',
			'db_where' => "");

		$limits[] = array('field' => 'limit_dns_record',
			'db_table' => 'dns_rr',
			'db_where' => "");

		$limits[] = array('field' => 'limit_database_quota',
			'db_table' => 'web_database',
			'db_where' => 'database_quota > 0', /* Count only posive value of quota, negative value -1 is unlimited */
			'q_type' => 'database_quota');
			
		$limits[] = array('field' => 'limit_database',
			'db_table' => 'web_database',
			'db_where' => "");

		$limits[] = array('field' => 'limit_cron',
			'db_table' => 'cron',
			'db_where' => "");

		$limits[] = array('field' => 'limit_client',
			'db_table' => 'client',
			'db_where' => "");

		$limits[] = array('field' => 'limit_domain',
			'db_table' => 'domain',
			'db_where' => "");


		//* Loading Template
		$app->uses('tpl,tform');

		$tpl = new tpl;
		$tpl->newTemplate("dashlets/templates/limits.htm");

		$wb = array();
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dashlet_limits.lng';
		if(is_file($lng_file)) include $lng_file;
		$tpl->setVar($wb);

		if($app->auth->is_admin()) {
			$user_is_admin = true;
		} else {
			$user_is_admin = false;
		}
		$tpl->setVar('is_admin', $user_is_admin);

		if($user_is_admin == false) {
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT * FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
		}

		$rows = array();
		foreach($limits as $limit) {
			$field = $limit['field'];
			if($user_is_admin) {
				$value = $wb['unlimited_txt'];
			} else {
				$value = $client[$field];
			}
			if($value != 0 || $value == $wb['unlimited_txt']) {
				$value_formatted = ($value == '-1')?$wb['unlimited_txt']:$value;
				if($limit['q_type']!=''){
					$usage = $this->_get_assigned_quota($limit) . " MB";
					$value_formatted = ($value == '-1')?$wb['unlimited_txt']:$value . " MB";
				}
				else $usage = $this->_get_limit_usage($limit);
				$percentage = ($value == '-1' || $value == 0 ? 0 : round(100 * $usage / $value));
				$rows[] = array('field' => $field,
					'field_txt' => $wb[$field.'_txt'],
					'value' => $value_formatted,
					'value_raw' => $value,
					'usage' => $usage,
					'usage_raw' => $usage,
					'percentage' => $percentage);
			}
		}
		$rows = $app->functions->htmlentities($rows);
		$tpl->setLoop('rows', $rows);


		return $tpl->grab();

	}

	function _get_limit_usage($limit) {
		global $app;

		$sql = "SELECT count(sys_userid) as number FROM ?? WHERE ";
		if($limit['db_where'] != '') $sql .= $limit['db_where']." AND ";
		$sql .= $app->tform->getAuthSQL('r');
		$rec = $app->db->queryOneRecord($sql, $limit['db_table']);
		return $rec['number'];

	}
	
	function _get_assigned_quota($limit) {
		global $app;

		$sql = "SELECT sum(??) as number FROM ?? WHERE ";
		if($limit['db_where'] != '') $sql .= $limit['db_where']." AND ";
		$sql .= $app->tform->getAuthSQL('r');
		$rec = $app->db->queryOneRecord($sql, $limit['q_type'], $limit['db_table']);
		if($limit['db_table']=='mail_user') $quotaMB = $rec['number'] / 1048576; // Mail quota is in bytes, must be converted to MB
		else $quotaMB = $rec['number'];
		return $quotaMB;

	}

}








?>
