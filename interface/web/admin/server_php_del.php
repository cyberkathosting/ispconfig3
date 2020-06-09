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

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/server_php.list.php";
$tform_def_file = "form/server_php.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');
$app->auth->check_security_permissions('admin_allow_server_php');

$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onBeforeDelete() {
		global $app; $conf;

		$check = array();

		// fastcgi
		if(!empty(trim($this->dataRecord['php_fastcgi_binary']))) $check[] = trim($this->dataRecord['php_fastcgi_binary']);
		if(!empty(trim($this->dataRecord['php_fastcgi_ini_dir']))) $check[] = trim($this->dataRecord['php_fastcgi_ini_dir']);
		if(!empty($check)) $fastcgi_check = implode(':', $check);
		unset($check);

		// fpm
		if(!empty(trim($this->dataRecord['php_fpm_init_script']))) $check[] = trim($this->dataRecord['php_fpm_init_script']);
		if(!empty(trim($this->dataRecord['php_fpm_ini_dir']))) $check[] = trim($this->dataRecord['php_fpm_ini_dir']);
		if(!empty(trim($this->dataRecord['php_fpm_pool_dir']))) $check[] = trim($this->dataRecord['php_fpm_pool_dir']);
		if(!empty($check)) $fpm_check = implode(':', $check);

 		$sql = 'SELECT domain_id FROM web_domain WHERE server_id = ? AND fastcgi_php_version LIKE ?';
 		if(isset($fastcgi_check)) $web_domains_fastcgi = $app->db->queryAllRecords($sql, $this->dataRecord['server_id'], '%:'.$fastcgi_check);
		if(isset($fpm_check)) $web_domains_fpm = $app->db->queryAllRecords($sql, $this->dataRecord['server_id'], '%:'.$fpm_check);

		if(!empty($webdomains_fastcgi) || !empty($web_domains_fpm))	$app->error($app->tform->lng('php_in_use_error'));

	}

}

$page = new page_action;
$page->onDelete();

?>
