<?php

/**
 * addon installer
 *
 * @author Marius Burkard
 */
class addon_installer {
	
	private function extractPackage($package_file) {
		global $app;
		
		$ret = null;
		$retval = 0;
		
		$cmd = 'which unzip';
		$tmp = explode("\n", exec($cmd, $ret, $retval));
		if($retval != 0) {
			throw new AddonInstallerException('unzip tool not found.');
		}
		$unzip = reset($tmp);
		unset($tmp);
		if(!$unzip) {
			throw new AddonInstallerException('unzip tool not found.');
		}
		
		$temp_dir = $app->system->tempdir(sys_get_temp_dir(), 'addon_', 0700);
		if(!$temp_dir) {
			throw new AddonInstallerException('Could not create temp dir.');
		}
		
		$ret = null;
		$retval = 0;
		$cmd = $unzip . ' -d ' . escapeshellarg($temp_dir) . ' ' . escapeshellarg($package_file);
		exec($cmd, $ret, $retval);
		if($retval != 0) {
			throw new AddonInstallerException('Package extraction failed.');
		}
		
		return $temp_dir;
	}

	/**
	 * @param string $path
	 * @return string
	 * @throws AddonInstallerValidationException
	 */
	private function validatePackage($path) {
		if(!is_dir($path)) {
			throw new AddonInstallerValidationException('Invalid path.');
		}
		
		$ini_file = $path . '/addon.ini';
		if(!is_file($ini_file)) {
			throw new AddonInstallerValidationException('Addon ini file missing.');
		}
		
		$ini = parse_ini_file($ini_file, true);
		if(!$ini || !isset($ini['addon'])) {
			throw new AddonInstallerValidationException('Ini file is missing addon section.');
		}
		
		$addon = $ini['addon'];
		if(!isset($addon['ident']) || !isset($addon['name']) || !isset($addon['version'])) {
			throw new AddonInstallerValidationException('Ini file is missing addon ident/name/version.');
		}
		
		$class_file = $path . '/' . $addon['ident'] . '.addon.php';
		if(!is_file($class_file)) {
			throw new AddonInstallerValidationException('Package is missing main addon class.');
		}
		
		if(isset($ini['ispconfig']['version.min']) && $ini['ispconfig']['version.min'] && version_compare($ini['ispconfig']['version.min'], ISPC_APP_VERSION, '>')) {
			throw new AddonInstallerValidationException('Addon requires at least ISPConfig version ' . $ini['ispconfig']['version.min'] . '.');
		} elseif(isset($ini['ispconfig']['version.max']) && $ini['ispconfig']['version.max'] && version_compare($ini['ispconfig']['version.min'], ISPC_APP_VERSION, '<')) {
			throw new AddonInstallerValidationException('Addon allows at max ISPConfig version ' . $ini['ispconfig']['version.max'] . '.');
		}
		
		$addon['class_file'] = $class_file;
		$addon['class_name'] = substr(basename($class_file), 0, -10) . '_addon_installer';
		
		return $addon;
	}
	
	private function getInstalledAddonVersion($ident) {
		global $app, $conf;
		
		$file_version = false;
		$db_version = false;
		
		$addon_path = realpath($conf['rootpath'] . '/..') . '/addons';
		// check for previous version
		if(is_dir($addon_path . '/' . $ident) && is_file($addon_path . '/' . $ident . '/addon.ini')) {
			$addon = parse_ini_file($addon_path . '/' . $ident . '/addon.ini', true);
			if(!$addon || !isset($addon['version']) || !isset($addon['ident']) || $addon['ident'] != $ident) {
				throw new AddonInstallerException('Installed app found but it is invalid.');
			}
			
			$file_version = $addon['version'];
		}
		
		$check = $app->db->queryOneRecord('SELECT `addon_version` FROM `addons` WHERE `addon_ident` = ?', $ident);
		if($check && $check['addon_version']) {
			$db_version = $check['addon_version'];
		}
		
		if(!$file_version && !$db_version) {
			return false;
		} elseif($file_version != $db_version) {
			throw new AddonInstallerException('Addon version mismatch in database (' . $db_version . ') and file system (' . $file_version . ').');
		}
		
		return $file_version;

	}
	
	/**
	 * @param string $package_file Full path
	 * @param boolean $force true if previous addon with same or higher version should be overwritten
	 * @throws AddonInstallerException
	 * @throws AddonInstallerValidationException
	 */
	public function installAddon($package_file, $force = false) {
		global $app;
		
		$app->load('ispconfig_addon_installer_base');
		
		if(!is_file($package_file)) {
			throw new AddonInstallerException('Package file not found.');
		} elseif(substr($package_file, -4) !== '.pkg') {
			throw new AddonInstallerException('Invalid package file.');
		}
		
		$tmp_dir = $this->extractPackage($package_file);
		if(!$tmp_dir) {
			// extracting failed
			throw new AddonInstallerException('Package extraction failed.');
		}
		
		$addon = $this->validatePackage($tmp_dir);
		if(!$addon) {
			throw new AddonInstallerException('Package validation failed.');
		}
		
		$is_update = false;
		$previous = $this->getInstalledAddonVersion($addon['ident']);
		if($previous !== false) {
			// this is an update
			if(version_compare($previous, $addon['version'], '>') && $force !== true) {
				throw new AddonInstallerException('Installed version is newer than the one to install.');
			} elseif(version_compare($previous, $addon['version'], '=') && $force !== true) {
				throw new AddonInstallerException('Installed version is the same as the one to install.');
			}
			$is_update = true;
		}
		include $addon['class_file'];
		if(!class_exists($addon['class_name'])) {
			throw new AddonInstallerException('Could not find main class in addon file.');
		}
		
		$class_name = $addon['class_name'];
		/* @var $inst ispconfig_addon_installer_base */
		$inst = new $class_name();
		$inst->setAddonName($addon['name']);
		$inst->setAddonIdent($addon['ident']);
		$inst->setAddonVersion($addon['version']);
		$inst->setAddonTempDir($tmp_dir);
		
		if($is_update === true) {
			$inst->onBeforeUpdate();
			$inst->onUpdate();
			$inst->onAfterUpdate();
		} else {
			$inst->onBeforeInstall();
			$inst->onInstall();
			$inst->onAfterInstall();
		}
		
		return true;
	}
	
}
