<?php

/*
Copyright (c) 2007 - 2009, Till Brehm, projektfarm Gmbh
All rights reserved.
Modification (c) 2009, Marius Cramer, pixcept KG

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class pma_symlink_plugin {

	var $plugin_name = 'pma_symlink_plugin';
	var $class_name = 'pma_symlink_plugin';

	var $action;

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		return false;

	}


	/*
	 	This function is called when the plugin is loaded
	*/

	function onLoad() {
		global $app;

		/*
		Register for the events
		*/

		$app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'update');
	}

	function insert($event_name, $data) {
		global $app, $conf;

		$this->action = 'insert';
		// just run the update function
		$this->update($event_name, $data);
	}

	function update($event_name, $data) {
		global $app, $conf;

		if($this->action != 'insert') $this->action = 'update';

		if($data["new"]["type"] != "vhost" && $data["new"]["parent_domain_id"] > 0) {

			$old_parent_domain_id = intval($data["old"]["parent_domain_id"]);
			$new_parent_domain_id = intval($data["new"]["parent_domain_id"]);

			// If the parent_domain_id has been chenged, we will have to update the old site as well.
			if($this->action == 'update' && $data["new"]["parent_domain_id"] != $data["old"]["parent_domain_id"]) {
				$tmp = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ? AND active = 'y'", $old_parent_domain_id);
				$data["new"] = $tmp;
				$data["old"] = $tmp;
				$this->action = 'update';
				$this->update($event_name, $data);
			}

			// This is not a vhost, so we need to update the parent record instead.
			$tmp = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ? AND active = 'y'", $new_parent_domain_id);
			$data["new"] = $tmp;
			$data["old"] = $tmp;
			$this->action = 'update';
		}

		if($data["new"]["document_root"] == '') {
			$app->log("document_root not set", LOGLEVEL_WARN);
			return 0;
		}

		$symlink = true;
		if($data["new"]["php"] == "suphp") $symlink = false;
		elseif($data["new"]["php"] == "cgi" && $data["new"]["suexec"] == "y") $symlink = false;
		elseif($data["new"]["php"] == "fast-cgi" && $data["new"]["suexec"] == "y") $symlink = false;


		if(!is_dir($data["new"]["document_root"]."/web")) mkdir($data["new"]["document_root"].'/web', 0755, true);
		if($symlink == false) {
			if(is_link($data["new"]["document_root"].'/web/phpmyadmin')) unlink($data["new"]["document_root"].'/web/phpmyadmin');
		} else {
			if(!is_link($data["new"]["document_root"].'/web/phpmyadmin')) symlink('/var/www/phpmyadmin', $data["new"]["document_root"].'/web/phpmyadmin');
			else symlink('/var/www/phpmyadmin', $data["new"]["document_root"].'/web/phpmyadmin');
		}
	}


} // end class

?>
