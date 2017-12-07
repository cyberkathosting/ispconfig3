<?php

if(!defined('INSTALLER_RUN')) die('Patch update file access violation.');

/*
	Example installer patch update class. the classname must match
	the php and the sql patch update filename. The php patches are
	only executed when a corresponding sql patch exists.
*/

class upd_0085 extends installer_patch_update {

	public function onAfterSQL() {
		global $inst, $conf;
		
		$cron_files = $conf['cron']['crontab_dir'] . '/ispc_*';
		$check_suffix = '';
		if (file_exists('/etc/gentoo-release')) {
			$cron_files .= '.cron';
			$check_suffix = '.cron';
		}
		
		$file_list = glob($cron_files);
		if(is_array($file_list) && !empty($file_list)) {
			for($f = 0; $f < count($file_list); $f++) {
				$cron_file = $file_list[$f];
				$fp = fopen($cron_file, 'r');
				while($fp && !feof($fp)) {
					$line = trim(fgets($fp));
					if($line == '') continue;
					elseif(substr($line, 0, 1) === '#') continue; // commented out
					
					$fields = preg_split('/\s+/', $line);
					if(trim($fields[0]) == '') {
						// invalid line
						swriteln($inst->lng('[INFO] Invalid cron line in file ' . $cron_file));
					} elseif(preg_match('/^\w+=/', $line)) {
						if(preg_match('/\s/', $line)) {
							// warning line with env var and space!
							swriteln($inst->lng("\n" . '[WARNING] Cron line in file ' . $cron_file . ' contains environment variable.' . "\n"));
						}
					} elseif(!isset($fields[5])) {
						// invalid line (missing user)
							swriteln($inst->lng("\n" . '[WARNING] Cron line in file ' . $cron_file . ' misses user field.' . "\n"));
					} else {
						$check_filename = trim($fields[5]) . $check_suffix;
						if(substr($cron_file, -strlen($check_filename)) != $check_filename) {
							// warning user not equal to file name
							swriteln($inst->lng("\n" . '[WARNING] SUSPECT USER IN CRON FILE ' . $cron_file . '! CHECK CRON FILE FOR MALICIOUS ENTRIES!' . "\n"));
						}
					}
				}
				fclose($fp);
			}
		}
	}
}

?>
