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
		
		$app->log('Extracting addon package ' . $package_file, 0, false);
		
		$cmd = 'which unzip';
		$tmp = explode("\n", exec($cmd, $ret, $retval));
		if($retval != 0) {
			$app->log('The unzip command was not found on the server.', 2, false);
			throw new AddonInstallerException('unzip tool not found.');
		}
		$unzip = reset($tmp);
		unset($tmp);
		if(!$unzip) {
			$app->log('Unzip tool was not found.', 2, false);
			throw new AddonInstallerException('unzip tool not found.');
		}
		
		$temp_dir = $app->system->tempdir(sys_get_temp_dir(), 'addon_', 0700);
		if(!$temp_dir) {
			$app->log('Could not create the temp dir.', 2, false);
			throw new AddonInstallerException('Could not create temp dir.');
		}
		
		$ret = null;
		$retval = 0;
		$cmd = $unzip . ' -d ' . escapeshellarg($temp_dir) . ' ' . escapeshellarg($package_file);
		exec($cmd, $ret, $retval);
		if($retval != 0) {
			$app->log('Package extraction failed.', 2, false);
			throw new AddonInstallerException('Package extraction failed.');
		}
		
		$app->log('Extracted to ' . $temp_dir, 0, false);
		
		return $temp_dir;
	}

	/**
	 * @param string $path
	 * @return string
	 * @throws AddonInstallerValidationException
	 */
	private function validatePackage($path) {
		global $app;
		
		$app->log('Validating extracted addon at ' . $path, 0, false);
		
		if(!is_dir($path)) {
			$app->log('Invalid path.', 2, false);
			throw new AddonInstallerValidationException('Invalid path.');
		}
		
		$ini_file = $path . '/addon.ini';
		if(!is_file($ini_file)) {
			$app->log('Addon ini file missing.', 2, false);
			throw new AddonInstallerValidationException('Addon ini file missing.');
		}
		
		$app->log('Parsing ini ' . $ini_file, 0, false);
		$ini = parse_ini_file($ini_file, true);
		if(!$ini || !isset($ini['addon'])) {
			$app->log('Ini file could not be read.', 2, false);
			throw new AddonInstallerValidationException('Ini file is missing addon section.');
		}
		
		$addon = $ini['addon'];
		if(!isset($addon['ident']) || !isset($addon['name']) || !isset($addon['version'])) {
			$app->log('Addon data in ini file missing or invalid.', 2, false);
			throw new AddonInstallerValidationException('Ini file is missing addon ident/name/version.');
		}
		
		$class_file = $path . '/' . $addon['ident'] . '.addon.php';
		if(!is_file($class_file)) {
			$app->log('Base class file in addon not found', 2, false);
			throw new AddonInstallerValidationException('Package is missing main addon class.');
		}
		
		if(isset($ini['ispconfig']['version.min']) && $ini['ispconfig']['version.min'] && version_compare($ini['ispconfig']['version.min'], ISPC_APP_VERSION, '>')) {
			$app->log('ISPConfig version too low for this addon.', 2, false);
			throw new AddonInstallerValidationException('Addon requires at least ISPConfig version ' . $ini['ispconfig']['version.min'] . '.');
		} elseif(isset($ini['ispconfig']['version.max']) && $ini['ispconfig']['version.max'] && version_compare($ini['ispconfig']['version.min'], ISPC_APP_VERSION, '<')) {
			$app->log('ISPConfig version too high for this addon.', 2, false);
			throw new AddonInstallerValidationException('Addon allows at max ISPConfig version ' . $ini['ispconfig']['version.max'] . '.');
		}
		
		$app->log('Loaded addon installer ' . $class_file, 0, false);
		
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
			if($addon && isset($addon['addon'])) {
				$addon = $addon['addon']; // ini section
			} else {
				$addon = false;
			}
			if(!$addon || !isset($addon['version']) || !isset($addon['ident']) || $addon['ident'] != $ident) {
				$app->log('Could not get version of installed addon.', 2, false);
				throw new AddonInstallerException('Installed app ' . $ident . ' found but it is invalid.');
			}
			
			$file_version = $addon['version'];
			$app->log('Installed version of addon ' . $ident . ' is ' . $file_version, 0, false);
		}
		
		$check = $app->db->queryOneRecord('SELECT `addon_version` FROM `addons` WHERE `addon_ident` = ?', $ident);
		if($check && $check['addon_version']) {
			$db_version = $check['addon_version'];
			$app->log('Installed version of addon ' . $ident . ' (in db) is ' . $db_version . '.', 0, false);
		}
		
		if(!$file_version && !$db_version) {
			return false;
		} elseif($file_version != $db_version) {
			$app->log('Version mismatch between ini file and database (' . $file_version . ' != ' . $db_version . ').', 0, false);
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
			$app->log('Package file not found: ' . $package_file, 2, false);
			throw new AddonInstallerException('Package file not found.');
		} elseif(substr($package_file, -4) !== '.pkg') {
			$app->log('Invalid package file: ' . $package_file, 2, false);
			throw new AddonInstallerException('Invalid package file.');
		}
		
		$tmp_dir = $this->extractPackage($package_file);
		if(!$tmp_dir) {
			// extracting failed
			$app->log('Package extraction failed.', 2, false);
			throw new AddonInstallerException('Package extraction failed.');
		}
		
		$addon = $this->validatePackage($tmp_dir);
		if(!$addon) {
			throw new AddonInstallerException('Package validation failed.');
		}
		$app->log('Package validated.', 0, false);
		
		$is_update = false;
		$previous = $this->getInstalledAddonVersion($addon['ident']);
		if($previous !== false) {
			// this is an update
			if(version_compare($previous, $addon['version'], '>') && $force !== true) {
				$app->log('Installed version is newer than the one to install and --force not used.', 2, false);
				throw new AddonInstallerException('Installed version is newer than the one to install.');
			} elseif(version_compare($previous, $addon['version'], '=') && $force !== true) {
				$app->log('Installed version is the same as the one to install and --force not used.', 2, false);
				throw new AddonInstallerException('Installed version is the same as the one to install.');
			}
			$is_update = true;
		}
		
		$app->log('Including package class file ' . $addon['class_file'], 0, false);
		
		include $addon['class_file'];
		$class_name = $addon['class_name'];
		if(!class_exists($class_name)) {
			$app->log('Class name ' . $class_name . ' not found in class file ' . $addon['class_file'], 2, false);
			throw new AddonInstallerException('Could not find main class in addon file.');
		}
		
		/* @var $inst ispconfig_addon_installer_base */
		$app->log('Instanciating installer class ' . $class_name, 0, false);
		
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
		
		exec('rm -rf ' . escapeshellarg($tmp_dir));
		
		$app->log('Installation completed.', 0, false);
		return true;
	}
	
}
