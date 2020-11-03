<?php

if(!defined('INSTALLER_RUN')) die('Patch update file access violation.');

/*
        Example installer patch update class. the classname must match
        the php and the sql patch update filename. The php patches are
        only executed when a corresponding sql patch exists.
*/

class upd_dev extends installer_patch_update {

	public function onAfterSQL() {
		global $inst, $conf;
	}
}

?>

