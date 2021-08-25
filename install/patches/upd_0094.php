<?php

if(!defined('INSTALLER_RUN')) die('Patch update file access violation.');

class upd_0094 extends installer_patch_update {

	public function onBeforeSQL() {
		global $inst;

		// Remove any duplicate mail_forwardings prior to adding unique key
		$inst->db->query("DELETE FROM mail_forwarding WHERE forwarding_id IN (SELECT forwarding_id FROM (SELECT forwarding_id, COUNT(source) AS source_count FROM mail_forwarding GROUP BY source HAVING source_count > 1) as t1)");
	}

}
