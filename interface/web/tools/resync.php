<?php
/*
Copyright (c) 2014, Florian Schaal, info@schaal-24.de
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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = 'form/resync.tform.php';

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	//* called during onShowEnd
	/*
	* @param array $server_rec
	* @param string $server_type
	* @param string or array $search
	*
	* @return array
	*/

	private function create_list($server_rec, $server_type, $search) {

		$server_count = 0;

		//* we allow multiple search-pattern - convert string to array
		if (!is_array($search)) {
			$_search = $search;
			$search=array();
			$search[]=$_search;
		}

		foreach ($server_rec as $server) {
			//* check the database for existing records
			$server_data = $this->server_has_data($server_type, $server['server_id']);
			foreach ($search as $needle) 
				if (in_array($needle, $server_data) && strpos($options_servers, $server['server_name']) === false) {
//				if (in_array($needle, $server_data)) {
					$options_servers .= "<option value='$server[server_id]'>$server[server_name]</option>";
					$server_count++;
				}
		}

		return array($options_servers, $server_count);
	}

	//* called from create_list
	private function server_has_data($type, $server) {

		global $app;

		$server_id = $app->functions->intval($server);

		if($type == 'mail') {
			$server_data = array (
    			'mail_domain' => array (
        			'index_field' => 'domain_id',
        			'server_type' => 'mail',
					'server_id' => $server_id,
    			),
				'mail_get' => array (
					'index_field' =>  'mailget_id',
					'server_type' => 'mail',
					'server_id' => $server_id,
				),
    			'mail_mailinglist' => array (
        			'index_field' =>  'mailinglist_id',
        			'server_type' => 'mail',
					'server_id' => $server_id,
    			),
    			'mail_user' => array (
        			'index_field' =>  'mailuser_id',
        			'server_type' => 'mail',
					'server_id' => $server_id,
				),
    			'mail_transport' => array (
        			'index_field' =>  'transport_id',
        			'server_type' => 'mail',
					'server_id' => $server_id,
				),
    			'mail_relay' => array (
        			'index_field' =>  'relay_recipient_id',
        			'server_type' => 'mail',
					'server_id' => $server_id,
				),
			);
		}
		if($type == 'mail_filter') {
			$server_data = array (
				'mail_access' => array (
					'index_field' => 'access_id',
        			'server_type' => 'mail',
					'server_id' => $server_id,
    			),
				'mail_content_filter' => array (
					'index_field' => 'content_filter_id',
        			'server_type' => 'mail',
    			),
				'mail_user_filter' => array (
					'index_field' => 'filter_id',
        			'server_type' => 'mail',
    			),
				'spamfilter_policy' => array (
					'index_field' => 'id',
					'server_type' => 'mail',
				),
				'spamfilter_users' => array (
					'index_field' => 'id',
					'server_type' => 'mail',
					'server_id' => $server_id,
				),
				'spamfilter_wblist' => array (
					'index_field' => 'wblist_id',
					'server_type' => 'mail',
					'server_id' => $server_id,
				),
			);
		}
		if($type == 'web'  ) {
			$server_data = array (
    			'web_domain' => array (
        			'index_field' => 'domain_id',
        			'server_type' => 'web',
					'server_id' => $server_id,
    			),
    			'shell_user' => array (
        			'index_field' => 'shell_user_id',
        			'server_type' => 'web',
					'server_id' => $server_id,
    			),
    			'cron' => array (
        			'index_field' => 'id',
        			'server_type' => 'cron',
					'server_id' => $server_id,
    			),
    			'ftp_user' => array (
        			'index_field' => 'ftp_user_id',
        			'server_type' => 'web',
					'server_id' => $server_id,
    			),
			);
		}
		if($type == 'dns' ) {
			$server_data = array (
				'dns_soa' => array (
					'index_field' => 'id',
					'server_type' => 'dns',
					'server_id' => $server_id,
				),
			);
		}
		if($type == 'file' ) {
			$server_data = array (
    			'webdav_user' => array (
        			'index_field' => 'webdav_user_id',
        			'server_type' => 'file',
					'server_id' => $server_id,
    			),
			);
		}
		if($type == 'db' ) {
			$server_data = array (
				'web_database' => array (
					'index_field' => 'web_database_id',
					'server_type' => 'db',
					'server_id' => $server_id,
				),
			);
		}
		if($type == 'vserver' ) {
			$server_data = array (
				'openvz_vm' => array (
					'index_field' => 'vm_id',
					'server_type' => 'vserver',
					'server_id' => $server_id,
				),
			);
		}
		//* proxy
		//* firewall
		$array_out = array();
		foreach($server_data as $db_table => $data) {
			$sql = @(isset($data['server_id']))?"SELECT * FROM ?? WHERE server_id = ?":"SELECT * FROM ??";
			$records = $app->db->queryAllRecords($sql, $db_table, $server_id);
			if (!empty($records)) array_push($array_out, $db_table);
		}

		return $array_out;
	}

	function onShowEnd() {
		global $app;

		//* fetch all-server
		$server_rec =  $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE active = 1 AND mirror_server_id = 0 ORDER BY active DESC, server_name");
		$server_count = 0;
		foreach ($server_rec as $server) {
			$options_servers .= "<option value='$server[server_id]'>$server[server_name]</option>";
			$server_count++;
		}
		if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_txt']."</option>" . $options_servers;
		$app->tpl->setVar('all_server_id', $options_servers);
		unset($options_servers);

		//* fetch mail-server
		$mail_server_rec = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE mail_server = 1 AND active = 1 AND mirror_server_id = 0 ORDER BY active DESC, server_name");
		if (!empty($mail_server_rec)) {
			$app->tpl->setVar('mail_server_found', 1);

			//* mail-domain
			$server_list = $this->create_list($mail_server_rec, 'mail', 'mail_domain');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_mail_txt']."</option>" . $options_servers;
				$app->tpl->setVar('mail_server_id', $options_servers);
				$app->tpl->setVar('mail_domain_found', 1);
				unset($options_servers);
			}

			//* mail-get
			$server_list = $this->create_list($mail_server_rec, 'mail', 'mail_get');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_mail_txt']."</option>" . $options_servers;
				$app->tpl->setVar('mailget_server_id', $options_servers);
				$app->tpl->setVar('mail_get_found', 1);
				unset($options_servers);
			}

			//* mailbox
			$server_list = $this->create_list($mail_server_rec, 'mail', 'mail_user');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_mail_txt']."</option>" . $options_servers;
				$app->tpl->setVar('mailbox_server_id', $options_servers);
				$app->tpl->setVar('mail_user_found', 1);
				unset($options_servers);
			}

			//* mailfilter
			$server_list = $this->create_list($mail_server_rec, 'mail_filter', array('mail_access', 'mail_content_filter', 'mail_user_filter','spamfilter_users', 'spamfilter_wblist'));
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_mail_txt']."</option>" . $options_servers;
				$app->tpl->setVar('mailfilter_server_id', $options_servers);
				$app->tpl->setVar('mail_filter_found', 1);
				unset($options_servers);
			}

			//* mailinglist
			$server_list = $this->create_list($mail_server_rec, 'mail', 'mail_mailinglist');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_mail_txt']."</option>" . $options_servers;
				$app->tpl->setVar('mailinglist_server_id', $options_servers);
				$app->tpl->setVar('mailinglist_found', 1);
				unset($options_servers);
			}

			//* mailtransport
			$server_list = $this->create_list($mail_server_rec, 'mail', 'mail_transport');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_mail_transport_txt']."</option>" . $options_servers;
				$app->tpl->setVar('mailtransport_server_id', $options_servers);
				$app->tpl->setVar('mailtransport_found', 1);
				unset($options_servers);
			}

			//* mailrelay
			$server_list = $this->create_list($mail_server_rec, 'mail', 'mail_relay');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_mail_relay_txt']."</option>" . $options_servers;
				$app->tpl->setVar('mailrelay_server_id', $options_servers);
				$app->tpl->setVar('mailrelay_found', 1);
				unset($options_servers);
			}
		}

		//* fetch web-server
		$web_server_rec = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE web_server = 1 AND active = 1 AND mirror_server_id = 0 ORDER BY active DESC, server_name");
		if (!empty($web_server_rec)) {
			$app->tpl->setVar('web_server_found', 1);

			//* web-domain
			$server_list = $this->create_list($web_server_rec, 'web', 'web_domain');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_web_txt']."</option>" . $options_servers;
				$app->tpl->setVar('web_server_id', $options_servers);
				$app->tpl->setVar('web_domain_found', 1);
				unset($options_servers);
			}

			//* ftp-user
			$server_list = $this->create_list($web_server_rec, 'web', 'ftp_user');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_web_txt']."</option>" . $options_servers;
				$app->tpl->setVar('ftp_server_id', $options_servers);
				$app->tpl->setVar('ftp_user_found', 1);
				unset($options_servers);
			}

			//* shell-user
			$server_list = $this->create_list($web_server_rec, 'web', 'shell_user');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_web_txt']."</option>" . $options_servers;
				$app->tpl->setVar('shell_server_id', $options_servers);
				$app->tpl->setVar('shell_user_found', 1);
				unset($options_servers);
			}

			//* cron
			$server_list = $this->create_list($web_server_rec, 'web', 'cron');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_web_txt']."</option>" . $options_servers;
				$app->tpl->setVar('cron_server_id', $options_servers);
				$app->tpl->setVar('cron_found', 1);
				unset($options_servers);
			}
		}

		//* fetch dns-server
		$dns_server_rec = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE dns_server = 1 AND active = 1 AND mirror_server_id = 0 ORDER BY active DESC, server_name");
		if (!empty($dns_server_rec)) {
			$app->tpl->setVar('dns_server_found', 1);

			$server_list = $this->create_list($dns_server_rec, 'dns', 'dns_soa');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_dns_txt']."</option>" . $options_servers;
				$app->tpl->setVar('dns_server_id', $options_servers);
				$app->tpl->setVar('dns_soa_found', 1);
				unset($options_servers);
			}
		}

		//* fetch webdav-user
		$file_server_rec = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE file_server = 1 AND active = 1 AND mirror_server_id = 0 ORDER BY active DESC, server_name");
		if (!empty($file_server_rec)) {
			$app->tpl->setVar('file_server_found', 1);

			$server_list = $this->create_list($file_server_rec, 'file', 'webdav_user');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_file_txt']."</option>" . $options_servers;
				$app->tpl->setVar('file_server_id', $options_servers);
				$app->tpl->setVar('webdav_user_found', 1);
				unset($options_servers);
			}
		}

		//* fetch database-server
		$db_server_rec = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE db_server = 1 AND active = 1 AND mirror_server_id = 0 ORDER BY active DESC, server_name");
		if (!empty($db_server_rec)) {
			$app->tpl->setVar('db_server_found', 1);

			$server_list = $this->create_list($db_server_rec, 'db', 'web_database');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_db_txt']."</option>" . $options_servers;
				$app->tpl->setVar('db_server_id', $options_servers);
				$app->tpl->setVar('client_db_found', 1);
				unset($options_servers);
			}
		}

		//* fetch vserver
		$v_server_rec = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE vserver_server = 1 AND active = 1 AND mirror_server_id = 0 ORDER BY active DESC, server_name");
		if (!empty($v_server_rec)) {
			$app->tpl->setVar('vserver_server_found', 1);

			$server_list = $this->create_list($v_server_rec, 'vserver', 'openvz_vm');
			$options_servers = $server_list[0];$server_count = $server_list[1];
			unset($server_list);
			if (isset($options_servers)) {	//* server with data found
				if ($server_count > 1) $options_servers = "<option value='0'>".$app->tform->wordbook['all_active_vserver_txt']."</option>" . $options_servers;
				$app->tpl->setVar('vserver_server_id', $options_servers);
				$app->tpl->setVar('vserver_found', 1);
				unset($options_servers);
			}
		}

		$csrf_token = $app->auth->csrf_token_get('tools_resync');
		$app->tpl->setVar('_csrf_id', $csrf_token['csrf_id']);
		$app->tpl->setVar('_csrf_key', $csrf_token['csrf_key']);

		parent::onShowEnd();
	}
			
	//* fetch values during do_resync
	private function query_server($db_table, $server_id, $server_type, $active=true, $opt='') {
		global $app;

		$server_name = array();
		if ( $server_id == 0 ) { //* resync multiple server
			$temp = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE ?? = 1 AND active = 1 AND mirror_server_id = 0", $server_type."_server");
			foreach ($temp as $server) {
				$temp_id .= $server['server_id'].',';
				$server_name[$server['server_id']] = $server['server_name'];
			}
			if ( isset($temp_id) ) $server_id = rtrim($temp_id,',');
		} else {
			$temp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ?", $server_id);
			$server_name[$server_id] = $temp['server_name'];
		}		
		unset($temp);

		$sql = "SELECT * FROM ??";
		if ($db_table != "mail_user_filter" && $db_table != "spamfilter_policy") $sql .= " WHERE server_id IN (".$server_id.") ";
		$sql .= $opt;
		if ($active) $sql .= " AND active = 'y'"; 
		$records = $app->db->queryAllRecords($sql, $db_table);

		return array($records, $server_name);
	}			

	private function do_resync($db_table, $index_field, $server_type, $server_id, $msg_field, $wordbook, $active=true) {
        global $app;

		$server_id = $app->functions->intval($server_id);
		$rec = $this->query_server($db_table, $server_id, $server_type, $active);
		$records = $rec[0];
		$server_name = $rec[1];
		$msg = '<b>'.$wordbook.'</b><br>';
		if(!empty($records)) 
			foreach($records as $rec) {
				$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
				if(!empty($rec[$msg_field])) $msg .= '['.$server_name[$rec['server_id']].'] '.$rec[$msg_field].'<br>';
			}
		else $msg .= $app->tform->wordbook['no_results_txt'].'<br>';

		return $msg.'<br>';
	}

    function onSubmit() {
        global $app;
		
		if(isset($_POST) && count($_POST) > 1) {
			//* CSRF Check
			$app->auth->csrf_token_check();
		}
		
		//* all services
		if($this->dataRecord['resync_all'] == 1) {
			$this->dataRecord['resync_sites'] = 1;
			$this->dataRecord['resync_ftp'] = 1;
			$this->dataRecord['resync_webdav'] = 1;
			$this->dataRecord['resync_shell'] = 1;
			$this->dataRecord['resync_cron'] = 1;
			$this->dataRecord['resync_db'] = 1;
			$this->dataRecord['resync_mail'] = 1;
			$this->dataRecord['resync_mailget'] = 1;
			$this->dataRecord['resync_mailbox'] = 1;
			$this->dataRecord['resync_mailfilter'] = 1;
			$this->dataRecord['resync_mailinglist'] = 1;
			$this->dataRecord['resync_mailtransport'] = 1;
			$this->dataRecord['resync_mailrelay'] = 1;
			$this->dataRecord['resync_vserver'] = 1;
			$this->dataRecord['resync_dns'] = 1;
			$this->dataRecord['resync_client'] = 1;
			$this->dataRecord['web_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['ftp_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['webdav_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['shell_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['cron_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['db_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['mail_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['mailbox_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['vserver_server_id'] = $this->dataRecord['all_server_id'];
			$this->dataRecord['dns_server_id'] = $this->dataRecord['all_server_id'];
		}

		//* websites
		if($this->dataRecord['resync_sites'] == 1)
			$msg .= $this->do_resync('web_domain', 'domain_id', 'web', $this->dataRecord['web_server_id'], 'domain', $app->tform->wordbook['do_sites_txt']);

		//* ftp
		if($this->dataRecord['resync_ftp'] == 1)
			$msg .= $this->do_resync('ftp_user', 'ftp_user_id', 'web', $this->dataRecord['ftp_server_id'], 'username',  $app->tform->wordbook['do_ftp_txt']);

		//* webdav
		if($this->dataRecord['resync_webdav'] == 1) 
			$msg .= $this->do_resync('webdav_user', 'webdav_user_id', 'file', $this->dataRecord['webdav_server_id'], 'username',  $app->tform->wordbook['do_webdav_txt']);

		//* shell
		if($this->dataRecord['resync_shell'] == 1) 
			$msg .= $this->do_resync('shell_user', 'shell_user_id', 'web', $this->dataRecord['shell_server_id'], 'username',  $app->tform->wordbook['do_shell_txt']);

		//* cron
		if($this->dataRecord['resync_cron'] == 1) 
			$msg .= $this->do_resync('cron', 'id', 'web', $this->dataRecord['cron_server_id'], 'command',  $app->tform->wordbook['do_cron_txt']);

		//* database
		if(isset($this->dataRecord['resync_db']) && $this->dataRecord['resync_db'] == 1) {
			$msg .= $this->do_resync('web_database_user', 'database_user_id', 'db', $this->dataRecord['db_server_id'], 'database_user',  $app->tform->wordbook['do_db_user_txt'], false);
			$msg .= $this->do_resync('web_database', 'database_id', 'db', $this->dataRecord['db_server_id'], 'database_name',  $app->tform->wordbook['do_db_txt']);
		}

		//* maildomains
		if($this->dataRecord['resync_mail'] == 1) {
			$msg .= $this->do_resync('mail_domain', 'domain_id', 'mail', $this->dataRecord['mail_server_id'], 'domain',  $app->tform->wordbook['do_mail_txt']);
			$msg .= $this->do_resync('spamfilter_policy', 'id', 'mail', $this->dataRecord['mail_server_id'], '',  $app->tform->wordbook['do_mail_spamfilter_policy_txt'], false);
		}

		//* mailget
		if($this->dataRecord['resync_mailget'] == 1) {
			$msg .= $this->do_resync('mail_get', 'mailget_id', 'mail', $this->dataRecord['mail_server_id'], 'source_username',  $app->tform->wordbook['do_mailget_txt']);
		}

		//* mailbox
		if($this->dataRecord['resync_mailbox'] == 1) {
			$msg .= $this->do_resync('mail_user', 'mailuser_id', 'mail', $this->dataRecord['mailbox_server_id'], 'email',  $app->tform->wordbook['do_mailbox_txt'], false);
			$msg .= $this->do_resync('mail_forwarding', 'forwarding_id', 'mail', $this->dataRecord['mailbox_server_id'], '',  $app->tform->wordbook['do_mail_alias_txt']);
		}

		//* mailfilter
		if($this->dataRecord['resync_mailfilter'] == 1) {
			$msg .= $this->do_resync('mail_access', 'access_id', 'mail', $this->dataRecord['mailbox_server_id'], '',  $app->tform->wordbook['do_mail_access_txt']);
			$msg .= $this->do_resync('mail_content_filter', 'content_filter_id', 'mail', $this->dataRecord['mailbox_server_id'], '',  $app->tform->wordbook['do_mail_contentfilter_txt']);
			$msg .= $this->do_resync('mail_user_filter', 'filter_id', 'mail', $this->dataRecord['mailbox_server_id'], '',  $app->tform->wordbook['do_mail_userfilter_txt'], false);
			//* spam
			$msg .= $this->do_resync('spamfilter_users', 'id', 'mail', $this->dataRecord['mailbox_server_id'], '',  $app->tform->wordbook['do_mail_spamfilter_txt'], false);
			$msg .= $this->do_resync('spamfilter_wblist', 'wblist_id', 'mail', $this->dataRecord['mailbox_server_id'], '',  $app->tform->wordbook['do_mail_spamfilter_txt']) 	;
		}

		//* mailinglists
		if($this->dataRecord['resync_mailinglist'] == 1) 
			$msg .= $this->do_resync('mail_mailinglist', 'mailinglist_id', 'mail', $this->dataRecord['mail_server_id'], 'listname',  $app->tform->wordbook['do_mailinglist_txt'], false);

		//* mailtransport
		if($this->dataRecord['resync_mailtransport'] == 1) 
			$msg .= $this->do_resync('mail_transport', 'transport_id', 'mail', $this->dataRecord['mail_server_id'], 'domain',  $app->tform->wordbook['do_mailtransport_txt'], false);

		//* mailrelay
		if($this->dataRecord['resync_mailrelay'] == 1) 
			$msg .= $this->do_resync('mail_relay_recipient', 'relay_recipient_id', 'mail', $this->dataRecord['mail_server_id'], 'source',  $app->tform->wordbook['do_mailrelay_txt'], false);

		//* vserver
		if($this->dataRecord['resync_vserver'] == 1) 
			$msg .= $this->do_resync('openvz_vm', 'vm_id', 'vserver', $this->dataRecord['vserver_server_id'], 'hostname',  $app->tform->wordbook['do_vserver_txt']);

		//* dns
		if($this->dataRecord['resync_dns'] == 1) {
			$rec=$this->query_server('dns_soa', $this->dataRecord['dns_server_id'], 'dns'); 
			$soa_records = $rec[0];
			$server_name = $rec[1];
			unset($rec);
			$msg .= '<b>'.$app->tform->wordbook['do_dns_txt'].'</b><br>';
			if(is_array($soa_records) && !empty($soa_records)) 
				foreach($soa_records as $soa_rec) {
					$temp = $this->query_server('dns_rr', $soa_rec['server_id'], 'dns', true, "AND zone = ".$app->functions->intval($soa_rec['id']));
					$rr_records = $temp[0];
					if(!empty($rr_records)) {
						foreach($rr_records as $rec) {
							$new_serial = $app->validate_dns->increase_serial($rec['serial']);
							$app->db->datalogUpdate('dns_rr', array("serial" => $new_serial), 'id', $rec['id']);
						}
					} else { 
						$msg .= $app->tform->wordbook['no_results_txt'].'<br>';
					}
					$new_serial = $app->validate_dns->increase_serial($soa_rec['serial']);
					$app->db->datalogUpdate('dns_soa', array("serial" => $new_serial), 'id', $soa_rec['id']);
					$msg .= '['.$server_name[$soa_rec['server_id']].'] '.$soa_rec['origin'].' ('.count($rr_records).')<br>';
				}
			else $msg .= $app->tform->wordbook['no_results_txt'].'<br>'; 

			$msg .= '<br>';
        }

		//* clients
		if($this->dataRecord['resync_client'] == 1) {
        	$db_table = 'client';
        	$index_field = 'client_id';
        	$records = $app->db->queryAllRecords("SELECT * FROM ??", $db_table);
			$msg .= '<b>'.$app->tform->wordbook['do_clients_txt'].'</b><br>';
			if(!empty($records)) {
	        	$tform_def_file = '../client/form/client.tform.php';
    	    	$app->uses('tpl,tform,tform_actions');
        		$app->load('tform_actions');
				foreach($records as $rec) {
					$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
					$tmp = new tform_actions;
					$tmp->id = $rec[$index_field];
					$tmp->dataRecord = $rec;
					$tmp->oldDataRecord = $rec;
					$app->plugin->raiseEvent('client:client:on_after_update', $tmp);
					$msg .= $rec['contact_name'].'<br>';
					unset($tmp);
				}
			} else {
				$msg .= $app->tform->wordbook['no_results_txt'].'<br>'; 
			}
			$msg .= '<br>';
		}

		echo $msg;
    } //* end onSumbmit

}

$page = new page_action;
$page->onLoad();
?>
