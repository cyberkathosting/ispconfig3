<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class cronjob_quota_notify extends cronjob {

	// job schedule
	protected $_schedule = '0 0 * * *';

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
	public function onBeforeRun() {
		global $app;

		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;
		
		/* used for all monitor cronjobs */
		$app->load('monitor_tools');
		$this->_tools = new monitor_tools();
		/* end global section for monitor cronjobs */

		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		//######################################################################################################
		// enforce traffic quota (run only on the "master-server")
		//######################################################################################################

		if ($app->dbmaster == $app->db) {

			$global_config = $app->getconf->get_global_config('mail');

			$current_month = date('Y-m');

			//* Check website traffic quota
			$sql = "SELECT sys_groupid,domain_id,domain,traffic_quota,traffic_quota_lock FROM web_domain WHERE (traffic_quota > 0 or traffic_quota_lock = 'y') and (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias')";
			$records = $app->db->queryAllRecords($sql);
			if(is_array($records)) {
				foreach($records as $rec) {

					$web_traffic_quota = $rec['traffic_quota'];
					$domain = $rec['domain'];

					//* get the traffic
					$tmp = $app->db->queryOneRecord("SELECT SUM(traffic_bytes) As total_traffic_bytes FROM web_traffic WHERE traffic_date like '$current_month%' AND hostname = '$domain'");
					$web_traffic = round($tmp['total_traffic_bytes']/1024/1024);

					if($web_traffic_quota > 0 && $web_traffic > $web_traffic_quota) {
						$app->dbmaster->datalogUpdate('web_domain', array("traffic_quota_lock" => 'y', "active" => 'n'), 'domain_id', $rec['domain_id']);
						$app->log('Traffic quota for '.$rec['domain'].' exceeded. Disabling website.', LOGLEVEL_DEBUG);

						//* Send traffic notifications
						if($rec['traffic_quota_lock'] != 'y' && ($web_config['overtraffic_notify_admin'] == 'y' || $web_config['overtraffic_notify_client'] == 'y')) {

							$placeholders = array('{domain}' => $rec['domain'],
								'{admin_mail}' => ($global_config['admin_mail'] != ''? $global_config['admin_mail'] : 'root'));

							$recipients = array();
							//* send email to admin
							if($global_config['admin_mail'] != '' && $web_config['overtraffic_notify_admin'] == 'y') {
								$recipients[] = $global_config['admin_mail'];
							}

							//* Send email to client
							if($web_config['overtraffic_notify_client'] == 'y') {
								$client_group_id = $rec["sys_groupid"];
								$client = $app->db->queryOneRecord("SELECT client.email FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
								if($client['email'] != '') {
									$recipients[] = $client['email'];
								}
							}

							$this->_tools->send_notification_email('web_traffic_notification', $placeholders, $recipients);
						}

					} else {
						//* unlock the website, if traffic is lower then quota
						if($rec['traffic_quota_lock'] == 'y') {
							$app->dbmaster->datalogUpdate('web_domain', array("traffic_quota_lock" => 'n', "active" => 'y'), 'domain_id', $rec['domain_id']);
							$app->log('Traffic quota for '.$rec['domain'].' ok again. Re-enabling website.', LOGLEVEL_DEBUG);
						}
					}
				}
			}


		}


		//######################################################################################################
		// send website quota warnings by email
		//######################################################################################################

		if ($app->dbmaster == $app->db) {

			$global_config = $app->getconf->get_global_config('mail');

			//* Check website disk quota
			$sql = "SELECT domain_id,sys_groupid,domain,system_user,last_quota_notification,DATEDIFF(CURDATE(), last_quota_notification) as `notified_before` FROM web_domain WHERE (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias')";
			$records = $app->db->queryAllRecords($sql);
			if(is_array($records) && !empty($records)) {

				$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'harddisk_quota' ORDER BY created DESC");
				$monitor_data = array();
				if(is_array($tmp_rec)) {
					foreach ($tmp_rec as $tmp_mon) {
						$monitor_data = array_merge_recursive($monitor_data, unserialize($app->db->unquote($tmp_mon['data'])));
					}
				}

				foreach($records as $rec) {

					//$web_hd_quota = $rec['hd_quota'];
					$domain = $rec['domain'];

					$username = $rec['system_user'];
					$rec['used'] = @$monitor_data['user'][$username]['used'];
					$rec['soft'] = @$monitor_data['user'][$username]['soft'];
					$rec['hard'] = @$monitor_data['user'][$username]['hard'];
					$rec['files'] = @$monitor_data['user'][$username]['files'];

					if (!is_numeric($rec['used'])){
						if ($rec['used'][0] > $rec['used'][1]){
							$rec['used'] = $rec['used'][0];
						} else {
							$rec['used'] = $rec['used'][1];
						}
					}
					if (!is_numeric($rec['soft'])) $rec['soft']=$rec['soft'][1];
					if (!is_numeric($rec['hard'])) $rec['hard']=$rec['hard'][1];
					if (!is_numeric($rec['files'])) $rec['files']=$rec['files'][1];

					// used space ratio
					if($rec['soft'] > 0){
						$used_ratio = $rec['used']/$rec['soft'];
					} else {
						$used_ratio = 0;
					}

					$rec['ratio'] = number_format($used_ratio * 100, 2, '.', '').'%';

					if($rec['used'] > 1024) {
						$rec['used'] = round($rec['used'] / 1024, 2).' MB';
					} else {
						if ($rec['used'] != '') $rec['used'] .= ' KB';
					}

					if($rec['soft'] > 1024) {
						$rec['soft'] = round($rec['soft'] / 1024, 2).' MB';
					} elseif($rec['soft'] == 0){
						$rec['soft'] = '----';
					} else {
						$rec['soft'] .= ' KB';
					}

					if($rec['hard'] > 1024) {
						$rec['hard'] = round($rec['hard'] / 1024, 2).' MB';
					} elseif($rec['hard'] == 0){
						$rec['hard'] = '----';
					} else {
						$rec['hard'] .= ' KB';
					}

					// send notifications only if 90% or more of the quota are used
					if($used_ratio < 0.9) {
						// reset notification date
						if($rec['last_quota_notification']) $app->dbmaster->datalogUpdate('web_domain', array("last_quota_notification" => null), 'domain_id', $rec['domain_id']);

						// send notification - everything ok again
						if($rec['last_quota_notification'] && $web_config['overquota_notify_onok'] == 'y' && ($web_config['overquota_notify_admin'] == 'y' || $web_config['overquota_notify_client'] == 'y')) {
							$placeholders = array('{domain}' => $rec['domain'],
								'{admin_mail}' => ($global_config['admin_mail'] != ''? $global_config['admin_mail'] : 'root'),
								'{used}' => $rec['used'],
								'{soft}' => $rec['soft'],
								'{hard}' => $rec['hard'],
								'{ratio}' => $rec['ratio']);

							$recipients = array();

							//* send email to admin
							if($global_config['admin_mail'] != '' && $web_config['overquota_notify_admin'] == 'y') {
								$recipients[] = $global_config['admin_mail'];
							}

							//* Send email to client
							if($web_config['overquota_notify_client'] == 'y') {
								$client_group_id = $rec["sys_groupid"];
								$client = $app->db->queryOneRecord("SELECT client.email FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
								if($client['email'] != '') {
									$recipients[] = $client['email'];
								}
							}
							$this->_tools->send_notification_email('web_quota_ok_notification', $placeholders, $recipients);
						}
					} else {

						// could a notification be sent?
						$send_notification = false;
						if(!$rec['last_quota_notification']) $send_notification = true; // not yet notified
						elseif($web_config['overquota_notify_freq'] > 0 && $rec['notified_before'] >= $web_config['overquota_notify_freq']) $send_notification = true;

						//* Send quota notifications
						if(($web_config['overquota_notify_admin'] == 'y' || $web_config['overquota_notify_client'] == 'y') && $send_notification == true) {
							$app->dbmaster->datalogUpdate('web_domain', array("last_quota_notification" => array("SQL" => "CURDATE()")), 'domain_id', $rec['domain_id']);

							$placeholders = array('{domain}' => $rec['domain'],
								'{admin_mail}' => ($global_config['admin_mail'] != ''? $global_config['admin_mail'] : 'root'),
								'{used}' => $rec['used'],
								'{soft}' => $rec['soft'],
								'{hard}' => $rec['hard'],
								'{ratio}' => $rec['ratio']);

							$recipients = array();

							//* send email to admin
							if($global_config['admin_mail'] != '' && $web_config['overquota_notify_admin'] == 'y') {
								$recipients[] = $global_config['admin_mail'];
							}

							//* Send email to client
							if($web_config['overquota_notify_client'] == 'y') {
								$client_group_id = $rec["sys_groupid"];
								$client = $app->db->queryOneRecord("SELECT client.email FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
								if($client['email'] != '') {
									$recipients[] = $client['email'];
								}
							}
							$this->_tools->send_notification_email('web_quota_notification', $placeholders, $recipients);
						}
					}
				}
			}
		}


		//######################################################################################################
		// send mail quota warnings by email
		//######################################################################################################

		if ($app->dbmaster == $app->db) {

			$global_config = $app->getconf->get_global_config('mail');
			$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

			//* Check email quota
			$sql = "SELECT mailuser_id,sys_groupid,email,name,quota,last_quota_notification,DATEDIFF(CURDATE(), last_quota_notification) as `notified_before` FROM mail_user";
			$records = $app->db->queryAllRecords($sql);
			if(is_array($records) && !empty($records)) {

				$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'email_quota' ORDER BY created DESC");
				$monitor_data = array();
				if(is_array($tmp_rec)) {
					foreach ($tmp_rec as $tmp_mon) {
						//$monitor_data = array_merge_recursive($monitor_data,unserialize($app->db->unquote($tmp_mon['data'])));
						$tmp_array = unserialize($app->db->unquote($tmp_mon['data']));
						if(is_array($tmp_array)) {
							foreach($tmp_array as $username => $data) {
								if(@!$monitor_data[$username]['used']) $monitor_data[$username]['used'] = $data['used'];
							}
						}
					}
				}

				foreach($records as $rec) {

					$email = $rec['email'];

					$rec['used'] = isset($monitor_data[$email]['used']) ? $monitor_data[$email]['used'] : array(1 => 0);

					if (!is_numeric($rec['used'])) $rec['used']=$rec['used'][1];

					// used space ratio
					if($rec['quota'] > 0){
						$used_ratio = $rec['used']/$rec['quota'];
					} else {
						$used_ratio = 0;
					}

					$rec['ratio'] = number_format($used_ratio * 100, 2, '.', '').'%';

					if($rec['quota'] > 0){
						$rec['quota'] = round($rec['quota'] / 1048576, 4).' MB';
					} else {
						$rec['quota'] = '----';
					}

					if($rec['used'] < 1544000) {
						$rec['used'] = round($rec['used'] / 1024, 4).' KB';
					} else {
						$rec['used'] = round($rec['used'] / 1048576, 4).' MB';
					}

					// send notifications only if 90% or more of the quota are used
					if($used_ratio < 0.9) {
						// reset notification date
						if($rec['last_quota_notification']) $app->dbmaster->datalogUpdate('mail_user', array("last_quota_notification" => null), 'mailuser_id', $rec['mailuser_id']);

						// send notification - everything ok again
						if($rec['last_quota_notification'] && $mail_config['overquota_notify_onok'] == 'y' && ($mail_config['overquota_notify_admin'] == 'y' || $mail_config['overquota_notify_client'] == 'y')) {
							$placeholders = array('{email}' => $rec['email'],
								'{admin_mail}' => ($global_config['admin_mail'] != ''? $global_config['admin_mail'] : 'root'),
								'{used}' => $rec['used'],
								'{name}' => $rec['name'],
								'{quota}' => $rec['quota'],
								'{ratio}' => $rec['ratio']);

							$recipients = array();
							//* send email to admin
							if($global_config['admin_mail'] != '' && $mail_config['overquota_notify_admin'] == 'y') {
								$recipients[] = $global_config['admin_mail'];
							}

							//* Send email to client
							if($mail_config['overquota_notify_client'] == 'y') {
								$client_group_id = $rec["sys_groupid"];
								$client = $app->db->queryOneRecord("SELECT client.email FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
								if($client['email'] != '') {
									$recipients[] = $client['email'];
								}
							}

							$this->_tools->send_notification_email('mail_quota_ok_notification', $placeholders, $recipients);
						}
					} else {

						//* Send quota notifications
						// could a notification be sent?
						$send_notification = false;
						if(!$rec['last_quota_notification']) $send_notification = true; // not yet notified
						elseif($mail_config['overquota_notify_freq'] > 0 && $rec['notified_before'] >= $mail_config['overquota_notify_freq']) $send_notification = true;

						if(($mail_config['overquota_notify_admin'] == 'y' || $mail_config['overquota_notify_client'] == 'y') && $send_notification == true) {
							$app->dbmaster->datalogUpdate('mail_user', array("last_quota_notification" => array("SQL" => "CURDATE()")), 'mailuser_id', $rec['mailuser_id']);

							$placeholders = array('{email}' => $rec['email'],
								'{admin_mail}' => ($global_config['admin_mail'] != ''? $global_config['admin_mail'] : 'root'),
								'{used}' => $rec['used'],
								'{name}' => $rec['name'],
								'{quota}' => $rec['quota'],
								'{ratio}' => $rec['ratio']);

							$recipients = array();
							//* send email to admin
							if($global_config['admin_mail'] != '' && $mail_config['overquota_notify_admin'] == 'y') {
								$recipients[] = $global_config['admin_mail'];
							}

							//* Send email to client
							if($mail_config['overquota_notify_client'] == 'y') {
								$client_group_id = $rec["sys_groupid"];
								$client = $app->db->queryOneRecord("SELECT client.email FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
								if($client['email'] != '') {
									$recipients[] = $client['email'];
								}
							}

							$this->_tools->send_notification_email('mail_quota_notification', $placeholders, $recipients);
						}
					}
				}
			}
		}

		//######################################################################################################
		// send database quota warnings by email
		//######################################################################################################

		if ($app->dbmaster == $app->db) {

			$global_config = $app->getconf->get_global_config('mail');

			//* get monitor-data
			$tmp_rec = $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'database_size' ORDER BY created DESC");
			if(is_array($tmp_rec)) {
				$monitor_data = array();
				foreach ($tmp_rec as $tmp_mon) {
					$tmp_array = unserialize($app->db->unquote($tmp_mon['data']));
					if(is_array($tmp_array)) 
						foreach($tmp_array as $sys_groupid => $data)
							$monitor_data[$data['sys_groupid']][] = $data;
				}
				//* remove duplicates from monitor-data
				foreach($monitor_data as $_monitor_data) 
					$monitor_data[$_monitor_data[0]['sys_groupid']]=array_map("unserialize", array_unique(array_map("serialize", $_monitor_data)));
			}

			//* get databases
			$database_records = $app->db->queryAllRecords("SELECT database_id,sys_groupid,database_name,database_quota,last_quota_notification,DATEDIFF(CURDATE(), last_quota_notification) as `notified_before` FROM web_database");

			if(is_array($database_records) && !empty($database_records) && is_array($monitor_data) && !empty($monitor_data)) {
				//* check database-quota
				foreach($database_records as $rec) {
					$database = $rec['database_name'];
					$quota = $rec['database_quota'] * 1024 * 1024;
					if (!is_numeric($quota)) break;

					foreach ($monitor_data as $cid) {

						foreach($cid as $monitor) {

							if ($monitor['database_name'] == $database) {
								//* get the client
								$client = $app->db->queryOneRecord("SELECT client.username, client.email FROM web_database, sys_group, client WHERE web_database.sys_groupid = sys_group.groupid AND sys_group.client_id = client.client_id AND web_database.database_name=?", $database);

								//* check quota
								if ($quota > 0) $used_ratio = $monitor['size'] / $quota;
								else $used_ratio = 0;

								//* send notifications only if 90% or more of the quota are used
								if($used_ratio > 0.9 && $used_ratio != 0) {

									//* could a notification be sent?
									$send_notification = false;
									if(!$rec['last_quota_notification']) $send_notification = true; //* not yet notified
									elseif($web_config['overquota_notify_freq'] > 0 && $rec['notified_before'] >= $web_config['overquota_notify_freq']) $send_notification = true;


									//* Send quota notifications
									if(($web_config['overquota_db_notify_admin'] == 'y' || $web_config['overquota_db_notify_client'] == 'y') && $send_notification == true) {
										$app->dbmaster->datalogUpdate('web_database', array("last_quota_notification" => array("SQL" => "CURDATE()")), 'database_id', $rec['database_id']);
										$placeholders = array(
											'{database_name}' => $rec['database_name'],
											'{admin_mail}' => ($global_config['admin_mail'] != ''? $global_config['admin_mail'] : 'root'),
											'{used}' => $app->functions->formatBytes($monitor['size']),
											'{quota}' => $app->functions->formatBytes($quota),
											'{ratio}' => number_format($used_ratio * 100, 2, '.', '').'%'
										);

										$recipients = array();

										//* send email to admin
										if($global_config['admin_mail'] != '' && $web_config['overquota_db_notify_admin'] == 'y')
											$recipients[] = $global_config['admin_mail'];

										//* Send email to client
										if($web_config['overquota_db_notify_client'] == 'y' && $client['email'] != '')
											$recipients[] = $client['email'];

										$this->_tools->send_notification_email('db_quota_notification', $placeholders, $recipients);

									}

								} else {
									//* reset notification date
									if($rec['last_quota_notification']) $app->dbmaster->datalogUpdate('web_database', array("last_quota_notification" => null), 'database_id', $rec['database_id']);

									// send notification - everything ok again
									if($rec['last_quota_notification'] && $web_config['overquota_notify_onok'] == 'y' && ($web_config['overquota_db_notify_admin'] == 'y' || $web_config['overquota_db_notify_client'] == 'y')) {
										$placeholders = array(
											'{database_name}' => $rec['database_name'],
											'{admin_mail}' => ($global_config['admin_mail'] != ''? $global_config['admin_mail'] : 'root'),
											'{used}' => $app->functions->formatBytes($monitor['size']),
											'{quota}' => $app->functions->formatBytes($quota),
											'{ratio}' => number_format($used_ratio * 100, 2, '.', '').'%'
										);

										$recipients = array();

										//* send email to admin
										if($global_config['admin_mail'] != '' && $web_config['overquota_db_notify_admin'] == 'y') 
											$recipients[] = $global_config['admin_mail'];

										//* Send email to client
										if($web_config['overquota_db_notify_client'] == 'y' && $client['email'] != '') 
											$recipients[] = $client['email'];

										$this->_tools->send_notification_email('db_quota_ok_notification', $placeholders, $recipients);

									}

								}

							}

						}   

					}

				}

			}
		}
		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
