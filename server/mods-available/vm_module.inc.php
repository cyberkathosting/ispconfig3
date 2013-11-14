<?php

/*
Copyright (c) 2009, Till Brehm, projektfarm Gmbh
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

class vm_module {

	var $module_name = 'vm_module';
	var $class_name = 'vm_module';
	var $actions_available = array( 'openvz_vm_insert',
		'openvz_vm_update',
		'openvz_vm_delete',
		'openvz_ip_insert',
		'openvz_ip_update',
		'openvz_ip_delete',
		'openvz_ostemplate_insert',
		'openvz_ostemplate_update',
		'openvz_ostemplate_delete');

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['vserver'] == true) {
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

		$app->modules->registerTableHook('openvz_vm', $this->module_name, 'process');
		$app->modules->registerTableHook('openvz_ip', $this->module_name, 'process');
		$app->modules->registerTableHook('openvz_ostemplate', $this->module_name, 'process');

	}

	/*
	 This function is called when a change in one of the registered tables is detected.
	 The function then raises the events for the plugins.
	*/

	function process($tablename, $action, $data) {
		global $app;

		switch ($tablename) {
		case 'openvz_vm':
			if($action == 'i') $app->plugins->raiseEvent('openvz_vm_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('openvz_vm_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('openvz_vm_delete', $data);
			break;
		case 'openvz_ip':
			if($action == 'i') $app->plugins->raiseEvent('openvz_ip_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('openvz_ip_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('openvz_ip_delete', $data);
			break;
		case 'openvz_ostemplate':
			if($action == 'i') $app->plugins->raiseEvent('openvz_ostemplate_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('openvz_ostemplate_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('openvz_ostemplate_delete', $data);
			break;
		} // end switch
	} // end function


} // end class

?>
