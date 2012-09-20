<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
Plesk(r) Importer (c) 2012, Marius Cramer, pixcept KG
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

global $app, $conf;

require_once('../../lib/config.inc.php');

require_once('../../lib/app.inc.php');

/**
 *
 * @param db $exdb
 * @return array 
 */
function read_limit_data($exdb) {
    $limits = array();
    // Limits
    $limit_data = $exdb->queryAllRecords("SELECT l.id, l.limit_name, l.value FROM Limits as l");
    foreach($limit_data as $entry) {
        if(array_key_exists($entry['id'], $limits) == false) $limits[$entry['id']] = array();
        $limits[$entry['id']][$entry['limit_name']] = $entry['value'];

        // limits that are there:
        /*
        disk_space
        disk_space_soft
        expiration
        max_box
        max_db
        max_dom_aliases
        max_maillists
        max_mn
        max_site
        max_site_builder
        max_subdom
        max_subftp_users
        max_traffic
        max_traffic_soft
        max_unity_mobile_sites
        max_webapps
        max_wu
        mbox_quota
        */
    }
    
    return $limits;
}

/**
 *
 * @param array $limits
 * @param int $id
 * @param string $limit
 * @param mixed $default
 * @return mixed 
 */
function get_limit($limits, $id, $limit, $default = false) {
    $ret = $default;
    if(isset($limits[$id][$limit])) $ret = $limits[$id][$limit];
    
    return $ret;
}

function get_option($options, $option, $default = false) {
    $ret = $default;
    if(isset($options[$option])) $ret = $options[$option];
    
    return $ret;
}

function add_dot($string) {
    if(strlen($string) > 0 && substr($string, -1, 1) !== '.') $string .= '.';
    return $string;
}

function byte_to_mbyte($byte) {
    if($byte <= 0) return $byte; // limit = -1 -> unlimited
    return round($byte / (1024*1024));
}

function yes_no($num, $reverse = false) {
    return (($num == 1 && !$reverse) || ($num != 1 && $reverse) ? 'y' : 'n');
}

// taken from the web_domain_edit.php
function id_hash($id,$levels) {
    $hash = "" . $id % 10 ;
    $id /= 10 ;
    $levels -- ;
    while ( $levels > 0 ) {
        $hash .= "/" . $id % 10 ;
        $id /= 10 ;
        $levels-- ;
    }
    return $hash;
}

/* TODO: document root rewrite on ftp account and other home directories */

//* Check permissions for module
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl');
$app->load('importer');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/import_plesk.htm');
$msg = '';
$error = '';

// Start migrating plesk data
if(isset($_POST['start']) && $_POST['start'] == 1) {
	
	//* Set variable sin template
	$app->tpl->setVar('dbhost',$_POST['dbhost']);
	$app->tpl->setVar('dbname',$_POST['dbname']);
	$app->tpl->setVar('dbuser',$_POST['dbuser']);
	$app->tpl->setVar('dbpassword',$_POST['dbpassword']);
	$app->tpl->setVar('webcontent',$_POST['webcontent']);
	$app->tpl->setVar('mailcontent',$_POST['mailcontent']);
	
	//* Establish connection to external database
	$msg .= 'Connecting to external database...<br />';
	
	//* Backup DB login details
	/*$conf_bak['db_host'] = $conf['db_host'];
	$conf_bak['db_database'] = $conf['db_database'];
	$conf_bak['db_user'] = $conf['db_user'];
	$conf_bak['db_password'] = $conf['db_password'];*/
	
	//* Set external Login details
	$conf['imp_db_host'] = $_POST['dbhost'];
	$conf['imp_db_database'] = $_POST['dbname'];
	$conf['imp_db_user'] = $_POST['dbuser'];
	$conf['imp_db_password'] = $_POST['dbpassword'];
    $conf['imp_db_charset'] = $conf['db_charset'];
    $conf['imp_db_new_link'] = $conf['db_new_link'];
    $conf['imp_db_client_flags'] = $conf['db_client_flags'];
	
	//* create new db object
	$exdb = new db('imp');
    
    $msg .= 'db object created...<br />';
    
    $importer = new importer();
    $session_id = 'ISPC3'; // set dummy session id for remoting lib
    $msg .= 'importer object created...<br />';
	
    
    // import on server
	$server_id = 1;
	
	//* Connect to DB
	if($exdb !== false) {
        $msg .= 'Connecting to external database done...<br />';
	
        $limits = read_limit_data($exdb);
        
        $msg .= 'read all limit data...<br />';
	
        // param_id -> cl_params table - not needed for import
        // tpye = admin, reseller, client
        $admins = $exdb->queryAllRecords("SELECT c.id, c.parent_id, c.type, c.cr_date, c.cname, c.pname, c.login, c.account_id, a.password, a.type as `pwtype`, c.status, c.phone, c.fax, c.email, c.address, c.city, c.state, c.pcode, c.country, c.locale, c.limits_id, c.params_id, c.perm_id, c.pool_id, c.logo_id, c.tmpl_id, c.guid, c.overuse, c.vendor_id, c.external_id FROM clients as c LEFT JOIN accounts as a ON (a.id = c.account_id) WHERE c.type = 'admin' ORDER BY c.parent_id, c.id");
        $resellers = $exdb->queryAllRecords("SELECT c.id, c.parent_id, c.type, c.cr_date, c.cname, c.pname, c.login, c.account_id, a.password, a.type as `pwtype`, c.status, c.phone, c.fax, c.email, c.address, c.city, c.state, c.pcode, c.country, c.locale, c.limits_id, c.params_id, c.perm_id, c.pool_id, c.logo_id, c.tmpl_id, c.guid, c.overuse, c.vendor_id, c.external_id FROM clients as c LEFT JOIN accounts as a ON (a.id = c.account_id) WHERE c.type = 'reseller' ORDER BY c.parent_id, c.id");
        $clients  = $exdb->queryAllRecords("SELECT c.id, c.parent_id, c.type, c.cr_date, c.cname, c.pname, c.login, c.account_id, a.password, a.type as `pwtype`, c.status, c.phone, c.fax, c.email, c.address, c.city, c.state, c.pcode, c.country, c.locale, c.limits_id, c.params_id, c.perm_id, c.pool_id, c.logo_id, c.tmpl_id, c.guid, c.overuse, c.vendor_id, c.external_id FROM clients as c LEFT JOIN accounts as a ON (a.id = c.account_id) WHERE c.type = 'client' ORDER BY c.parent_id, c.id");
        
        $users = array_merge($admins, $resellers, $clients);
        $msg .= 'read all users (' . count($users) . ')...<br />';
	
        
        $plesk_ispc_ids = array(); // array with key = plesk id, value = ispc id
        
        $phpopts = array('no', 'fast-cgi', 'cgi', 'mod', 'suphp', 'php-fpm');
        
        // import admins / resellers
        for($i = 0; $i < count($users); $i++) {
            $entry = $users[$i];
            
            $old_client = $importer->client_get_by_username($session_id, $entry['login']);
            if($old_client) {
                if($old_client['client_id'] == 0) {
                    $entry['login'] = 'psa_' . $entry['login'];
                    $old_client = $importer->client_get_by_username($session_id, $entry['login']);
                    if($old_client) {
                        $msg .= $entry['login'] . ' existed, updating id ' . $old_client['client_id'] . '<br />';
                    }
                } else {
                    $msg .= $entry['login'] . ' existed, updating id ' . $old_client['client_id'] . '<br />';                    
                }
            }
            
            $params = array(
                            'company_name' => $entry['cname'],
                            'contact_name' => $entry['pname'],
                            //'customer_no' => '',
                            'username' => $entry['login'],
                            'password' => $entry['password'],
                            'language' => substr($entry['locale'], 0, 2), // plesk stores as de-DE or en-US
                            //'usertheme' => '',
                            'street' => $entry['address'],
                            'zip' => $entry['pcode'],
                            'city' => $entry['city'],
                            'state' => $entry['state'],
                            'country' => $entry['country'],
                            'telephone' => $entry['phone'],
                            //'mobile' => $entry[''],
                            'fax' => $entry['fax'],
                            'email' => $entry['email'],
                            //'internet' => $entry[''],
                            //'icq' => $entry[''],
                            //'vat_id' => $entry[''],
                            //'company_id' => $entry[''],
                            //'bank_account_number' => $entry[''],
                            //'bank_code' => $entry[''],
                            //'bank_name' => $entry[''],
                            //'bank_account_iban' => $entry[''],
                            //'bank_account_swift' => $entry[''],
                            'notes' => 'imported from Plesk id ' . $entry['id'],
                            //'template_master' => $entry[''],
                            //'template_additional' => $entry[''],
                            //'default_mailserver' => $entry[''],
                            'limit_maildomain' => get_limit($limits, $entry['id'], 'max_site', -1),
                            'limit_mailbox' => get_limit($limits, $entry['id'], 'max_box', -1),
                            'limit_mailalias' => get_limit($limits, $entry['id'], 'max_mn', -1),
                            'limit_mailaliasdomain' => get_limit($limits, $entry['id'], 'max_dom_aliases', -1),
                            'limit_mailmailinglist' => get_limit($limits, $entry['id'], 'max_maillists', -1),
                            'limit_mailforward' => get_limit($limits, $entry['id'], 'max_mn', -1),
                            'limit_mailcatchall' => 1,
                            'limit_mailrouting' => 0,
                            'limit_mailfilter' => 0,
                            'limit_fetchmail' => 0,
                            'limit_mailquota' => get_limit($limits, $entry['id'], 'mbox_quota', -1),
                            'limit_spamfilter_wblist' => 0,
                            'limit_spamfilter_user' => 0,
                            'limit_spamfilter_policy' => 0,
                            //'default_webserver' => '',
                            'limit_web_domain' => get_limit($limits, $entry['id'], 'max_site', -1),
                            'limit_web_quota' => get_limit($limits, $entry['id'], 'disk_space', -1),
                            'web_php_options' => implode(',', $phpopts),
                            'limit_web_aliasdomain' => get_limit($limits, $entry['id'], 'max_dom_aliases', -1),
                            'limit_web_subdomain' => get_limit($limits, $entry['id'], 'max_subdom', -1),
                            'limit_ftp_user' => (string)($app->functions->intval(get_limit($limits, $entry['id'], 'max_subftp_users', -2)) + 1),
                            'limit_shell_user' => 0,
                            'ssh_chroot' => 'no,jailkit',
                            'limit_webdav_user' => get_limit($limits, $entry['id'], 'max_wu', 0),
                            //'default_dnsserver' => '',
                            'limit_dns_zone' => -1,
                            'limit_dns_slave_zone' => -1,
                            'limit_dns_record' => -1,
                            'limit_client' => ($entry['type'] == 'client' ? 0 : -1),
                            //'default_dbserver' => '',
                            'limit_database' => get_limit($limits, $entry['id'], 'max_db', -1),
                            'limit_cron' => 0,
                            'limit_cron_type' => 'url',
                            'limit_cron_frequency' => '5',
                            'limit_traffic_quota' => get_limit($limits, $entry['id'], 'max_traffic', -1),
                            'limit_openvz_vm' => 0,
                            'limit_openvz_vm_template_id' => ''
                            );
            $reseller_id = 0;
            if($entry['parent_id'] != 0) {
                if(array_key_exists($entry['parent_id'], $plesk_ispc_ids)) {
                    $reseller_id = $plesk_ispc_ids[$entry['parent_id']];
                }
            }
            
            if($old_client) {
                $new_id = $old_client['client_id'];
                $ok = $importer->client_update($session_id, $old_client['client_id'], $reseller_id, $params);
                if($ok === false) {
                    
                }
            } else {
                $new_id = $importer->client_add($session_id, $reseller_id, $params);
            }
            if($new_id === false) {
                //something went wrong here...
                $msg .= "Client " . $entry['id'] . " (" . $entry['pname'] . ") could not be inserted/updated.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
            } else {
                $msg .= "Client " . $entry['id'] . " (" . $entry['pname'] . ") inserted/updated.<br />";
            }
            
            $plesk_ispc_ids[$entry['id']] = $new_id;
        }
        unset($users);
        unset($clients);
        unset($resellers);
        unset($admins);
        
        $web_config = $app->getconf->get_server_config($server_id,'web');
        
        $domains = $exdb->queryAllRecords("SELECT d.id, d.cr_date, d.name, d.displayName, d.dns_zone_id, d.status, d.htype, d.real_size, d.cl_id, d.limits_id, d.params_id, d.guid, d.overuse, d.gl_filter, d.vendor_id, d.webspace_id, d.webspace_status, d.permissions_id, d.external_id FROM domains as d");
        $dom_ftp_users = array();
        $domain_ids = array();
        $domain_roots = array();
        $domain_owners = array();
        $dns_domain_ids = array();
        $maildomain_ids = array();
        foreach($domains as $entry) {
            $res = $exdb->query("SELECT d.dom_id, d.param, d.val FROM dom_param as d WHERE d.dom_id = '" . $entry['id'] . "'");
            $options = array();
            while($opt = $exdb->nextRecord()) {
                $options[$opt['param']] = $opt['val'];
            }
            
            /* TODO: options that might be used later:
             * OveruseBlock true/false
             * OveruseNotify true/false
             * OveruseSuspend true/false
             * wu_script true/false (webusers allowed to use scripts?)
             * webmail string (webmailer used - horde)
             */
            
            $redir_type = '';
            $redir_path = '';
            
            if($entry['htype'] === 'std_fwd') {
                // redirection
                $redir = $exdb->queryOneRecord("SELECT f.dom_id, f.ip_address_id, f.redirect FROM forwarding as f WHERE f.dom_id = '" . $entry['id'] . "'");
                $redir_type = 'R,L';
                $redir_path = $redir['redirect'];
            } elseif($entry['htype'] === 'vrt_hst') {
                // default virtual hosting (vhost)
            } else {
                /* TODO: unknown type */
            }
            
            $hosting = $exdb->queryOneRecord("SELECT h.dom_id, h.sys_user_id, h.ip_address_id, h.real_traffic, h.fp, h.fp_ssl, h.fp_enable, h.fp_adm, h.fp_pass, h.ssi, h.php, h.php_safe_mode, h.cgi, h.perl, h.python, h.fastcgi, h.miva, h.coldfusion, h.asp, h.asp_dot_net, h.ssl, h.webstat, h.same_ssl, h.traffic_bandwidth, h.max_connection, h.php_handler_type, h.www_root, h.maintenance_mode, h.certificate_id, s.login, s.account_id, s.home, s.shell, s.quota, s.mapped_to, a.password, a.type as `pwtype` FROM hosting as h LEFT JOIN sys_users as s ON (s.id = h.sys_user_id) LEFT JOIN accounts as a ON (s.account_id = a.id) WHERE h.dom_id = '" . $entry['id'] . "'");
            if($hosting['sys_user_id']) {
                $dom_ftp_users[] = array('id' => 0,
                                         'dom_id' => $hosting['dom_id'],
                                         'sys_user_id' => $hosting['sys_user_id'],
                                         'login' => $hosting['login'],
                                         'account_id' => $hosting['account_id'],
                                         'home' => $hosting['home'],
                                         'shell' => $hosting['shell'],
                                         'quota' => $hosting['quota'],
                                         'mapped_to' => $hosting['mapped_to'],
                                         'password' => $hosting['password'],
                                         'pwtype' => $hosting['pwtype']
                                        );
            }
            
            $phpmode = 'no';
            if(get_option($hosting, 'php', 'false') === 'true') {
                $mode = get_option($hosting, 'php_handler_type', 'module');
                if($mode === 'module') $phpmode = 'mod';
                else $phpmode = 'fast-cgi';
                /* TODO: what other options could be in "php_handler_type"? */
            }
            
            /* TODO: plesk offers some more options:
             * sys_user_id -> owner of files?
             * ip_address_id - needed?
             * fp - frontpage extensions
             * miva - ?
             * coldfusion
             * asp
             * asp_dot_net
             * traffic_bandwidth
             * max_connections
             */
            
            $params = array(
                            'server_id' => $server_id,
                            'ip_address' => '*',
                            //'ipv6_address' => '',
                            'domain' => $entry['name'],
                            'type' => 'vhost', // can be vhost or alias
                            'parent_domain_id' => '', // only if alias
                            'vhost_type' => 'name', // or ip (-based)
                            'hd_quota' => byte_to_mbyte(get_limit($limits, $entry['id'], 'disk_space', -1)),
                            'traffic_quota' => byte_to_mbyte(get_limit($limits, $entry['id'], 'max_traffic', -1)),
                            'cgi' => yes_no(get_option($hosting, 'cgi', 'false') === 'true' ? 1 : 0),
                            'ssi' => yes_no(get_option($hosting, 'ssi', 'false') === 'true' ? 1 : 0),
                            'suexec' => yes_no(1), // does plesk use this?!
                            'errordocs' => get_option($options, 'apacheErrorDocs', 'false') === 'true' ? 1 : 0,
                            'subdomain' => 'www', // plesk always uses this option
                            'ssl' => yes_no(get_option($hosting, 'ssl', 'false') === 'true' ? 1 : 0),
                            'php' => $phpmode,
                            'fastcgi_php_version' => '', // plesk has no different php versions
                            'ruby' => yes_no(0), // plesk has no ruby support
                            'python' => yes_no(get_option($hosting, 'python', 'false') === 'true' ? 1 : 0),
                            'active' => yes_no(($entry['status'] == 0 && get_option($hosting, 'maintenance_mode', 'false') !== 'true') ? 1 : 0),
                            'redirect_type' => $redir_type,
                            'redirect_path' => $redir_path,
                            'seo_redirect' => '',
                            'ssl_state' => $entry[''],
                            'ssl_locality' => $entry[''],
                            'ssl_organisation' => $entry[''],
                            'ssl_organisation_unit' => $entry[''],
                            'ssl_country' => $entry[''],
                            'ssl_domain' => $entry[''],
                            'ssl_request' => $entry[''],
                            'ssl_cert' => $entry[''],
                            'ssl_bundle' => $entry[''],
                            'ssl_action' => $entry[''],
                            'stats_password' => '',
                            'stats_type' => get_option($hosting, 'webstat', 'webalizer') === 'awstats' ? 'awstats' : 'webalizer',
                            'backup_interval' => 'none',
                            'backup_copies' => 1,
                            'allow_override' => 'All',
                            'pm_process_idle_timeout' => 10,
                            'pm_max_requests' => 0
                            );
            
            // find already inserted domain
            $old_domain = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain = '" . $entry['name'] . "'");
            if($old_domain) {
                $new_id = $old_domain['domain_id'];
                $msg .= "Found domain with id " . $new_id . ", updating it.<br />";
                $params = array_merge($old_domain, $params);
                $ok = $importer->sites_web_domain_update($session_id, $plesk_ispc_ids[$entry['cl_id']], $new_id, $params);
                //if(!$ok) $new_id = false;
            } else {
                $new_id = $importer->sites_web_domain_add($session_id, $plesk_ispc_ids[$entry['cl_id']], $params, true); // read only...
            }
            
            $domain_ids[$entry['id']] = $new_id;
            $domain_roots[$entry['id']] = $entry['www_root'];
            $domain_owners[$entry['id']] = $entry['cl_id'];
            $dns_domain_ids[$entry['dns_zone_id']] = $entry['id'];
            
            if($new_id === false) {
                //something went wrong here...
                $msg .= "Domain " . $entry['id'] . " (" . $entry['name'] . ") could not be inserted.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
            } else {
                $msg .= "Domain" . $entry['id'] . " (" . $entry['name'] . ") inserted.<br />";
            }
            
            // add domain to mail domains too
            $params = array(
                            'server_id' => $server_id,
                            'domain' => $entry['name'],
                            'active' => yes_no(($entry['status'] == 0 ? 1 : 0))
                            );
            $old_domain = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain = '" . $entry['name'] . "'");
            if($old_domain) {
                $new_id = $old_domain['domain_id'];
                $params = array_merge($old_domain, $params);
                $msg .= "Found maildomain with id " . $new_id . ", updating it.<br />";
                $ok = $importer->mail_domain_update($session_id, $plesk_ispc_ids[$entry['cl_id']], $new_id, $params);
                //if(!$ok) $new_id = false;
            } else {
                $new_id = $importer->mail_domain_add($session_id, $plesk_ispc_ids[$entry['cl_id']], $params);
            }
            
            $maildomain_ids[$entry['id']] = $new_id;
            if($new_id === false) {
                //something went wrong here...
                $msg .= "Maildomain " . $entry['id'] . " (" . $entry['name'] . ") could not be inserted.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
            } else {
                $msg .= "Maildomain " . $entry['id'] . " (" . $entry['name'] . ") inserted.<br />";
            }

        }
        
        $domain_aliases = $exdb->queryAllRecords("SELECT da.id, da.name, da.displayName, da.dns, da.mail, da.web, da.dom_id, da.status FROM domainaliases as da");
        foreach($domain_aliases as $entry) {
            $params = array(
                            'server_id' => $server_id,
                            'domain' => $entry['name'],
                            'type' => 'alias',
                            'parent_domain_id' => $domain_ids[$entry['dom_id']],
                            'redirect_type' => '',
                            'redirect_path' => '',
                            'subdomain' => 'www',
                            'active' => yes_no(($entry['status'] == 0 && $entry['web'] === 'true') ? 1 : 0)
                            );
            
            $old_domain = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain = '" . $entry['name'] . "'");
            if($old_domain) {
                $new_id = $old_domain['domain_id'];
                $params = array_merge($old_domain, $params);
                $msg .= "Found domain with id " . $new_id . ", updating it.<br />";
                $ok = $importer->sites_web_aliasdomain_update($session_id, $plesk_ispc_ids[$domain_owners[$entry['dom_id']]], $new_id, $params);
                //if(!$ok) $new_id = false;
            } else {
                $new_id = $importer->sites_web_aliasdomain_add($session_id, $plesk_ispc_ids[$domain_owners[$entry['dom_id']]], $params);
            }
            
            if($new_id === false) {
                //something went wrong here...
                $msg .= "Aliasdomain " . $entry['id'] . " (" . $entry['name'] . ") could not be inserted.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
            } else {
                $msg .= "Aliasdomain " . $entry['id'] . " (" . $entry['name'] . ") inserted.<br />";
            }
            
            // add alias to mail domains, too
            $params = array(
                        'server_id' => $server_id,
                        'domain' => $entry['name'],
                        'active' => yes_no(($entry['status'] == 0 && $entry['mail'] === 'true') ? 1 : 0)
                        );

            $old_domain = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain = '" . $entry['name'] . "'");
            if($old_domain) {
                $new_id = $old_domain['domain_id'];
                $params = array_merge($old_domain, $params);
                $msg .= "Found mail domain with id " . $new_id . ", updating it.<br />";
                $ok = $importer->sites_web_aliasdomain_update($session_id, $plesk_ispc_ids[$domain_owners[$entry['dom_id']]], $new_id, $params);
                //if(!$ok) $new_id = false;
            } else {
                $new_id = $importer->mail_domain_add($session_id, $plesk_ispc_ids[$domain_owners[$entry['dom_id']]], $params);
            }
            
            $maildomain_ids[$entry['id']] = $new_id;
            if($new_id === false) {
                //something went wrong here...
                $msg .= "Aliasmaildomain " . $entry['id'] . " (" . $entry['name'] . ") could not be inserted.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
            } else {
                $msg .= "Aliasmaildomain " . $entry['id'] . " (" . $entry['name'] . ") inserted.<br />";
            }
        }
        
        // subdomains in plesk are real vhosts, so we have to treat them as vhostsubdomains
        $subdomains = $exdb->queryAllRecords("SELECT d.id, d.dom_id, d.name, d.displayName, d.sys_user_id, d.ssi, d.php, d.cgi, d.perl, d.python, d.fastcgi, d.miva, d.coldfusion, d.asp, d.asp_dot_net, d.ssl, d.same_ssl, d.php_handler_type, d.www_root, d.maintenance_mode, d.certificate_id FROM subdomains as d");
        $subdomain_ids = array();
        $subdomain_roots = array();
        $subdomain_owners = array();
        foreach($subdomains as $entry) {
            $res = $exdb->query("SELECT d.dom_id, d.param, d.val FROM dom_param as d WHERE d.dom_id = '" . $entry['dom_id'] . "'");
            $options = array();
            while($opt = $exdb->nextRecord()) {
                $options[$opt['param']] = $opt['val'];
            }
            
            $parent_domain = $exdb->queryOneRecord("SELECT d.id, d.cl_id, d.name FROM domains as d WHERE d.id = '" . $entry['dom_id'] . "'");
            
            /* TODO: options that might be used later:
             * OveruseBlock true/false
             * OveruseNotify true/false
             * OveruseSuspend true/false
             * wu_script true/false (webusers allowed to use scripts?)
             * webmail string (webmailer used - horde)
             */
            
            $redir_type = '';
            $redir_path = '';
            
            if($entry['htype'] === 'std_fwd') {
                // redirection
                $redir = $exdb->queryOneRecord("SELECT f.dom_id, f.ip_address_id, f.redirect FROM forwarding as f WHERE f.dom_id = '" . $entry['id'] . "'");
                $redir_type = 'R,L';
                $redir_path = $redir['redirect'];
            } elseif($entry['htype'] === 'vrt_hst') {
                // default virtual hosting (vhost)
            } else {
                /* TODO: unknown type */
            }
            
            $hosting = $exdb->queryOneRecord("SELECT h.dom_id, h.sys_user_id, h.ip_address_id, h.real_traffic, h.fp, h.fp_ssl, h.fp_enable, h.fp_adm, h.fp_pass, h.ssi, h.php, h.php_safe_mode, h.cgi, h.perl, h.python, h.fastcgi, h.miva, h.coldfusion, h.asp, h.asp_dot_net, h.ssl, h.webstat, h.same_ssl, h.traffic_bandwidth, h.max_connection, h.php_handler_type, h.www_root, h.maintenance_mode, h.certificate_id FROM hosting as h WHERE h.dom_id = '" . $entry['dom_id'] . "'");
            $hosting = array_merge($hosting, $entry); //settings from subdomain override parent settings
            
            $phpmode = 'no';
            if(get_option($hosting, 'php', 'false') === 'true') {
                $mode = get_option($hosting, 'php_handler_type', 'module');
                if($mode === 'module') $phpmode = 'mod';
                else $phpmode = 'fast-cgi';
                /* TODO: what other options could be in "php_handler_type"? */
            }
            /* TODO: plesk offers some more options:
             * sys_user_id -> owner of files?
             * ip_address_id - needed?
             * fp - frontpage extensions
             * miva - ?
             * coldfusion
             * asp
             * asp_dot_net
             * traffic_bandwidth
             * max_connections
             */
            
            $params = array(
                            'server_id' => $server_id,
                            'ip_address' => '*',
                            //'ipv6_address' => '',
                            'domain' => $entry['name'] . '.' . $parent_domain['name'],
                            'type' => 'vhost', // can be vhost or alias
                            'parent_domain_id' => $domain_ids[$entry['dom_id']],
                            'vhost_type' => 'name', // or ip (-based)
                            'hd_quota' => byte_to_mbyte(get_limit($limits, $entry['dom_id'], 'disk_space', -1)),
                            'traffic_quota' => byte_to_mbyte(get_limit($limits, $entry['dom_id'], 'max_traffic', -1)),
                            'cgi' => yes_no(get_option($hosting, 'cgi', 'false') === 'true' ? 1 : 0),
                            'ssi' => yes_no(get_option($hosting, 'ssi', 'false') === 'true' ? 1 : 0),
                            'suexec' => yes_no(1), // does plesk use this?!
                            'errordocs' => get_option($options, 'apacheErrorDocs', 'false') === 'true' ? 1 : 0,
                            'subdomain' => 'www', // plesk always uses this option
                            'ssl' => yes_no(get_option($hosting, 'ssl', 'false') === 'true' ? 1 : 0),
                            'php' => $phpmode,
                            'fastcgi_php_version' => '', // plesk has no different php versions
                            'ruby' => yes_no(0), // plesk has no ruby support
                            'python' => yes_no(get_option($hosting, 'python', 'false') === 'true' ? 1 : 0),
                            'active' => yes_no(($entry['status'] == 0 && get_option($hosting, 'maintenance_mode', 'false') !== 'true') ? 1 : 0),
                            'redirect_type' => $redir_type,
                            'redirect_path' => $redir_path,
                            'seo_redirect' => '',
                            'ssl_state' => $entry[''],
                            'ssl_locality' => $entry[''],
                            'ssl_organisation' => $entry[''],
                            'ssl_organisation_unit' => $entry[''],
                            'ssl_country' => $entry[''],
                            'ssl_domain' => $entry[''],
                            'ssl_request' => $entry[''],
                            'ssl_cert' => $entry[''],
                            'ssl_bundle' => $entry[''],
                            'ssl_action' => $entry[''],
                            'stats_password' => '',
                            'stats_type' => get_option($hosting, 'webstat', 'webalizer') === 'awstats' ? 'awstats' : 'webalizer',
                            'backup_interval' => 'none',
                            'backup_copies' => 1,
                            'allow_override' => 'All',
                            'pm_process_idle_timeout' => 10,
                            'pm_max_requests' => 0
                            );

            $old_domain = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain = '" . $entry['name'] . '.' . $parent_domain['name'] . "'");
            if($old_domain) {
                $new_id = $old_domain['domain_id'];
                $params = array_merge($old_domain, $params);
                $msg .= "Found domain with id " . $new_id . ", updating it.<br />";
                $ok = $importer->sites_web_vhost_subdomain_update($session_id, $plesk_ispc_ids[$parent_domain['cl_id']], $new_id, $params);
                //if(!$ok) $new_id = false;
            } else {
                $new_id = $importer->sites_web_vhost_subdomain_add($session_id, $plesk_ispc_ids[$parent_domain['cl_id']], $params, true); // read only...
            }
            
            $subdomain_ids[$entry['id']] = $new_id;
            $subdomain_roots[$entry['id']] = $entry['www_root'];
            $subdomain_owners[$entry['id']] = $entry['cl_id'];
            if($new_id === false) {
                //something went wrong here...
                $msg .= "Subdomain " . $entry['id'] . " (" . $entry['name'] . ") could not be inserted.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
            } else {
                $msg .= "Subdomain " . $entry['id'] . " (" . $entry['name'] . ") inserted.<br />";
            }
        }
        
        // dns have to be done AFTER domains due to missing client info
        
        $dns_zone_ids = array();
        $dns_zone_serials = array();
        $dns_zones = $exdb->queryAllRecords("SELECT d.id, d.name, d.displayName, d.status, d.email, d.type, d.ttl, d.ttl_unit, d.refresh, d.refresh_unit, d.retry, d.retry_unit, d.expire, d.expire_unit, d.minimum, d.minimum_unit, d.serial_format, d.serial FROM dns_zone as d");
        foreach($dns_zones as $entry) {
            $ns = $exdb->queryOneRecord("SELECT d.id, d.val FROM dns_recs as d WHERE d.dns_zone_id = '" . $entry['id'] . "' AND d.type = 'NS'");
            if(!$ns) $ns = array('id' => 0, 'val' => 'ns.' . $entry['name']);
            
            $dom_id = $dns_domain_ids[$entry['id']];
            $client_id = $plesk_ispc_ids[$domain_owners[$entry['dom_id']]];
            if(!$client_id) $client_id = 0;
            
            $params = array(
                            'server_id' => $server_id,
                            'origin' => add_dot($entry['name']), // what to put here?
                            'ns' => add_dot($ns['val']), // what to put here?
                            'mbox' => str_replace('@', '.', add_dot($entry['email'])),
                            'serial' => $entry['serial'],
                            'refresh' => $entry['refresh'],
                            'retry' => $entry['retry'],
                            'expire' => $entry['expire'],
                            'minimum' => $entry['minimum'],
                            'ttl' => $entry['ttl'],
                            'xfer' => '',
                            'also_notify' => '',
                            'update_acl' => '',
                            'active' => yes_no(($entry['status'] == 0 ? 1 : 0))
                            );
            
            $old_dns = $app->db->queryOneRecord("SELECT id FROM dns_soa WHERE origin = '" . add_dot($entry['name']) . "'");
            if($old_dns) $old_id = $old_dns['id'];
            if($old_id) {
                $new_id = $old_id;
                $ok = $importer->dns_zone_update($session_id, $client_id, $old_id, $params);
                //if(!$ok) {
                //    $msg .= "DNS " . $entry['id'] . " (" . $entry['name'] . ") could not be updated.<br />";
                //    $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
                //} else {
                    $msg .= "DNS " . $entry['id'] . " (" . $entry['name'] . ") updated.<br />";
                //}
            } else {
                $new_id = $importer->dns_zone_add($session_id, $client_id, $params);
                if($new_id === false) {
                    //something went wrong here...
                    $msg .= "DNS " . $entry['id'] . " (" . $entry['name'] . ") could not be inserted.<br />";
                    $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
                } else {
                    $msg .= "DNS " . $entry['id'] . " (" . $entry['name'] . ") inserted.<br />";
                }
            }
            $dns_zone_ids[$entry['id']] = $new_id;
            $dns_zone_serials[$entry['id']] = $entry['serial'];
        }
        unset($dns_zones);
        
        /* types:
         * PTR, NS, A, CNAME, MX, TXT, AAAA
         */
        $dns_records = $exdb->queryAllRecords("SELECT d.id, d.dns_zone_id, d.type, d.displayHost, d.host, d.displayVal, d.val, d.opt, d.time_stamp FROM dns_recs as d");
        foreach($dns_records as $entry) {
            $dns_id = (array_key_exists($entry['dns_zone_id'], $dns_zone_ids) ? $dns_zone_ids[$entry['dns_zone_id']] : 0);
            if(!$dns_id) {
                // entry for missing dns zone...?
                continue;
            }
            
            $dom_id = $dns_domain_ids[$entry['dns_zone_id']];
            $client_id = $plesk_ispc_ids[$domain_owners[$entry['dom_id']]];
            if(!$client_id) $client_id = 0;
            
            $params = array(
                        'server_id' => $server_id,
                        'zone' => $dns_id,
                        'name' => add_dot($entry['host']),
                        'type' => $entry['type'],
                        'data' => $entry['val'],
                        //'ttl' => '',
                        'active' => yes_no(1),
                        'stamp' => $entry['time_stamp'],
                        //'serial' => $dns_zone_serials[$entry['id']]
                        );
            
            
            $record = $app->db->queryOneRecord("SELECT id FROM dns_rr WHERE zone = '" . $dns_zone_ids[$entry['dns_zone_id']] . "' AND name = '" . add_dot($entry['host']) . "' AND type = '" . $entry['type'] . "'");
            $old_id = 0;
            if($record) {
                $old_id = $record['id'];
            }
            
            $new_id = false;
            if($entry['type'] === 'MX') {
                $params['aux'] = $entry['opt'];
                if($old_id) {
                    $ok = $importer->dns_mx_update($session_id, $client_id, $old_id, $params);
                    if($ok) $new_id = $old_id;
                } else {
                    $new_id = $importer->dns_mx_add($session_id, $client_id, $params);
                }
            } elseif($entry['type'] === 'PTR') {
                if($old_id) {
                    $ok = $importer->dns_ptr_update($session_id, $client_id, $old_id, $params);
                    if($ok) $new_id = $old_id;
                } else {
                    $new_id = $importer->dns_ptr_add($session_id, $client_id, $params);
                }
            } elseif($entry['type'] === 'A') {
                if($old_id) {
                    $ok = $importer->dns_a_update($session_id, $client_id, $old_id, $params);
                    if($ok) $new_id = $old_id;
                } else {
                    $new_id = $importer->dns_a_add($session_id, $client_id, $params);
                }
            } elseif($entry['type'] === 'AAAA') {
                if($old_id) {
                    $ok = $importer->dns_aaaa_update($session_id, $client_id, $old_id, $params);
                    if($ok) $new_id = $old_id;
                } else {
                    $new_id = $importer->dns_aaaa_add($session_id, $client_id, $params);
                }
            } elseif($entry['type'] === 'TXT') {
                if($old_id) {
                    $ok = $importer->dns_txt_update($session_id, $client_id, $old_id, $params);
                    if($ok) $new_id = $old_id;
                } else {
                    $new_id = $importer->dns_txt_add($session_id, $client_id, $params);
                }
            } elseif($entry['type'] === 'CNAME') {
                if($old_id) {
                    $ok = $importer->dns_cname_update($session_id, $client_id, $old_id, $params);
                    if($ok) $new_id = $old_id;
                } else {
                    $new_id = $importer->dns_cname_add($session_id, $client_id, $params);
                }
            } elseif($entry['type'] === 'NS') {
                if($old_id) {
                    $ok = $importer->dns_ns_update($session_id, $client_id, $old_id, $params);
                    if($ok) $new_id = $old_id;
                } else {
                    $new_id = $importer->dns_ns_add($session_id, $client_id, $params);
                }
            }
            if($new_id === false) {
                //something went wrong here...
                $msg .= "DNS " . $entry['id'] . " (" . $entry['name'] . ") could not be inserted/updated.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
            } else {
                $msg .= "DNS " . $entry['id'] . " (" . $entry['name'] . ") inserted/updated.<br />";
            }
            
        }
        unset($dns_records);
        
        
        $folder_ids = array();
        /* web_folder creation*/
        $protected_dirs = $exdb->queryAllRecords("SELECT id, non_ssl, ssl, cgi_bin, realm, path, dom_id FROM protected_dirs");
        foreach($protected_dirs as $entry) {
            $params = array('server_id' => $server_id,
                            'parent_domain_id' => $domain_ids[$entry['dom_id']],
                            'path' => $entry['path'],
                            'active' => 'y');
            $folder_id = 0;
            $check = $app->db->queryOneRecord('SELECT * FROM `web_folder` WHERE `parent_domain_id` = \'' . $domain_ids[$entry['dom_id']] . '\' AND `path` = \'' . $app->db->quote($entry['path']));
            if($check) {
                $importer->sites_web_folder_update($session_id, $client_id, $check['web_folder_id'], array_merge($check, $params));
                $folder_id = $check['web_folder_id'];
            } else {
                $folder_id = $importer->sites_web_folder_add($session_id, $client_id, $params);
            }
            
            $msg .= 'Created / updated HTTP AUTH folder: ' . $entry['path'] . '<br />';
            $folder_ids[$entry['id']] = $folder_id;
        }
        
        $pd_users = $exdb->queryAllRecords("SELECT u.id, u.login, u.account_id, u.pd_id, a.password FROM pd_users as u INNER JOIN accounts as a ON (a.id = u.account_id)");
        foreach($protected_dirs as $entry) {
            $params = array('server_id' => $server_id,
                            'web_folder_id' => $folder_ids[$entry['id']],
                            'username' => $entry['login'],
                            'password' => $entry['password'],
                            'active' => 'y');
            
            $check = $app->db->queryOneRecord('SELECT * FROM `web_folder_user` WHERE `web_folder_id` = ? AND `username` = ?', $folder_id, $entry['login']);
            if($check) {
                if($dry_run == false) $importer->sites_web_folder_user_update($session_id, $client_id, $check['web_folder_user_id'], array_merge($check, $params));
            } else {
                if($dry_run == false) $importer->sites_web_folder_user_add($session_id, $client_id, $params);
            }
        }
        
        /*$web_users = $exdb->queryAllRecords("SELECT id, dom_id, sys_user_id, ssi, php, cgi, perl, python, fastcgi, asp, asp_dot_net FROM web_users");
        foreach($web_users as $entry) {
            $params = 
        }
        */
        
        
        $ftp_users = $exdb->queryAllRecords("SELECT f.id, f.dom_id, f.sys_user_id, s.login, s.account_id, s.home, s.shell, s.quota, s.mapped_to, a.password, a.type as `pwtype` FROM ftp_users as f INNER JOIN sys_users as s ON (s.id = f.sys_user_id) INNER JOIN accounts as a ON (a.id = s.account_id)");
        $ftp_users = array_merge($ftp_users, $dom_ftp_users);
        foreach($ftp_users as $entry) {
            $parent_domain = $exdb->queryOneRecord("SELECT d.id, d.cl_id, d.name FROM domains as d WHERE d.id = '" . $entry['dom_id'] . "'");
            
            $ispc_dom_id = $domain_ids[$entry['dom_id']];
            $client_id = $plesk_ispc_ids[$domain_owners[$entry['dom_id']]];
            if(!$client_id) $client_id = 0;
            
            $document_root = str_replace("[website_id]",$ispc_dom_id,$web_config["website_path"]);
            $document_root = str_replace("[website_idhash_1]",id_hash($ispc_dom_id,1),$document_root);
            $document_root = str_replace("[website_idhash_2]",id_hash($ispc_dom_id,1),$document_root);
            $document_root = str_replace("[website_idhash_3]",id_hash($ispc_dom_id,1),$document_root);
            $document_root = str_replace("[website_idhash_4]",id_hash($ispc_dom_id,1),$document_root);

            // Set the values for document_root, system_user and system_group
            $system_user = 'web'.$ispc_dom_id;
            $system_group = 'client'.$client_id;
            $document_root = str_replace("[client_id]",$client_id,$document_root);
            $document_root = str_replace("[client_idhash_1]",id_hash($client_id,1),$document_root);
            $document_root = str_replace("[client_idhash_2]",id_hash($client_id,2),$document_root);
            $document_root = str_replace("[client_idhash_3]",id_hash($client_id,3),$document_root);
            $document_root = str_replace("[client_idhash_4]",id_hash($client_id,4),$document_root);
            
            $uid = $system_user;
            $gid = $system_group;
            
            $sys_grp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = '" . $client_id . "'");
            if(!$sys_grp) $sys_grp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = 0");
            
            if(!$sys_grp) $sys_groupid = 1;
            else $sys_groupid = $sys_grp['groupid'];
            
            $params = array(
                            'server_id' => $server_id,
                            'parent_domain_id' => $domain_ids[$entry['dom_id']],
                            'username' => $entry['login'],
                            'password' => $entry['password'],
                            'quota_size' => byte_to_mbyte(($entry['quota'] == 0 ? -1 : $entry['quota'])),
                            'active' => yes_no(1),
                            'uid' => $uid,
                            'gid' => $gid,
                            'dir' => $document_root,
                            'sys_groupid' => $sys_groupid
                            //'quota_files' => $entry[''],
                            //'ul_ratio' => $entry[''],
                            //'dl_ratio' => $entry[''],
                            //'ul_bandwidth' => $entry[''],
                            //'dl_bandwidth' => $entry['']
                            );
            $new_id = false;
            $old_ftp = $app->db->queryOneRecord("SELECT ftp_user_id, parent_domain_id FROM ftp_user WHERE username = '" . $entry['login'] ."'");
            if($old_ftp) {
                if($old_ftp['parent_domain_id'] != $domain_ids[$entry['dom_id']]) {
                    $msg .= "FTP Account conflicts with other domain!<br />";
                } else {
                    $new_id = $old_ftp['ftp_user_id'];
                    $ok = $importer->sites_ftp_user_update($session_id, $client_id, $new_id, $params);
                    //if(!$ok) $new_id = false;
                }
            } else {
                $new_id = $importer->sites_ftp_user_add($session_id, $client_id, $params);
            }
            if($new_id === false) {
                //something went wrong here...
                $msg .= "FTP " . $entry['id'] . " (" . $entry['login'] . ") could not be inserted.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
                $msg .= "Params: " . var_export($params, true) . "<br />";
            } else {
                $msg .= "FTP Account " . $entry['id'] . " (" . $entry['login'] . ") inserted.<br />";
            }
        }
        
        $mail_config = $app->getconf->get_server_config($server_id, 'mail');
        
        $mail_addresses = $exdb->queryAllRecords("SELECT m.id, m.mail_name, m.perm_id, m.postbox, m.account_id, m.redirect, m.redir_addr, m.mail_group, m.autoresponder, m.spamfilter, m.virusfilter, m.mbox_quota, m.dom_id, m.userId, a.password, a.type as `pwtype` FROM mail as m LEFT JOIN accounts as a ON (a.id = m.account_id) ");
        $mail_ids = array();
        foreach($mail_addresses as $entry) {
            
            $parent_domain = $exdb->queryOneRecord("SELECT d.id, d.cl_id, d.name FROM domains as d WHERE d.id = '" . $entry['dom_id'] . "'");
            if(!$parent_domain) {
                $msg .= "Could not insert/update mail address " . $entry['mail_name'] . " as domain is missing.<br />";
                continue;
            }
            
            /* postbox true/false
             * mail_group true/false
             * spamfilter true/false
             */
            
            
            $has_responder = false;
            if($entry['autoresponder'] === 'true') {
                $responder = $exdb->queryOneRecord("SELECT id, mn_id, resp_name, keystr, key_where, subject, reply_to, content_type, charset, text, resp_on, ans_freq, mem_limit FROM mail_resp WHERE mn_id = '" . $entry['id'] . "'");
                if($responder) $has_responder = true;
            }
            
            $maildir = str_replace("[domain]",$parent_domain["name"],$mail_config["maildir_path"]);
			$maildir = str_replace("[localpart]",strtolower($entry["mail_name"]),$maildir);
            
            
            $params = array(
                            'server_id' => $server_id,
                            'email' => $entry['mail_name'] . "@" . $parent_domain['name'],
                            'login' => strtolower($entry['mail_name'] . "@" . $parent_domain['name']),
                            'password' => $entry['password'],
                            'name' => $entry[''],
                            'quota' => ($entry['mbox_quota'] == -1 ? 0 : $entry['mbox_quota']), // in bytes!
                            'cc' => $entry['redir_addr'],
                            'maildir' => $maildir,
                            'homedir' => $mail_config["homedir_path"],
                            'uid' => $mail_config["mailuser_uid"],
                            'gid' => $mail_config["mailuser_gid"],
                            'postfix' => yes_no(1),
                            'disableimap' => yes_no(0),
                            'disablepop3' => yes_no(0),
                            'autoresponder_subject' => ($has_responder ? $responder['subject'] : ''),
                            'autoresponder_text' => ($has_responder ? $responder['text'] : ''),
                            'autoresponder' => yes_no($has_responder ? 1 : 0),
                            'autoresponder_start_date' => ($has_responder && $responder['resp_on'] === 'true' ? strftime('%Y-%m-%d', time()) : strftime('%Y-%m-%d', time() - (3600*24))),
                            'autoresponder_end_date' => ($has_responder && $responder['resp_on'] === 'true' ? strftime('%Y-%m-%d', time() + (3600*24*365)) : strftime('%Y-%m-%d', time())),
                            'move_junk' => yes_no(0)
                            );
            $client_id = $plesk_ispc_ids[$domain_owners[$entry['dom_id']]];
            
            // if this is no postbox we do not need to create a mailuser
            if($entry['postbox'] !== 'false') {
                $old_mail = $app->db->queryOneRecord("SELECT mailuser_id FROM mail_user WHERE email = '" . $entry['mail_name'] . "@" . $parent_domain['name'] . "'");
                if($old_mail) {
                    $new_id = $old_mail['mailuser_id'];
                    $ok = $importer->mail_user_update($session_id, $client_id, $new_id, $params);
                    //if(!$ok) $new_id = false;
                } else {
                    $new_id = $importer->mail_user_add($session_id, $client_id, $params);
                }

                if($new_id === false) {
                    //something went wrong here...
                    $msg .= "Mail" . $entry['id'] . " (" . $entry['mail_name'] . "@" . $parent_domain['name'] . ") could not be inserted/updated.<br />";
                    $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
                } else {
                    $msg .= "Mail " . $entry['id'] . " (" . $entry['mail_name'] . "@" . $parent_domain['name'] . ") inserted/updated.<br />";
                }
                $mail_ids[$entry['id']] = $new_id;
            }
            
            // select all redirs for this address
            $mail_redir = $exdb->queryAllRecords("SELECT id, mn_id, address FROM mail_redir WHERE mn_id = '" . $entry['id'] . "'");
            foreach($mail_redir as $redir) {
                $params = array(
                                'server_id' => $server_id,
                                'source' => $entry['mail_name'] . "@" . $parent_domain['name'],
                                'destination' => $redir['address'],
                                'type' => 'forward', // or forward
                                'active' => yes_no(1)
                                );
                
                $old_mail = $app->db->queryOneRecord("SELECT forwarding_id FROM mail_forwarding WHERE source = '" . $entry['mail_name'] . "@" . $parent_domain['name'] . "' AND destination = '" . $redir['address'] . "'");
                if($old_mail) {
                    $new_id = $old_mail['forwarding_id'];
                    $ok = $importer->mail_forward_update($session_id, $client_id, $new_id, $params);
                    //if(!$ok) $new_id = false;
                } else {
                    $new_id = $importer->mail_forward_add($session_id, $client_id, $params);
                }

                if($new_id === false) {
                    //something went wrong here...
                    $msg .= "Mail redirect " . $entry['id'] . " (" . $entry['mail_name'] . "@" . $parent_domain['name'] . " to " . $redir['address'] . ") could not be inserted/updated.<br />";
                    $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
                } else {
                    $msg .= "Mail redirect " . $entry['id'] . " (" . $entry['mail_name'] . "@" . $parent_domain['name'] . " to " . $redir['address'] . ") inserted/updated.<br />";
                }
            }
            unset($mail_redir);
        }
        unset($mail_addresses);
        
        $mail_aliases = $exdb->queryAllRecords("SELECT a.id, a.mn_id, a.alias, m.dom_id, m.mail_name FROM mail_aliases as a INNER JOIN mail as m ON (m.id = a.mn_id)");
        foreach($mail_aliases as $entry) {
            
            $parent_domain = $exdb->queryOneRecord("SELECT d.id, d.cl_id, d.name FROM domains as d WHERE d.id = '" . $entry['dom_id'] . "'");
            if(!$parent_domain) {
                $msg .= "Could not insert/update mail alias " . $entry['alias'] . " as domain is missing.<br />";
                continue;
            }
            
            $params = array(
                            'server_id' => $server_id,
                            'source' => $entry['alias'] . "@" . $parent_domain['name'],
                            'destination' => $entry['mail_name'] . "@" . $parent_domain['name'],
                            'type' => 'alias', // or forward
                            'active' => yes_no(1)
                            );
            $client_id = $plesk_ispc_ids[$domain_owners[$entry['dom_id']]];
        
            $old_mail = $app->db->queryOneRecord("SELECT forwarding_id FROM mail_forwarding WHERE source = '" . $entry['alias'] . "@" . $parent_domain['name'] . "' AND destination = '" . $entry['mail_name'] . "@" . $parent_domain['name'] . "'");
            if($old_mail) {
                $new_id = $old_mail['forwarding_id'];
                $ok = $importer->mail_alias_update($session_id, $client_id, $new_id, $params);
                //if(!$ok) $new_id = false;
            } else {
                $new_id = $importer->mail_alias_add($session_id, $client_id, $params);
            }
            
            if($new_id === false) {
                //something went wrong here...
                $msg .= "Mail alias " . $entry['id'] . " (" . $entry['alias'] . "@" . $parent_domain['name'] . ") could not be inserted/updated.<br />";
                $msg .= "&nbsp; Error: " . $importer->getFault() . "<br />";
            } else {
                $msg .= "Mail alias " . $entry['id'] . " (" . $entry['alias'] . "@" . $parent_domain['name'] . ") inserted/updated.<br />";
            }
        }
        unset($mail_aliases);
        
        //spamfilter // preferences = true/false, username = email address, can be *@*
        //id, username, preferences
        
        //spamfilter_preferences
        //prefid, spamfilter_id, preference, value
        
        
        
        //$client_traffic = $exdb->queryAllRecords("SELECT t.cl_id, t.date, t.http_in, t.http_out, t.ftp_in, t.ftp_out, t.smtp_in, t.smtp_out, t.pop3_imap_in, t.pop3_imap_out FROM ClientsTraffic as t");
        
        $db_userids = array();
        
        $db_users  = $exdb->queryAllRecords("SELECT u.id, u.login, u.account_id, u.db_id, a.password, a.type as `pwtype` FROM db_users as u LEFT JOIN accounts as a ON (a.id = u.account_id)");
        foreach($db_users as $db_user) {
            // database user
            $params = array('server_id' => $server_id,
                            'database_user' => $db_user['login'],
                            'database_password' => $db_user['password']);
            $check = $app->db->queryOneRecord('SELECT * FROM `web_database_user` WHERE `database_user` = \'' . $app->db->quote($db_user['login']) . '\'');
            $db_user_id = 0;
            if($check) {
                $importer->sites_database_user_update($session_id, $client_id, $check['database_user_id'], array_merge($check, $params));
                $db_user_id = $check['database_user_id'];
            } else {
                $db_user_id = $api->sites_database_user_add($session_id, $client_id, $params);
            }
            
            if(!isset($db_userids[$db_user['db_id']])) $db_userids[$db_user['db_id']] = $db_user_id;
            print 'Created / updated database user: ' . $db_user['login'] . NL;
        }
            
        $databases  = $exdb->queryAllRecords("SELECT d.id, d.name, d.type, d.dom_id, d.db_server_id, d.default_user_id FROM databases as d");
        foreach($databases as $database) {
            $params = array('server_id' => $server_id,
                            'parent_domain_id' => $domain_ids[$database['dom_id']],
                            'type' => 'mysql',
                            'database_name' => $database['name'],
                            'database_user_id' => $db_userids[$database['id']],
                            'database_ro_user_id' => 0,
                            'database_charset' => 'utf8',
                            'remote_access' => 'n',
                            'active' => 'y',
                            'remote_ips' => '');
            
            $check = $app->db->queryOneRecord('SELECT * FROM `web_database` WHERE `database_name` = \'' . $app->db->quote($database['name']) . '\'');
            if($check) {
                $importer->sites_database_update($session_id, $client_id, $check['database_id'], array_merge($check, $params));
            } else {
                $importer->sites_database_add($session_id, $client_id, $params);
            }
            
            print 'Created / updated database: ' . $database['name'] . NL;
         }
        
        // do we need table disk_usage for import? i think we don't
        
        // name is domain name, displayName is including "Umlaute"
        //$anon_ftp = $exdb->queryAllRecords("SELECT f.id, f.dom_id, f.max_conn, f.bandwidth, f.incoming, f.incoming_readable, f.incoming_subdirs, f.status, f.quota, f.display_login, f.login_text FROM anon_ftp as f");
        
        
        //DomainServices
        //id, dom_id, type, status, parameters_id, ipCollectionId
        
        //DomainsTraffic
        //dom_id, date, http_in, http_out, ftp_in, ftp_out, smtp_in, smtp_out, pop3_imap_in, pop3_imap_out
        
        
        //IP_Adresses
        //id, ip_address, mask, iface, ssl_certificate_id, default_domain_id, ftps, main, status
        
        //ip_pool
        //id, ip_address_id, type

        /* TODO:
                */
        //misc // needed? global settings
        //param, val
        
        //Permissions
        //id, permission, value
        
        //smb_users // pass is base64 encoded plaintext
        //id, login, password, contactName, email, companyName, phone, fax, address, city, state, zip, country, creationDate, isBuiltIn, roleId, uuid, isLocked, authCookie, sessionId, externalId, ownerId, isDomainAdmin, additionalInfo, imNumber, imType, isLegacyUser
        
        /* TODO: 
        sys_users // mapped_to = parent_id
        id, login, account_id, home, shell, quota, mapped_to
        
         */
	} else {
        $msg .= 'Connecting to external database failed!<br />';
        $msg .= $exdb->connect_error;
        $msg .= substr($exdb->errorMessage, 0, 25);
	
		$error .= $exdb->errorMessage;
	}
	
	//* restore db login details
	/*$conf['db_host'] = $conf_bak['db_host'];
	$conf['db_database'] = $conf_bak['db_database'];
	$conf['db_user'] = $conf_bak['db_user'];
	$conf['db_password'] = $conf_bak['db_password'];*/
	
}

$app->tpl->setVar('msg',$msg);
$app->tpl->setVar('error',$error);


$app->tpl_defaults();
$app->tpl->pparse();


?>