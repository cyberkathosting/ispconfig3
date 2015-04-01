<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

class xmpp_module {

    var $module_name = 'xmpp_module';
    var $class_name = 'xmpp_module';
    var $actions_available = array(
        'xmpp_domain_insert',
        'xmpp_domain_update',
        'xmpp_domain_delete',
        'xmpp_user_insert',
        'xmpp_user_update',
        'xmpp_user_delete'
    );

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
         This function is called when the module is loaded
    */

    function onLoad() {
        global $app;

        /*
        Annonce the actions that where provided by this module, so plugins
        can register on them.
        */

        $app->plugins->announceEvents($this->module_name, $this->actions_available);

        /*
        As we want to get notified of any changes on several database tables,
        we register for them.

        The following function registers the function "functionname"
         to be executed when a record for the table "dbtable" is
         processed in the sys_datalog. "classname" is the name of the
         class that contains the function functionname.
        */

        $app->modules->registerTableHook('xmpp_domain', 'xmpp_module', 'process');
        $app->services->registerService('metronome', 'xmpp_module', 'reloadXMPP');
        $app->services->registerService('metronome', 'xmpp_module', 'restartXMPP');

    }

    /*
     This function is called when a change in one of the registered tables is detected.
     The function then raises the events for the plugins.
    */

    function process($tablename, $action, $data) {
        global $app;

        switch ($tablename) {
            case 'xmpp_domain':
                if($action == 'i') $app->plugins->raiseEvent('xmpp_domain_insert', $data);
                if($action == 'u') $app->plugins->raiseEvent('xmpp_domain_update', $data);
                if($action == 'd') $app->plugins->raiseEvent('xmpp_domain_delete', $data);
                break;
            case 'xmpp_user':
                if($action == 'i') $app->plugins->raiseEvent('xmpp_user_insert', $data);
                if($action == 'u') $app->plugins->raiseEvent('xmpp_user_update', $data);
                if($action == 'd') $app->plugins->raiseEvent('xmpp_user_delete', $data);
                break;
        } // end switch
    } // end function


    function restartXMPP($action = 'restart') {
        global $app, $conf;

        // load the server configuration options
        $app->uses('getconf,system');

        $daemon = 'metronome';

        $retval = array('output' => '', 'retval' => 0);
        if($action == 'restart') {
            $cmd = $app->system->getinitcommand($daemon, 'restart');
        } else {
            $cmd = $app->system->getinitcommand($daemon, 'reload');
        }
        exec($cmd.' 2>&1', $retval['output'], $retval['retval']);
        $app->log("Restarting xmpp: $cmd", LOGLEVEL_DEBUG);
        return $retval;
    }
} // end class

?>
