<?php

/*
Copyright (c) 2012, ISPConfig UG
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
$app->auth->check_module_permissions('dns');

//$app->uses('tform');

$type = $_GET["type"];

//if($_SESSION["s"]["user"]["typ"] == 'admin') {


if($type == 'get_ipv4'){
	//$q = $app->db->quote(trim($_GET["q"]));
	//$authsql = " AND ".$app->tform->getAuthSQL('r');
	//$modules = explode(',', $_SESSION['s']['user']['modules']);

	$result = array();

	// ipv4
	//$result[] = _search('admin', 'server_ip', "AND ip_type = 'IPv4' AND (client_id = 0 OR client_id=".$app->functions->intval($_SESSION['s']['user']['client_id']).")");
	$result[] = $app->functions->suggest_ips('IPv4');

	$json = $app->functions->json_encode($result);
}

if($type == 'get_ipv6'){
	//$q = $app->db->quote(trim($_GET["q"]));
	//$authsql = " AND ".$app->tform->getAuthSQL('r');
	//$modules = explode(',', $_SESSION['s']['user']['modules']);

	$result = array();

	// ipv6
	//$result[] = _search('admin', 'server_ip', "AND ip_type = 'IPv6' AND (client_id = 0 OR client_id=".$app->functions->intval($_SESSION['s']['user']['client_id']).")");
	$result[] = $app->functions->suggest_ips('IPv6');

	$json = $app->functions->json_encode($result);
}

//}

/*
function _search($module, $section, $additional_sql = '', $unique = false){
	global $app, $q, $authsql, $modules;

	$result_array = array('cheader' => array(), 'cdata' => array());
	if(in_array($module, $modules) || ($module == 'admin' && $section == 'server_ip')){
		$search_fields = array();
		$desc_fields = array();
		if(is_file('../'.$module.'/form/'.$section.'.tform.php')){
			include_once('../'.$module.'/form/'.$section.'.tform.php');

			$category_title = $form["title"];
			$form_file = $form["action"];
			$db_table = $form["db_table"];
			$db_table_idx = $form["db_table_idx"];
			$order_by = $db_table_idx;

			if(is_array($form["tabs"]) && !empty($form["tabs"])){
				foreach($form["tabs"] as $tab){
					if(is_array($tab['fields']) && !empty($tab['fields'])){
						foreach($tab['fields'] as $key => $val){
							if(isset($val['searchable']) && $val['searchable'] > 0){
								$search_fields[] = $key." LIKE '%".$q."%'";
								if($val['searchable'] == 1){
									$order_by = $key;
									$title_key = $key;
								}
								if($val['searchable'] == 2){
									$desc_fields[] = $key;
								}
							}
						}
					}
				}
			}
		}
		unset($form);

		$where_clause = '';
		if(!empty($search_fields)){
			$where_clause = implode(' OR ', $search_fields);
		} else {
			// valid SQL query which returns an empty result set
			$where_clause = '1 = 0';
		}
		if($where_clause != '') $where_clause = '('.$where_clause.')';
		if($additional_sql != '') $where_clause .= ' '.$additional_sql.' ';
		$order_clause = '';
		if($order_by != '') $order_clause = ' ORDER BY '.$order_by;

		$sql = "SELECT * FROM ".$db_table." WHERE ".$where_clause.$authsql.$order_clause." LIMIT 0,10";
		$results = $app->db->queryAllRecords($sql);

		if(is_array($results) && !empty($results)){
			$lng_file = '../'.$module.'/lib/lang/'.$_SESSION['s']['language'].'_'.$section.'.lng';
			if(is_file($lng_file)) include($lng_file);
			$result_array['cheader'] = array('title' => $category_title,
											'total' => count($results),
											'limit' => count($results)
											);
			foreach($results as $result){
				$description = '';
				if(!empty($desc_fields)){
					$desc_items = array();
					foreach($desc_fields as $desc_field){
						if($result[$desc_field] != '') $desc_items[] = $wb[$desc_field.'_txt'].': '.$result[$desc_field];
					}
					if(!empty($desc_items)) $description = implode(' - ', $desc_items);
				}

				$result_array['cdata'][] = array(	'title' => $wb[$title_key.'_txt'].': '.$result[$title_key],
													'description' => $description,
													'onclick' => '',
													'fill_text' => $result[$title_key]
												);
			}
			if($unique === true){
				$result_array['cdata'] = array_unique($result_array['cdata']);
				$result_array['cheader']['total'] = $result_array['cheader']['limit'] = count($result_array['cdata']);
			}
		}
	}
	return $result_array;
}
*/

header('Content-type: application/json');
echo $json;
?>
