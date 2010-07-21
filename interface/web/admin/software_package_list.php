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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

//* Get the latest packages from the repositorys and insert them in the local database
$packages_added = 0;
$repos = $app->db->queryAllRecords("SELECT software_repo_id, repo_url, repo_username, repo_password FROM software_repo WHERE active = 'y'");
if(is_array($repos) && isset($_GET['action']) && $_GET['action'] == 'repoupdate' ) {
	foreach($repos as $repo) {
		$client = new SoapClient(null, array('location' => $repo['repo_url'],
                                     		 'uri'      => $repo['repo_url']));
		
		$packages = $client->get_packages($repo['repo_username'], $repo['repo_password']);
		if(is_array($packages)) {
			foreach($packages as $p) {
				$package_name = $app->db->quote($p['name']);
				$tmp = $app->db->queryOneRecord("SELECT package_id FROM software_package WHERE package_name = '$package_name'");
				
				$package_title = $app->db->quote($p['title']);
				$package_description = $app->db->quote($p['description']);
				$software_repo_id = intval($repo['software_repo_id']);
				$package_type = $app->db->quote($p['type']);
				$package_installable = $app->db->quote($p['installable']);
				$package_requires_db = $app->db->quote($p['requires_db']);
				
				if(empty($tmp['package_id'])) {
					//$sql = "INSERT INTO software_package (software_repo_id, package_name, package_title, package_description,package_type,package_installable,package_requires_db) VALUES ($software_repo_id, '$package_name', '$package_title', '$package_description','$package_type','$package_installable','$package_requires_db')";
					//$app->db->query($sql);
					$insert_data = "(software_repo_id, package_name, package_title, package_description,package_type,package_installable,package_requires_db) VALUES ($software_repo_id, '$package_name', '$package_title', '$package_description','$package_type','$package_installable','$package_requires_db')";
					$app->db->datalogInsert('software_package', $insert_data, 'package_id');
					$packages_added++;
				} else {
					//$sql = "UPDATE software_package SET software_repo_id = $software_repo_id, package_title = '$package_title', package_description = '$package_description', package_type = '$package_type', package_installable = '$package_installable', package_requires_db = '$package_requires_db' WHERE package_name = '$package_name'";
					//$app->db->query($sql);
					$update_data = "software_repo_id = $software_repo_id, package_title = '$package_title', package_description = '$package_description', package_type = '$package_type', package_installable = '$package_installable', package_requires_db = '$package_requires_db'";
					//echo $update_data;
					$app->db->datalogUpdate('software_package', $update_data, 'package_id',$tmp['package_id']);
				}
			}
		}
        
        $packages = $app->db->queryAllRecords("SELECT software_package.package_name, v1, v2, v3, v4 FROM software_package LEFT JOIN software_update ON ( software_package.package_name = software_update.package_name ) GROUP BY package_name ORDER BY v1 DESC , v2 DESC , v3 DESC , v4 DESC");
        if(is_array($packages)) {
            foreach($packages as $p) {
            
                $version = $p['v1'].'.'.$p['v2'].'.'.$p['v3'].'.'.$p['v4'];
                $updates = $client->get_updates($p['package_name'], $version,$repo['repo_username'], $repo['repo_password']);
                
                if(is_array($updates)) {
                    foreach($updates as $u) {
                        
                        $version_array = explode('.',$u['version']);
                        $v1 = intval($version_array[0]);
                        $v2 = intval($version_array[1]);
                        $v3 = intval($version_array[2]);
                        $v4 = intval($version_array[3]);
                        
                        $package_name = $app->db->quote($u['package_name']);
                        $software_repo_id = intval($repo['software_repo_id']);
                        $update_url = $app->db->quote($u['url']);
                        $update_md5 = $app->db->quote($u['md5']);
                        $update_dependencies = (isset($u['dependencies']))?$app->db->quote($u['dependencies']):'';
                        $update_title = $app->db->quote($u['title']);
                        $type = $app->db->quote($u['type']);
                        
                        // Check that we do not have this update in the database yet
                        $sql = "SELECT * FROM software_update WHERE package_name = '$package_name' and v1 = '$v1' and v2 = '$v2' and v3 = '$v3' and v4 = '$v4'";
                        $tmp = $app->db->queryOneRecord($sql);
                        if(!isset($tmp['software_update_id'])) {
                            // Insert the update in the datbase
                            //$sql = "INSERT INTO software_update (software_repo_id, package_name, update_url, update_md5, update_dependencies, update_title, v1, v2, v3, v4, type) 
                            //VALUES ($software_repo_id, '$package_name', '$update_url', '$update_md5', '$update_dependencies', '$update_title', '$v1', '$v2', '$v3', '$v4', '$type')";
                            //die($sql);
                            //$app->db->query($sql);
							$insert_data = "(software_repo_id, package_name, update_url, update_md5, update_dependencies, update_title, v1, v2, v3, v4, type) 
                            VALUES ($software_repo_id, '$package_name', '$update_url', '$update_md5', '$update_dependencies', '$update_title', '$v1', '$v2', '$v3', '$v4', '$type')";
							$app->db->datalogInsert('software_update', $insert_data, 'software_update_id');
                        }
                        
                    }
                }
            }
        }
	}
}

//* Install packages, if GET Request
/*
if(isset($_GET['action']) && $_GET['action'] == 'install' && $_GET['package'] != '' && $_GET['server_id'] > 0) {
	$package_name = $app->db->quote($_GET['package']);
	$server_id = intval($_GET['server_id']);
	$sql = "SELECT software_update_id, package_name, update_title FROM software_update WHERE type = 'full' AND package_name = '$package_name' ORDER BY v1 DESC, v2 DESC, v3 DESC, v4 DESC LIMIT 0,1";
	$tmp = $app->db->queryOneRecord($sql);
	$software_update_id = $tmp['software_update_id'];
	
	$insert_data = "(package_name, server_id, software_update_id, status) VALUES ('$package_name', '$server_id', '$software_update_id','installing')";
	// $insert_data = "(package_name, server_id, software_update_id, status) VALUES ('$package_name', '$server_id', '$software_update_id','installed')";
	$app->db->datalogInsert('software_update_inst', $insert_data, 'software_update_inst_id');
}
*/



// Show the list in the interface
// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/software_package_list.htm');


$servers = $app->db->queryAllRecords('SELECT server_id, server_name FROM server ORDER BY server_name');
$packages = $app->db->queryAllRecords('SELECT * FROM software_package');
if(is_array($packages)) {
	foreach($packages as $key => $p) {
		$installed_txt = '';
		foreach($servers as $s) {
			$inst = $app->db->queryOneRecord("SELECT * FROM software_update, software_update_inst WHERE software_update_inst.software_update_id = software_update.software_update_id AND software_update_inst.package_name = '".addslashes($p["package_name"])."' AND server_id = '".$s["server_id"]."'");
			$version = $inst['v1'].'.'.$inst['v2'].'.'.$inst['v3'].'.'.$inst['v4'];
			
			if($inst['status'] == 'installed') {
				$installed_txt .= $s['server_name'].": ".$app->lng("Installed version $version")."<br />";
            } elseif ($inst['status'] == 'installing') {
                $installed_txt .= $s['server_name'].": ".$app->lng("Installation in progress")."<br />";
            } elseif ($inst['status'] == 'failed') {
                $installed_txt .= $s['server_name'].": ".$app->lng("Installation failed")."<br />";
			} elseif ($inst['status'] == 'deleting') {
				$installed_txt .= $s['server_name'].": ".$app->lng("Deletion in progress")."<br />";
			} else {
				if($p['package_installable'] == 'no') {
					$installed_txt .= $s['server_name'].": ".$app->lng("Package can not be installed.")."<br />";
				} else {
					$installed_txt .= $s['server_name'].": <a href=\"#\" onClick=\"loadContent('admin/software_package_install.php?package=".$p["package_name"]."&server_id=".$s["server_id"]."');\">Install now</a><br />";
				}
			}
		}
		$packages[$key]['installed'] = $installed_txt;
	}
}



$app->tpl->setLoop('records',$packages);

include_once('lib/lang/en_software_package_list.lng');
$app->tpl->setVar($wb);


$app->tpl_defaults();
$app->tpl->pparse();


?>