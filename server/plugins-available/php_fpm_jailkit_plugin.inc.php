<?php

/*
Copyright (c) 2018, Till Brehm, projektfarm Gmbh
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

class php_fpm_jailkit_plugin {

    //* $plugin_name and $class_name have to be the same then the name of this class
    var $plugin_name = 'php_fpm_jailkit_plugin';
    var $class_name = 'php_fpm_jailkit_plugin';
   
    private $parent_domain;
    private $jailkit_config;

    //* This function is called during ispconfig installation to determine
    //  if a symlink shall be created for this plugin.
    function onInstall() {
        global $conf;

        if($conf['services']['web'] == true) {
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

        $app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'insert');
        $app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'update');
        $app->plugins->registerEvent('web_domain_delete', $this->plugin_name, 'delete');

    }

    //* This function is called, when a website is inserted in the database
    function insert($event_name, $data) {
        global $app, $conf;

        $this->action = 'insert';
        // just run the update function
        $this->update($event_name, $data);

    }

    //* This function is called, when a website is updated in the database
    function update($event_name, $data)
    {
        global $app, $conf;

        if ($data["new"]["php_fpm_chroot"] != 'y') {
            if ($this->action == 'update' && $data['old']['document_root'] != '' && $data['new']['document_root'] != $data['old']['document_root']) {
                $this->remove_old_mount_mysql($data);
            }
            return 0;
        }
        if( file_exists("/var/run/mysqld")) {
            if ($this->action == 'update' && $data['old']['document_root'] != '' && $data['new']['document_root'] != $data['old']['document_root']) {
                $this->remove_old_mount_mysql($data);
            }

            $fstab_line = '/var/run/mysqld ' . $data['new']['document_root'] . '/var/run/mysqld    none    bind,nobootwait    0    0';
            $app->system->replaceLine('/etc/fstab', $fstab_line, $fstab_line, 0, 1);

            $command = 'mount --bind /var/run/mysqld ' . escapeshellarg($data['new']['document_root']) . '/var/run/mysqld/';
            exec($command);
        }

        $this->parent_domain = $data["new"];
        $parent_domain = $data["new"];
        if(!$app->system->is_allowed_user($parent_domain['system_user'], true, true)
            || !$app->system->is_allowed_group($parent_domain['system_group'], true, true)) {
            $app->log("Websites cannot be owned by the root user or group.", LOGLEVEL_WARN);
            return false;
        }

        $app->uses('system');


        if($app->system->is_user($parent_domain['system_user'])) {

            /**
             * Setup Jailkit Chroot System If Enabled
             */

            $app->log("Jailkit Plugin (PHP-FPM Chroot) -> setting up jail", LOGLEVEL_DEBUG);
            // load the server configuration options

            $app->uses("getconf");
            $this->data = $data;
            $this->app = $app;
            $this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');

            $this->_update_website_security_level();

            $app->system->web_folder_protection($parent_domain['document_root'], false);

            $this->_setup_jailkit_chroot();
            $this->_add_jailkit_user();

            $this->_update_website_security_level();
            $app->system->web_folder_protection($parent_domain['document_root'], true);


            $app->log("Jailkit Plugin (PHP-FPM Chroot) -> update username:".$parent_domain['system_user'], LOGLEVEL_DEBUG);

        } else {
            $app->log("Jailkit Plugin (PHP-FPM Chroot) -> update username:".$parent_domain['system_user']." skipped, the user does not exist.", LOGLEVEL_WARN);
        }
    }

    function remove_old_mount_mysql($data){
        global $app, $conf;

        if(!file_exists("/var/run/mysqld")){
            return;
        }
        $fstab_line = '/var/run/mysqld ' . $data['old']['document_root'] . '/var/run/mysqld    none    bind,nobootwait    0    0';

        $app->system->removeLine('/etc/fstab', $fstab_line);

        $command = 'umount '.escapeshellarg($data['old']['document_root']).'/var/run/mysqld/';
        exec($command);
    }

    //* This function is called, when a website is deleted in the database
    function delete($event_name, $data) {
        global $app, $conf;

        $this->remove_old_mount_mysql($data);
    }

    function _setup_jailkit_chroot()
    {
        global $app;

        //check if the chroot environment is created yet if not create it with a list of program sections from the config
        if (!is_dir($this->parent_domain['document_root'].'/etc/jailkit'))
        {
            $command = '/usr/local/ispconfig/server/scripts/create_jailkit_chroot.sh';
            $command .= ' '.escapeshellcmd($this->parent_domain['document_root']);
            $command .= ' \''.$this->jailkit_config['jailkit_chroot_app_sections'].'\'';
            exec($command.' 2>/dev/null');

            $this->app->log("Added jailkit chroot with command: ".$command, LOGLEVEL_DEBUG);

            //$this->_add_jailkit_programs(); // done later on

            $this->app->load('tpl');

            $tpl = new tpl();
            $tpl->newTemplate("bash.bashrc.master");

            $tpl->setVar('jailkit_chroot', true);
            $tpl->setVar('domain', $this->parent_domain['domain']);
            $tpl->setVar('home_dir', $this->_get_home_dir(""));

            $bashrc = escapeshellcmd($this->parent_domain['document_root']).'/etc/bash.bashrc';
            if(@is_file($bashrc) || @is_link($bashrc)) unlink($bashrc);

            $app->system->file_put_contents($bashrc, $tpl->grab());
            unset($tpl);

            $this->app->log('Added bashrc script: '.$bashrc, LOGLEVEL_DEBUG);

            $tpl = new tpl();
            $tpl->newTemplate('motd.master');

            $tpl->setVar('domain', $this->parent_domain['domain']);

            $motd = escapeshellcmd($this->parent_domain['document_root']).'/var/run/motd';
            if(@is_file($motd) || @is_link($motd)) unlink($motd);

            $app->system->file_put_contents($motd, $tpl->grab());

        }
        $this->_add_jailkit_programs();
    }

    function _add_jailkit_programs()
    {
        global $app;

        //copy over further programs and its libraries
        $jailkit_chroot_app_programs = preg_split("/[\s,]+/", $this->jailkit_config['jailkit_chroot_app_programs']);
        if(is_array($jailkit_chroot_app_programs) && !empty($jailkit_chroot_app_programs)){
            foreach($jailkit_chroot_app_programs as $jailkit_chroot_app_program){
                $jailkit_chroot_app_program = trim($jailkit_chroot_app_program);
                if(is_file($jailkit_chroot_app_program) || is_dir($jailkit_chroot_app_program)){
                    //copy over further programs and its libraries
                    $command = '/usr/local/ispconfig/server/scripts/create_jailkit_programs.sh';
                    $command .= ' '.escapeshellcmd($this->data['new']['dir']);
                    $command .= ' '.$jailkit_chroot_app_program;
                    exec($command.' 2>/dev/null');

                    $this->app->log("Added programs to jailkit chroot with command: ".$command, LOGLEVEL_DEBUG);
                }
            }
        }

//        $command = '/usr/local/ispconfig/server/scripts/create_jailkit_programs.sh';
//        $command .= ' '.escapeshellcmd($this->parent_domain['document_root']);
//        $command .= ' \''.$this->jailkit_config['jailkit_chroot_cron_programs'].'\'';
//        exec($command.' 2>/dev/null');
//
//        $this->app->log("Added cron programs to jailkit chroot with command: ".$command, LOGLEVEL_DEBUG);
    }

    function _add_jailkit_user()
    {
        global $app;

        //add the user to the chroot
        $jailkit_chroot_userhome = $this->_get_home_dir($this->parent_domain['system_user']);

        if(!is_dir($this->parent_domain['document_root'].'/etc')) mkdir($this->parent_domain['document_root'].'/etc');
        if(!is_file($this->parent_domain['document_root'].'/etc/passwd')) exec('touch '.$this->parent_domain['document_root'].'/etc/passwd');

        // IMPORTANT!
        // ALWAYS create the user. Even if the user was created before
        // if we check if the user exists, then a update (no shell -> jailkit) will not work
        // and the user has FULL ACCESS to the root of the server!
        $command = '/usr/local/ispconfig/server/scripts/create_jailkit_user.sh';
        $command .= ' '.escapeshellcmd($this->parent_domain['system_user']);
        $command .= ' '.escapeshellcmd($this->parent_domain['document_root']);
        $command .= ' '.$jailkit_chroot_userhome;
        $command .= ' '.escapeshellcmd("/bin/bash");
        exec($command.' 2>/dev/null');

        $this->app->log("Added jailkit user to chroot with command: ".$command, LOGLEVEL_DEBUG);

        $app->system->mkdir(escapeshellcmd($this->parent_domain['document_root'].$jailkit_chroot_userhome), 0755, true);
        $app->system->chown(escapeshellcmd($this->parent_domain['document_root'].$jailkit_chroot_userhome), escapeshellcmd($this->parent_domain['system_user']));
        $app->system->chgrp(escapeshellcmd($this->parent_domain['document_root'].$jailkit_chroot_userhome), escapeshellcmd($this->parent_domain['system_group']));

    }

    function _get_home_dir($username)
    {
        return str_replace("[username]", escapeshellcmd($username), $this->jailkit_config["jailkit_chroot_home"]);
    }

    //* Update the website root directory permissions depending on the security level
    function _update_website_security_level() {
        global $app, $conf;

        // load the server configuration options
        $app->uses("getconf");
        $web_config = $app->getconf->get_server_config($conf["server_id"], 'web');

        $web = $this->parent_domain['new'];

        //* If the security level is set to high
        if($web_config['security_level'] == 20 && is_array($web)) {
            $app->system->web_folder_protection($web["document_root"], false);
            $app->system->chmod($web["document_root"], 0755);
            $app->system->chown($web["document_root"], 'root');
            $app->system->chgrp($web["document_root"], 'root');
            $app->system->web_folder_protection($web["document_root"], true);
        }
    }

    //* Wrapper for exec function for easier debugging
    private function _exec($command) {
        global $app;
        $app->log('exec: '.$command, LOGLEVEL_DEBUG);
        exec($command);
    }



} // end class

?>
