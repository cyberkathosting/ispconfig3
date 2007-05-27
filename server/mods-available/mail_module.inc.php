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

class mail_module {
	
	var $module_name = 'mail_module';
	var $class_name = 'mail_module';
	var $actions_available = array(	'mail_domain_insert',
									'mail_domain_update',
									'mail_domain_delete',
									'mail_user_insert',
									'mail_user_update',
									'mail_user_delete',
									'mail_access_insert',
									'mail_access_update',
									'mail_access_delete',
									'mail_forwarding_insert',
									'mail_forwarding_update',
									'mail_forwarding_delete',
									'mail_transport_insert',
									'mail_transport_update',
									'mail_transport_delete');
	
	/*
	 	This function is called when the module is loaded
	*/
	
	function onLoad() {
		
		/*
		Annonce the actions that where provided by this module, so plugins 
		can register on them.
		*/
		
		$app->plugins->registerEvents($this->module_name,$this->actions_available);
		
		/*
		As we want to get notified of any changes on several database tables,
		we register for them.
		
		The following function registers the function "functionname"
 		to be executed when a record for the table "dbtable" is 
 		processed in the sys_datalog. "classname" is the name of the
 		class that contains the function functionname.
		*/
		
		$app->modules->registerTableHook('mail_access','mail_module','process');
		$app->modules->registerTableHook('mail_domain','mail_module','process');
		$app->modules->registerTableHook('mail_forwarding','mail_module','process');
		$app->modules->registerTableHook('mail_transport','mail_module','process');
		$app->modules->registerTableHook('mail_user','mail_module','process');
		
	}
	
	/*
	 This function is called when a change in one of the registered tables is detected.
	 The function then raises the events for the plugins.
	*/

	function process($tablename,$action,$data) {
		global $app;
		
		switch ($tablename) {
			case 'mail_access':
				if($action == 'i') $app->plugins->raiseEvent('mail_access_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('mail_access_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('mail_access_delete',$data);
			break;
			case 'mail_domain':
				if($action == 'i') $app->plugins->raiseEvent('mail_domain_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('mail_domain_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('mail_domain_delete',$data);
			break;
			case 'mail_forwarding':
				if($action == 'i') $app->plugins->raiseEvent('mail_forwarding_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('mail_forwarding_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('mail_forwarding_delete',$data);
			break;
			case 'mail_transport':
				if($action == 'i') $app->plugins->raiseEvent('mail_transport_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('mail_transport_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('mail_transport_delete',$data);
			break;
			case 'mail_user':
				if($action == 'i') $app->plugins->raiseEvent('mail_user_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('mail_user_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('mail_user_delete',$data);
			break;
		} // end switch
	} // end function

} // end class

?>