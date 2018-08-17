<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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

//* Check permissions for module
$app->auth->check_module_permissions('admin');
$app->auth->check_security_permissions('admin_allow_software_packages');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

//* Get the latest updates from the repositorys and insert them in the local database
$updates_added = 0;
$repos = $app->db->queryAllRecords("SELECT software_repo_id, repo_url, repo_username, repo_password FROM software_repo WHERE active = 'y'");
if(is_array($repos)) {
	foreach($repos as $repo) {

		/*
		SELECT software_package.package_name, v1, v2, v3, v4
		FROM software_package
		LEFT JOIN software_update ON ( software_package.package_name = software_update.package_name )
		LEFT JOIN software_update_inst ON ( software_update.software_update_id = software_update_inst.software_update_id )
		GROUP BY package_name
		ORDER BY v1 DESC , v2 DESC , v3 DESC , v4 DESC
		*/

		$client = new SoapClient(null, array('location' => $repo['repo_url'],
				'uri'      => $repo['repo_url']));

		$packages = $app->db->queryAllRecords("SELECT software_package.package_name, v1, v2, v3, v4 FROM software_package LEFT JOIN software_update ON ( software_package.package_name = software_update.package_name ) GROUP BY package_name ORDER BY v1 DESC , v2 DESC , v3 DESC , v4 DESC");
		if(is_array($packages)) {
			foreach($packages as $p) {

				$version = $p['v1'].'.'.$p['v2'].'.'.$p['v3'].'.'.$p['v4'];
				$updates = $client->get_updates($p['package_name'], $version, $repo['repo_username'], $repo['repo_password']);

				if(is_array($updates)) {
					foreach($updates as $u) {

						$version_array = explode('.', $u['version']);
						$v1 = $app->functions->intval($version_array[0]);
						$v2 = $app->functions->intval($version_array[1]);
						$v3 = $app->functions->intval($version_array[2]);
						$v4 = $app->functions->intval($version_array[3]);

						$package_name = $u['package_name'];
						$software_repo_id = $app->functions->intval($repo['software_repo_id']);
						$update_url = $u['url'];
						$update_md5 = $u['md5'];
						$update_dependencies = (isset($u['dependencies']))?$u['dependencies']:'';
						$update_title = $u['title'];
						$type = $u['type'];

						// Check that we do not have this update in the database yet
						$sql = "SELECT * FROM software_update WHERE package_name = ? and v1 = ? and v2 = ? and v3 = ? and v4 = ?";
						$tmp = $app->db->queryOneRecord($sql, $package_name, $v1, $v2, $v3, $v4);
						if(!isset($tmp['software_update_id'])) {
							// Insert the update in the datbase
							$sql = "INSERT INTO software_update (software_repo_id, package_name, update_url, update_md5, update_dependencies, update_title, v1, v2, v3, v4, type)
							VALUES ($software_repo_id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
							//die($sql);
							$app->db->query($sql, $package_name, $update_url, $update_md5, $update_dependencies, $update_title, $v1, $v2, $v3, $v4, $type);
						}

					}
				}
			}
		}
	}
}


//* Install packages, if GET Request
if(isset($_GET['action']) && $_GET['action'] == 'install' && $_GET['package'] != '' && $_GET['server_id'] > 0) {
	$package_name = $_GET['package'];
	$server_id = $app->functions->intval($_GET['server_id']);
	$software_update_id = $app->functions->intval($_GET['id']);

	$insert_data = array(
		"package_name" => $package_name,
		"server_id" => $server_id,
		"software_update_id" => $software_update_id,
		"status" => 'installing'
	);
	$app->db->datalogInsert('software_update_inst', $insert_data, 'software_update_inst_id');

}



// Show the list in the interface
// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/software_update_list.htm');

/*
SELECT software_package.package_name, software_package.package_title, software_update.update_title, v1, v2, v3, v4, software_update_inst.status
		FROM software_package
		LEFT JOIN software_update ON ( software_package.package_name = software_update.package_name )
		LEFT JOIN software_update_inst ON ( software_update.software_update_id = software_update_inst.software_update_id )
GROUP BY software_update.software_update_id
		ORDER BY v1 DESC , v2 DESC , v3 DESC , v4 DESC
*/



if(isset($_POST["server_id"]) && $_POST["server_id"] > 0) {
	$server_id = $app->functions->intval($_POST["server_id"]);
} else {
	$server_id = 1;
}

$servers = $app->db->queryAllRecords('SELECT server_id, server_name FROM server ORDER BY server_name');
foreach($servers as $key => $server) {
	if($server['server_id'] == $server_id) {
		$servers[$key]['selected'] = 'selected';
	} else {
		$servers[$key]['selected'] = '';
	}
}

$app->tpl->setLoop('servers', $servers);

$sql = "SELECT v1, v2, v3, v4, software_update.update_title, software_update.software_update_id, software_update.package_name, v1, v2, v3, v4, software_update_inst.status
		FROM software_update LEFT JOIN software_update_inst ON ( software_update.software_update_id = software_update_inst.software_update_id )
		WHERE server_id = $server_id
		GROUP BY software_update.package_name
		ORDER BY software_update.package_name ASC, v1 DESC , v2 DESC , v3 DESC , v4 DESC";

$installed_packages = $app->db->queryAllRecords($sql);


$records_out = array();

if(is_array($installed_packages)) {
	foreach($installed_packages as $ip) {

		// Get version number of the latest installed version
		$sql = "SELECT v1, v2, v3, v4 FROM software_update, software_update_inst WHERE software_update.software_update_id = software_update_inst.software_update_id AND server_id = ? ORDER BY v1 DESC , v2 DESC , v3 DESC , v4 DESC LIMIT 0,1";
		$lu = $app->db->queryOneRecord($sql, $server_id);

		// Get all installable updates
		$sql = "SELECT * FROM software_update WHERE v1 >= ? AND v2 >= ? AND v3 >= ? AND v4 >= ? AND package_name = ? ORDER BY v1 DESC , v2 DESC , v3 DESC , v4 DESC";
		$updates = $app->db->queryAllRecords($sql, $lu['v1'], $lu['v2'], $lu['v3'], $lu['v4'], $ip['package_name']);
		//die($sql);

		if(is_array($updates)) {
			// Delete the last record as it is already installed
			unset($updates[count($updates)-1]);

			foreach($updates as $key => $u) {
				$version = $u['v1'].'.'.$u['v2'].'.'.$u['v3'].'.'.$u['v4'];
				$installed_txt = "<a href=\"#\" data-load-content=\"admin/software_update_list.php?action=install&package=".$u["package_name"]."&id=".$u["software_update_id"]."&server_id=".$server_id."\">Install Update</a><br />";
				$records_out[] = array('version' => $version, 'update_title' => $u["update_title"], 'installed' => $installed_txt);

			}
		}
	}
}



$app->tpl->setLoop('records', $records_out);

$language = (isset($_SESSION['s']['language']))?$_SESSION['s']['language']:$conf['language'];
include_once 'lib/lang/'.$app->functions->check_language($language).'_software_update_list.lng';
$app->tpl->setVar($wb);


$app->tpl_defaults();
$app->tpl->pparse();


?>
