<?php

/**
 * Base class for app installer
 *
 * @author Marius Burkard
 */
class ispconfig_addon_installer_base {
	
	protected $addon_name;
	protected $addon_ident;
	protected $addon_version;
	
	protected $temp_dir;
	
	public function __construct() {
		$this->addon_ident = preg_replace('/_addon_installer$/', '', get_called_class());
	}
	
	public function setAddonName($name) {
		$this->addon_name = $name;
	}
	
	public function setAddonIdent($ident) {
		$this->addon_ident = $ident;
	}
	
	public function setAddonVersion($version) {
		$this->addon_version = $version;
	}
	
	public function setAddonTempDir($path) {
		$this->temp_dir = $path;
	}
	
	protected function copyInterfaceFiles() {
		global $conf;
		
		$install_dir = realpath($conf['rootpath'] . '/..');
		
		if(is_dir($this->temp_dir . '/interface')) {
			$ret = null;
			$retval = 0;
			$command = 'cp -rf ' . escapeshellarg($this->temp_dir . '/interface') . ' ' . escapeshellarg($install_dir . '/');
			exec($command, $ret, $retval);
			if($retval != 0) {
				throw new AddonInstallerException('Command ' . $command . ' failed with code ' . $retval);
			}
			
			return true;
		} else {
			return false;
		}
	}
	
	protected function copyServerFiles() {
		global $conf;
		
		$install_dir = realpath($conf['rootpath'] . '/..');
		
		if(is_dir($this->temp_dir . '/server')) {
			$ret = null;
			$retval = 0;
			$command = 'cp -rf ' . escapeshellarg($this->temp_dir . '/server'). ' ' . escapeshellarg($install_dir . '/');
			exec($command, $ret, $retval);
			if($retval != 0) {
				throw new AddonInstallerException('Command ' . $command . ' failed with code ' . $retval);
			}
			return true;
		} else {
			return false;
		}		
	}
	
	protected function copyAddonFiles() {
		global $app, $conf;
		
		$install_dir = realpath($conf['rootpath'] . '/..') . '/addons/' . $this->addon_ident;
		if(!is_dir($install_dir)) {
			if(!$app->system->mkdir($install_dir, false, 0750, true)) {
				throw new AddonInstallerException('Could not create addons dir ' . $install_dir);
			}
		}
		
		if(is_dir($this->temp_dir . '/install')) {
			$ret = null;
			$retval = 0;
			$command = 'cp -rf ' . escapeshellarg($this->temp_dir . '/addon.ini') . ' ' . escapeshellarg($this->temp_dir . '/' . $this->addon_ident . 'addon.php') . ' ' . escapeshellarg($this->temp_dir . '/install'). ' ' . escapeshellarg($install_dir . '/');
			exec($command, $ret, $retval);
			if($retval != 0) {
				throw new AddonInstallerException('Command ' . $command . ' failed with code ' . $retval);
			}
			
			return true;
		} else {
			return false;
		}		
	}
	
	protected function executeSqlStatements() {
		global $app, $conf;
		
		// create addon entry if not existing
		$qry = 'INSERT IGNORE INTO `addons` (`addon_ident`, `addon_version`, `addon_name`, `db_version`) VALUES (?, ?, ?, ?)';
		$app->db->query($qry, $this->addon_ident, $this->addon_version, $this->addon_name, 0);
		
		$incremental = false;
		$check = $app->db->queryOneRecord('SELECT `db_version` FROM `addons` WHERE `addon_ident` = ?', $this->addon_ident);
		if($check) {
			$incremental = 0;
			if($check['db_version']) {
				$incremental = $check['db_version'];
			}
		}
		
		
		$mysql_command = 'mysql --default-character-set=' . escapeshellarg($conf['db_charset']) . ' --force -h ' . escapeshellarg($conf['db_host']) . ' -u ' . escapeshellarg($conf['db_user']) . ' -p' . escapeshellarg($conf['db_password']) . ' -P ' . escapeshellarg($conf['db_port']) . ' -D ' . escapeshellarg($conf['db_database']);
		
		if($incremental === false) {
			$sql_file = $this->temp_dir . '/install/sql/' . $this->addon_ident . '.sql';
			if(is_file($sql_file)) {
				$ret = null;
				$retval = 0;
				exec($mysql_command . ' < ' . escapeshellarg($sql_file), $ret, $retval);
				if($retval != 0) {
					/* TODO: log error! */
				}
			}
		} else {
			$new_db_version = $incremental;
			while(true) {
				$sql_file = $this->temp_dir . '/install/sql/incremental/upd_' . str_pad($new_db_version + 1, '0', 5, STR_PAD_LEFT) . '.sql';
				if(!is_file($sql_file)) {
					break;
				} else {
					$ret = null;
					$retval = 0;
					exec($mysql_command . ' < ' . escapeshellarg($sql_file), $ret, $retval);
					if($retval != 0) {
						/* TODO: log error! */
					}
				}
				
				$new_db_version++;
			}
			
			$app->db->query('UPDATE `addons` SET `addon_version` = ?, `db_version` = ? WHERE `addon_ident` = ?', $this->addon_version, $new_db_version, $this->addon_ident);
		}
		
		return true;
	}
	
	public function onBeforeInstall() { }
	
	public function onInstall() {
		$this->copyAddonFiles();
		$this->copyInterfaceFiles();
		$this->copyServerFiles();
		$this->executeSqlStatements();
	}
	
	public function onAfterInstall() { }
	
	public function onBeforeUpdate() { }
	
	public function onUpdate() {
		$this->copyAddonFiles();
		$this->copyInterfaceFiles();
		$this->copyServerFiles();
		$this->executeSqlStatements();
	}
	
	public function onAfterUpdate() { }
	
	
	
	public function onRaisedInstallerEvent($event_name) {
		
	}
}

class AddonInstallerException extends Exception {
	public function __construct($message = "", $code = 0, $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

class AddonInstallerValidationException extends AddonInstallerException { }