<?php

/*
Copyright (c) 2015 Michael Fürmann, Spicy Web (spicyweb.de)
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

class xmpp_plugin {

    var $plugin_name = 'xmpp_server_plugin';
    var $class_name = 'xmpp_server_plugin';

    var $xmpp_config_dir = '/etc/metronome';

    var $ssl_certificate_changed = false;
    var $ssl_certificate_deleted = false;


    //* This function is called during ispconfig installation to determine
    //  if a symlink shall be created for this plugin.
    function onInstall() {
        global $conf;

        if($conf['services']['xmpp'] == true) {
            return true;
        } else {
            return false;
        }

    }

    /*
         This function is called when the plugin is loaded
    */

    function onLoad() {
        global $app;

        /*
        Register for the events
        */

        $app->plugins->registerEvent('server_insert', 'xmpp_plugin', 'insert');
        $app->plugins->registerEvent('server_update', 'xmpp_plugin', 'update');

        $app->plugins->registerEvent('xmpp_domain_insert', 'xmpp_plugin', 'ssl');
        $app->plugins->registerEvent('xmpp_domain_update', 'xmpp_plugin', 'ssl');
        $app->plugins->registerEvent('xmpp_domain_delete', 'xmpp_plugin', 'ssl');

        $app->plugins->registerEvent('xmpp_domain_insert', 'xmpp_plugin', 'domainInsert');
        $app->plugins->registerEvent('xmpp_domain_update', 'xmpp_plugin', 'domainUpdate');
        $app->plugins->registerEvent('xmpp_domain_delete', 'xmpp_plugin', 'domainDelete');
        $app->plugins->registerEvent('xmpp_user_insert', 'xmpp_plugin', 'userInsert');
        $app->plugins->registerEvent('xmpp_user_update', 'xmpp_plugin', 'userUpdate');
        $app->plugins->registerEvent('xmpp_user_delete', 'xmpp_plugin', 'userDelete');

    }

    function insert($event_name, $data) {
        global $app, $conf;

        $this->update($event_name, $data);

    }

    // The purpose of this plugin is to rewrite the main.cf file
    function update($event_name, $data) {
        global $app, $conf;

        // get the config
        $app->uses("getconf,system,tpl");


        $old_ini_data = $app->ini_parser->parse_ini_string($data['old']['config']);
        $xmpp_config = $app->getconf->get_server_config($conf['server_id'], 'xmpp');

        // Global server config
        $tpl = new tpl();
        $tpl->newTemplate('metronome_conf_global.master');
        $tpl->setVar('ipv6', $xmpp_config['xmpp_use_ipv6']=='y'?'true':'false');
        $tpl->setVar('bosh_timeout', intval($xmpp_config['xmpp_bosh_max_inactivity']));
        $tpl->setVar('port_http', intval($xmpp_config['xmpp_port_http']));
        $tpl->setVar('port_https', intval($xmpp_config['xmpp_port_https']));
        $tpl->setVar('port_pastebin', intval($xmpp_config['xmpp_port_pastebin']));
        $tpl->setVar('port_bosh', intval($xmpp_config['xmpp_port_bosh']));
        // Global server admins (for all hosted domains)
        $admins = '';
        foreach(explode(',', $xmpp_config['xmpp_server_admins']) AS $a)
            $admins.= "\t\"".trim($a)."\",\n";
        $tpl->setVar('server_admins', $admins);
        unset($admins);
        // enabled modules, so own modules or simmilar prosody-modules can easily be added
        $modules = '';
        foreach(explode(',', $xmpp_config['xmpp_modules_enabled']) AS $m)
            $modules.= "\t\"".trim($m)."\",\n";
        $tpl->setVar('modules_enabled', $modules);
        unset($modules);
        $app->system->file_put_contents($this->xmpp_config_dir.'/global.cfg.lua', $tpl->grab());
        unset($tpl);

        $app->services->restartServiceDelayed('metronome', 'restart');
        return;
    }

    function domainInsert($event_name, $data) {
        global $app, $conf;

        $this->domainUpdate($event_name, $data);

    }

    function domainUpdate($event_name, $data){
        global $app, $conf;

        // get the config
        $app->uses("getconf,system,tpl");

        // Collections
        $status_hosts = array($data['new']['domain']);
        $status_comps = array();

        // Create main host file
        $tpl = new tpl();
        $tpl->newTemplate('metronome_conf_host.master');
        $tpl->setVar('domain', $data['new']['domain']);
        $tpl->setVar('active', $data['new']['active'] == 'y' ? 'true' : 'false');
        $tpl->setVar('public_registration', $data['new']['public_registration'] == 'y' ? 'true' : 'false');
        // Domain admins
        $admins = array();
        foreach(explode(',',$data['new']['domain_admins']) AS $adm){
            $admins[] = trim($adm);
        }
        $tpl->setVar('domain_admins', "\t\t\"".implode("\",\n\t\t\"",$admins)."\"\n");

        // Enable / Disable features
        if($data['new']['use_pubsub']=='y'){
            $tpl->setVar('use_pubsub', 'true');
            $status_comps[] = 'pubsub.'.$data['new']['domain'];
        }else{
            $tpl->setVar('use_pubsub', 'false');
        }
        if($data['new']['use_proxy']=='y'){
            $tpl->setVar('use_proxy', 'true');
            $status_comps[] = 'proxy.'.$data['new']['domain'];
        }else{
            $tpl->setVar('use_proxy', 'false');
        }

        if($data['new']['use_anon_host']=='y'){
            $tpl->setVar('use_anon_host', 'true');
            $status_hosts[] = 'anon.'.$data['new']['domain'];
        }else{
            $tpl->setVar('use_anon_host', 'false');
        }
        if($data['new']['use_vjud']=='y'){
            $tpl->setVar('use_vjud', 'true');
            $tpl->setVar('vjud_opt_mode', 'opt-'.$data['new']['vjud_opt_mode']);
            $status_comps[] = 'vjud.'.$data['new']['domain'];
        }else{
            $tpl->setVar('use_vjud', 'false');
        }

        $tpl->setVar('use_muc', $data['new']['use_muc_host']=='y'?'true':'false');
        if($data['new']['use_muc_host'] == 'y'){
            $status_comps[] = 'muc.'.$data['new']['domain'];
            $tpl->setVar('muc_restrict_room_creation', $data['new']['muc_restrict_room_creation']);
            $tpl->setVar('muc_name', strlen($data['new']['muc_name']) ? $data['new']['muc_name'] : $data['new']['domain'].' Chatrooms');
            // Admins for MUC channels
            $admins = array();
            foreach(explode(',',$data['new']['muc_admins']) AS $adm){
                $admins[] = trim($adm);
            }
            $tpl->setVar('muc_admins', "\t\t\"".implode("\",\n\t\t\"",$admins)."\"\n");
            $tpl->setVar('use_pastebin', $data['new']['use_pastebin']=='y'?'true':'false');
            $tpl->setVar('pastebin_expire', intval($data['new']['pastebin_expire_after']));
            $tpl->setVar('pastebin_trigger', $data['new']['pastebin_trigger']);
            $tpl->setVar('use_archive', $data['new']['use_http_archive']=='y'?'true':'false');
            $tpl->setVar('archive_join', $data['new']['http_archive_show_join']=='y'?'true':'false');
            $tpl->setVar('archive_status', $data['new']['http_archive_show_status']=='y'?'true':'false');

        }

        // Check for SSL
        if(strlen($data['new']['ssl_cert']) && strlen($data['new']['ssl_key']) && !$this->ssl_certificate_deleted || $this->ssl_certificate_changed)
            $tpl->setVar('ssl_cert', true);

        $app->system->file_put_contents($this->xmpp_config_dir.'/hosts/'.$data['new']['domain'].'.cfg.lua', $tpl->grab());
        unset($tpl);

        // Create status host file
        if($data['new']['use_status_host']=='y'){
            $tpl = new tpl;
            $tpl->newTemplate('metronome_conf_status.master');
            $tpl->setVar('domain', $data['new']['domain']);
            $tpl->setVar('status_hosts', "\t\t\"".implode("\",\n\t\t\"",$status_hosts)."\"\n");
            $tpl->setVar('status_comps', "\t\t\"".implode("\",\n\t\t\"",$status_comps)."\"\n");
            $app->system->file_put_contents($this->xmpp_config_dir.'/status/'.$data['new']['domain'].'.cfg.lua', $tpl->grab());
            unset($tpl);
        }

        $app->services->restartServiceDelayed('metronome', 'reload');
    }

    function domainDelete($event_name, $data){
        global $app, $conf;

        // get the config
        $app->uses("system");
        $domain = $data['old']['domain'];
        $folder = str_replace('-', '%2d', str_replace('.', '%2e', $str = urlencode($domain)));

        // Remove config files
        $app->system->unlink("/etc/metronome/hosts/$domain.cfg.lua");
        $app->system->unlink("/etc/metronome/status/$domain.cfg.lua");
        $app->system->unlink("/etc/metronome/certs/$domain.cert");
        $app->system->unlink("/etc/metronome/certs/$domain.key");
        $app->system->unlink("/etc/metronome/certs/$domain.csr");
        // Remove all stored data
        var_dump('rm -rf /var/lib/metronome/'.$folder);
        $app->system->exec_safe('rm -rf ?', '/var/lib/metronome/'.$folder);
        $app->system->exec_safe('rm -rf ?*?', '/var/lib/metronome/', '%2e'.$folder);

        $app->services->restartServiceDelayed('metronome', 'reload');
    }

    function userInsert($event_name, $data){
        //$data['new']['auth_method']
        // Check domain for auth settings
        // Don't allow manual user creation for mailaccount controlled domains

        // maybe metronomectl adduser for new local users
    }
    function userUpdate($event_name, $data){
        // Check domain for auth settings
        // Don't allow manual user update for mailaccount controlled domains

        // maybe metronomectl passwd for existing local users
    }
    function userDelete($event_name, $data){
        // Check domain for auth settings
        // Don't allow manual user deletion for mailaccount controlled domains

        // Remove account from metronome
        $app->system->exec_safe('metronomectl deluser ?', $data['old']['jid']);
    }

    // Handle the creation of SSL certificates
    function ssl($event_name, $data) {
        global $app, $conf;

        $app->uses('system,tpl');

        // load the server configuration options
        $app->uses('getconf');
        $web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

        $ssl_dir = '/etc/metronome/certs';
        $domain = $data['new']['domain'];
        $cnf_file = $ssl_dir.'/'.$domain.'.cnf';
        $key_file = $ssl_dir.'/'.$domain.'.key';
        $csr_file = $ssl_dir.'/'.$domain.'.csr';
        $crt_file = $ssl_dir.'/'.$domain.'.cert';

        //* Create a SSL Certificate, but only if this is not a mirror server.
        if($data['new']['ssl_action'] == 'create' && $conf['mirror_server_id'] == 0) {

            $this->ssl_certificate_changed = true;

            //* Rename files if they exist
            if(file_exists($cnf_file)) $app->system->rename($cnf_file, $cnf_file.'.bak');
            if(file_exists($key_file)){
                $app->system->rename($key_file, $key_file.'.bak');
                $app->system->chmod($key_file.'.bak', 0400);
                $app->system->chown($key_file.'.bak', 'metronome');
            }
            if(file_exists($csr_file)) $app->system->rename($csr_file, $csr_file.'.bak');
            if(file_exists($crt_file)) $app->system->rename($crt_file, $crt_file.'.bak');

            // Write new CNF file
            $tpl = new tpl();
            $tpl->newTemplate('metronome_conf_ssl.master');
            $tpl->setVar('domain', $domain);
            $tpl->setVar('ssl_country', $data['new']['ssl_country']);
            $tpl->setVar('ssl_locality', $data['new']['ssl_locality']);
            $tpl->setVar('ssl_organisation', $data['new']['ssl_organisation']);
            $tpl->setVar('ssl_organisation_unit', $data['new']['ssl_organisation_unit']);
            $tpl->setVar('ssl_email', $data['new']['ssl_email']);
            $app->system->file_put_contents($cnf_file, $tpl->grab());

            // Generate new key, csr and cert
            $app->system->exec_safe("(cd /etc/metronome/certs && make ?)", "$domain.key");
            $app->system->exec_safe("(cd /etc/metronome/certs && make ?)", "$domain.csr");
            $app->system->exec_safe("(cd /etc/metronome/certs && make ?)", "$domain.cert");

            $ssl_key = $app->system->file_get_contents($key_file);
            $app->system->chmod($key_file, 0400);
            $app->system->chown($key_file, 'metronome');
            $ssl_request = $app->system->file_get_contents($csr_file);
            $ssl_cert = $app->system->file_get_contents($crt_file);
            /* Update the DB of the (local) Server */
            $app->db->query("UPDATE xmpp_domain SET ssl_request = ?, ssl_cert = ?, ssl_key = ? WHERE domain = ?", $ssl_request, $ssl_cert, $ssl_key, $data['new']['domain']);
            $app->db->query("UPDATE xmpp_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
            /* Update also the master-DB of the Server-Farm */
            $app->dbmaster->query("UPDATE xmpp_domain SET ssl_request = ?, ssl_cert = ?, ssl_key = ? WHERE domain = ?", $ssl_request, $ssl_cert, $ssl_key, $data['new']['domain']);
            $app->dbmaster->query("UPDATE xmpp_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
            $app->log('Creating XMPP SSL Cert for: '.$domain, LOGLEVEL_DEBUG);
        }

        //* Save a SSL certificate to disk
        if($data["new"]["ssl_action"] == 'save') {
            $this->ssl_certificate_changed = true;

            //* Rename files if they exist
            if(file_exists($cnf_file)) $app->system->rename($cnf_file, $cnf_file.'.bak');
            if(file_exists($key_file)){
                $app->system->rename($key_file, $key_file.'.bak');
                $app->system->chmod($key_file.'.bak', 0400);
                $app->system->chown($key_file.'.bak', 'metronome');
            }
            if(file_exists($csr_file)) $app->system->rename($csr_file, $csr_file.'.bak');
            if(file_exists($crt_file)) $app->system->rename($crt_file, $crt_file.'.bak');

            //* Write new ssl files
            if(trim($data["new"]["ssl_request"]) != '')
                $app->system->file_put_contents($csr_file, $data["new"]["ssl_request"]);
            if(trim($data["new"]["ssl_cert"]) != '')
                $app->system->file_put_contents($crt_file, $data["new"]["ssl_cert"]);

            //* Write the key file, if field is empty then import the key into the db
            if(trim($data["new"]["ssl_key"]) != '') {
                $app->system->file_put_contents($key_file, $data["new"]["ssl_key"]);
                $app->system->chmod($key_file, 0400);
                $app->system->chown($key_file, 'metronome');
            } else {
                $ssl_key = $app->system->file_get_contents($key_file);
                /* Update the DB of the (local) Server */
                $app->db->query("UPDATE xmpp_domain SET ssl_key = ? WHERE domain = ?", $ssl_key, $data['new']['domain']);
                /* Update also the master-DB of the Server-Farm */
                $app->dbmaster->query("UPDATE xmpp_domain SET ssl_key = '$ssl_key' WHERE domain = ?", $data['new']['domain']);
            }

            /* Update the DB of the (local) Server */
            $app->db->query("UPDATE xmpp_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);

            /* Update also the master-DB of the Server-Farm */
            $app->dbmaster->query("UPDATE xmpp_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
            $app->log('Saving XMPP SSL Cert for: '.$domain, LOGLEVEL_DEBUG);
        }

        //* Delete a SSL certificate
        if($data['new']['ssl_action'] == 'del') {
            $this->ssl_certificate_deleted = true;
            $app->system->unlink($csr_file);
            $app->system->unlink($crt_file);
            $app->system->unlink($key_file);
            $app->system->unlink($cnf_file);
            $app->system->unlink($csr_file.'.bak');
            $app->system->unlink($crt_file.'.bak');
            $app->system->unlink($key_file.'.bak');
            $app->system->unlink($cnf_file.'.bak');
            /* Update the DB of the (local) Server */
            $app->db->query("UPDATE xmpp_domain SET ssl_request = '', ssl_cert = '', ssl_key = '' WHERE domain = ?", $data['new']['domain']);
            $app->db->query("UPDATE xmpp_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
            /* Update also the master-DB of the Server-Farm */
            $app->dbmaster->query("UPDATE xmpp_domain SET ssl_request = '', ssl_cert = '', ssl_key = '' WHERE domain = ?", $data['new']['domain']);
            $app->dbmaster->query("UPDATE xmpp_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
            $app->log('Deleting SSL Cert for: '.$domain, LOGLEVEL_DEBUG);
        }

    }

} // end class

?>
