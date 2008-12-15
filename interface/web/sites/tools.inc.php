<?php
function convertClientName($name){
	/**
	 *  only allow 'a'..'z', '_', '0'..'9'
	 */
	$allowed = 'abcdefghijklmnopqrstuvwxyz0123456789_';
	$res = '';
	$name = strtolower(trim($name));
	for ($i=0; $i < strlen($name); $i++){
		if ($name[$i] == ' ') continue;
		if (strpos($allowed, $name[$i]) !== false){
			$res .= $name[$i];
		}
		else {
			$res .= '_';
		}
	}
	return $res;
}


?>
