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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/web_childdomain.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->uses('listform_actions');

//* Get and set the child domain type - store in session
$show_type = 'aliasdomain';
if(isset($_GET['type']) && $_GET['type'] == 'subdomain') $show_type = 'subdomain';
elseif(!isset($_GET['type']) && isset($_SESSION['s']['var']['childdomain_type']) && $_SESSION['s']['var']['childdomain_type'] == 'subdomain') $show_type = 'subdomain';

$_SESSION['s']['var']['childdomain_type'] = $show_type;

class list_action extends listform_actions {
	function onShow() {
		global $app;
		$app->tpl->setVar('childdomain_type', $_SESSION['s']['var']['childdomain_type'], true);
		
		parent::onShow();
	}
}


$list = new list_action;
// Limit the results to alias domains
$list->SQLExtWhere = "web_domain.type = '" . ($show_type == 'subdomain' ? 'subdomain' : 'alias') . "'";
$list->SQLOrderBy = 'ORDER BY web_domain.domain';
$list->onLoad();


?>
