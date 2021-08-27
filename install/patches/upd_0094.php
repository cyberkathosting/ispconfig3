<?php

if(!defined('INSTALLER_RUN')) die('Patch update file access violation.');

class upd_0094 extends installer_patch_update {

	public function onBeforeSQL() {
		global $inst;

		// Remove any duplicate mail_forwardings prior to adding unique key
		$inst->db->query("DELETE FROM mail_forwarding WHERE forwarding_id NOT IN (SELECT MIN(forwarding_id) FROM mail_forwarding GROUP BY source)");

		// Remove any duplicate mail_transports prior to adding unique key
		$inst->db->query("DELETE FROM mail_transport WHERE transport_id NOT IN (SELECT MIN(transport_id) FROM mail_transport GROUP BY domain, server_id)");
	}

}
