<?php

/**
 * Base class for app installer
 * This is a stripped down class with only the event method. The full class is only used in /server/lib/classes
 *
 * @author Marius Burkard
 */
class ispconfig_addon_installer_base {
	
	protected $addon_ident;
	
	public function __construct() {
		$this->addon_ident = preg_replace('/_addon_installer$/', '', get_called_class());
	}
	
	public function onRaisedInstallerEvent($event_name, $data) {
		
	}
}
