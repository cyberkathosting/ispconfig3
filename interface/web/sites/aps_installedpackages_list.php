<?php
/*
Copyright (c) 2012, ISPConfig UG
Contributors: web wack creations,  http://www.web-wack.at
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
//require_once('classes/class.base.php'); // for constants
$app->load('aps_base');

// Path to the list definition file
$list_def_file = "list/aps_installedpackages.list.php";

// Check the module permissions
$app->auth->check_module_permissions('sites');

// Load needed classes
$app->uses('tpl,tform,listform,listform_actions');

// Show further information only to admins or resellers
if($_SESSION['s']['user']['typ'] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid']))
	$app->tpl->setVar('is_noclient', 1);

// Show each user the own packages (if not admin)
$client_ext = '';
$is_admin = ($_SESSION['s']['user']['typ'] == 'admin') ? true : false;
if(!$is_admin)
{
	$cid = $app->db->queryOneRecord('SELECT client_id FROM client WHERE username = ?', $_SESSION['s']['user']['username']);
	//$client_ext = ' AND aps_instances.customer_id = '.$cid['client_id'];
	$client_ext = ' AND '.$app->tform->getAuthSQL('r', 'aps_instances');
}
$app->listform_actions->SQLExtWhere = 'aps_instances.package_id = aps_packages.id'.$client_ext;
$app->listform_actions->SQLOrderBy = 'ORDER BY package_name';

// We are using parts of listform_actions because ISPConfig doesn't allow
// queries over multiple tables so we construct them ourselves
$_SESSION['s']['form']['return_to'] = '';

// Load the list template
$app->listform->loadListDef($list_def_file);
if(!is_file('templates/'.$app->listform->listDef["name"].'_list.htm'))
{
	$app->uses('listform_tpl_generator');
	$app->listform_tpl_generator->buildHTML($app->listform->listDef);
}
$app->tpl->newTemplate("listpage.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/'.$app->listform->listDef["name"].'_list.htm');

// Build the WHERE query for search
$sql_where = '';
if($app->listform_actions->SQLExtWhere != '')
	$sql_where .= ' '.$app->listform_actions->SQLExtWhere.' and';
$sql_where = $app->listform->getSearchSQL($sql_where);
$app->tpl->setVar($app->listform->searchValues);

// Paging
$limit_sql = $app->listform->getPagingSQL($sql_where);
$app->tpl->setVar('paging', $app->listform->pagingHTML);

if(!$is_admin) {
	// Our query over multiple tables
	$query = "SELECT aps_instances.id AS id, aps_instances.package_id AS package_id,
                 aps_instances.customer_id AS customer_id, client.username AS customer_name,
                 aps_instances.instance_status AS instance_status, aps_packages.name AS package_name,
                 aps_packages.version AS package_version, aps_packages.release AS package_release,
                 aps_packages.package_status AS package_status,
              CONCAT((SELECT value FROM aps_instances_settings WHERE name='main_domain' AND instance_id = aps_instances.id),
                 '/', (SELECT value FROM aps_instances_settings WHERE name='main_location' AND instance_id = aps_instances.id))
                  AS install_location
          FROM aps_instances, aps_packages, client
          WHERE client.client_id = aps_instances.customer_id AND ".$sql_where." ".$app->listform_actions->SQLOrderBy." ".$limit_sql;
} else {
	$query = "SELECT aps_instances.id AS id, aps_instances.package_id AS package_id,
                 aps_instances.customer_id AS customer_id, sys_group.name AS customer_name,
				 aps_instances.instance_status AS instance_status, aps_packages.name AS package_name,
                 aps_packages.version AS package_version, aps_packages.release AS package_release,
                 aps_packages.package_status AS package_status,
              CONCAT((SELECT value FROM aps_instances_settings WHERE name='main_domain' AND instance_id = aps_instances.id),
                 '/', (SELECT value FROM aps_instances_settings WHERE name='main_location' AND instance_id = aps_instances.id))
                  AS install_location
          FROM aps_instances, aps_packages, sys_group
          WHERE sys_group.client_id = aps_instances.customer_id AND ".$sql_where." ".$app->listform_actions->SQLOrderBy." ".$limit_sql;

}

$records = $app->db->queryAllRecords($query);
$app->listform_actions->DataRowColor = '#FFFFFF';

$csrf_token = $app->auth->csrf_token_get($app->listform->listDef['name']);
$_csrf_id = $csrf_token['csrf_id'];
$_csrf_key = $csrf_token['csrf_key'];

// Re-form all result entries and add extra entries
$records_new = array();
if(is_array($records))
{
	$app->listform_actions->idx_key = $app->listform->listDef["table_idx"];
	foreach($records as $key => $rec)
	{
		// Set an abbreviated install location to beware the page layout
		$ils = '';
		if(strlen($rec['Install_location']) >= 38) $ils = substr($rec['Install_location'], 0,  35).'...';
		else $ils = $rec['install_location'];
		$rec['install_location_short'] = $ils;

		// Of course an instance can only then be removed when it's not already tagged for removal
		if($rec['instance_status'] != INSTANCE_REMOVE && $rec['instance_status'] != INSTANCE_INSTALL)
			$rec['delete_possible'] = 'true';

		$records_new[$key] = $app->listform_actions->prepareDataRow($rec);
		$records_new[$key]['csrf_id'] = $_csrf_id;
		$records_new[$key]['csrf_key'] = $_csrf_key;
	}
}
$app->tpl->setLoop('records', $records_new);

$app->listform_actions->onShow();
?>
