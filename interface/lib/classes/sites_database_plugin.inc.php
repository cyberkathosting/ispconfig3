<?php

/*
Copyright (c) 2012, Marius Cramer, pixcept KG
All rights reserved.

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

class sites_database_plugin {

	public function processDatabaseInsert($form_page) {
		global $app;

		$this->processDatabaseUpdate($form_page);
	}

	public function processDatabaseUpdate($form_page) {
		global $app;

		if($form_page->dataRecord["parent_domain_id"] > 0) {
			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $form_page->dataRecord["parent_domain_id"]);

			//* The Database user shall be owned by the same group then the website
			$sys_groupid = $app->functions->intval($web['sys_groupid']);
			$backup_interval = $web['backup_interval'];
			$backup_format_web = $web['backup_format_web'];
			$backup_format_db = $web['backup_format_db'];
			$backup_copies = $app->functions->intval($web['backup_copies']);

			$sql = "UPDATE web_database SET sys_groupid = ?, backup_interval = ?, backup_copies = ?, backup_format_web = ?, backup_format_db = ? WHERE database_id = ?";
			$app->db->query($sql, $sys_groupid, $backup_interval, $backup_copies, $backup_format_web, $backup_format_db, $form_page->id);
		}
	}

	public function processDatabaseDelete($primary_id) {
		global $app;

	}

}

?>
