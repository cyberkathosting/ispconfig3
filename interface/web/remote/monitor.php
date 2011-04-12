<?php
require_once('../../lib/config.inc.php');
$conf['start_session'] = false;
require_once('../../lib/app.inc.php');

if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past

$type = addslashes($_GET['type']);
$token = addslashes($_GET['token']);
$server_id = intval($_GET['server']);

$app->uses('getconf');
$interface_config = $app->getconf->get_global_config('misc');
$secret = $interface_config['monitor_key'];

$out = array();

if($token == '' or $secret == '' or $token != $secret) {
	$out['state'] = 'syserror';
	$out['data'] = 'Password empty or incorrect.';
	$out['time'] = date('Y-m-d H:i');
} else {
	if($type == 'serverlist') {
		$sql = 'SELECT server_id, server_name FROM server WHERE 1 ORDER BY server_id';
		$records = $app->db->queryAllRecords($sql);
		$out['state'] = 'ok';
		$out['data'] = $records;
		$out['time'] = date('Y-m-d H:i',$rec['created']);
	} else {
		$rec = $app->db->queryOneRecord("SELECT * FROM monitor_data WHERE type = '$type' AND server_id = $server_id");
		if(is_array($rec)) {
			$out['state'] = $rec['state'];
			$out['data'] = unserialize(stripslashes($rec['data']));
			if(is_array($out['data']) && sizeof($out['data']) > 0){
				foreach($out['data'] as $key => $val){
					if(!$val) $out['data'][$key] = "&nbsp;";
				}
			}
			$out['time'] = date('Y-m-d H:i',$rec['created']);
		} else {
			$out['state'] = 'syserror';
			$out['data'] = 'No monitor record found.';
			$out['time'] = date('Y-m-d H:i');
		}
		$sql = 'SELECT server_id, server_name FROM server WHERE 1 ORDER BY server_id';
		$records = $app->db->queryAllRecords($sql);
		$out['serverlist'] = $records;
	}
}
$out['type'] = $type;

echo json_encode($out);
exit;
?>