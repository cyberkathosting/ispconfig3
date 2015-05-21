<?php
require_once '../../lib/config.inc.php';
$conf['start_session'] = false;
require_once '../../lib/app.inc.php';

if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past

$type = addslashes($_GET['type']);
$token = addslashes($_GET['token']);
$server_id = $app->functions->intval($_GET['server']);

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
		foreach($records as $index => $rec) {
			$rec = $app->db->queryOneRecord("SELECT * FROM monitor_data WHERE server_id = ? AND state NOT IN ('ok', 'no_state', 'info')", $rec['server_id']);
			if($rec) $records[$index]['state'] = 'warn';
			else $records[$index]['state'] = 'ok';
		}
		$out['state'] = 'ok';
		$out['data'] = $records;
		$out['time'] = date('Y-m-d H:i', $rec['created']);
	} else {
		$rec = $app->db->queryOneRecord("SELECT * FROM monitor_data WHERE type = ? AND server_id = ?", $type, $server_id);
		if(is_array($rec)) {
			$out['state'] = $rec['state'];
			$out['data'] = unserialize(stripslashes($rec['data']));
			if(is_array($out['data']) && sizeof($out['data']) > 0){
				foreach($out['data'] as $key => $val){
					if(!$val) $out['data'][$key] = "&nbsp;";
				}
			}
			$out['time'] = date('Y-m-d H:i', $rec['created']);
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

function __json_encode($data) {
	if( is_array($data) || is_object($data) ) {
		$islist = is_array($data) && ( empty($data) || array_keys($data) === range(0, count($data)-1) );

		if( $islist ) {
			$json = '[' . implode(',', array_map('__json_encode', $data) ) . ']';
		} else {
			$items = array();
			foreach( $data as $key => $value ) {
				$items[] = __json_encode("$key") . ':' . __json_encode($value);
			}
			$json = '{' . implode(',', $items) . '}';
		}
	} elseif( is_string($data) ) {
		// Escape non-printable or Non-ASCII characters.
		// I also put the \\ character first, as suggested in comments on the 'addcslashes' page.
		$string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';
		$json    = '';
		$len    = strlen($string);
		// Convert UTF-8 to Hexadecimal Codepoints.
		for( $i = 0; $i < $len; $i++ ) {

			$char = $string[$i];
			$c1 = ord($char);

			// Single byte;
			if( $c1 <128 ) {
				$json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
				continue;
			}

			// Double byte
			$c2 = ord($string[++$i]);
			if ( ($c1 & 32) === 0 ) {
				$json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
				continue;
			}

			// Triple
			$c3 = ord($string[++$i]);
			if( ($c1 & 16) === 0 ) {
				$json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128));
				continue;
			}

			// Quadruple
			$c4 = ord($string[++$i]);
			if( ($c1 & 8 ) === 0 ) {
				$u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1;

				$w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3);
				$w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128);
				$json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
			}
		}
	} else {
		// int, floats, bools, null
		$json = strtolower(var_export( $data, true ));
	}
	return $json;
}

if(function_exists('json_encode')) { // PHP >= 5.2
	echo json_encode($out);
} else { // PHP < 5.2
	echo __json_encode($out);
}
exit;
?>
