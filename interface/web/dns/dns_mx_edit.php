<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/dns_mx.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';
require_once './dns_edit_base.php';

// Loading classes
class page_action extends dns_page_action {

	function onInsert() {
		global $app, $conf;

		// Check if record is existing already
		$duplicate_mx = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = ? AND name = ? AND type = ? AND data = ? AND ".$app->tform->getAuthSQL('r'), $this->dataRecord["zone"], $this->dataRecord["name"], $this->dataRecord["type"], $this->dataRecord["data"]);
		

		if(is_array($duplicate_mx) && !empty($duplicate_mx)) $app->error($app->tform->wordbook["duplicate_mx_record_txt"]);

		parent::onInsert();
	}

	function onUpdate() {
		global $app, $conf;

		// Check if record is existing already
		$duplicate_mx = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = ? AND name = ? AND type = ? AND data = ? AND id != ? AND ".$app->tform->getAuthSQL('r'), $this->dataRecord["zone"], $this->dataRecord["name"], $this->dataRecord["type"], $this->dataRecord["data"], $this->dataRecord["id"]);

		if(is_array($duplicate_mx) && !empty($duplicate_mx)) $app->error($app->tform->wordbook["duplicate_mx_record_txt"]);

		parent::onUpdate();
	}

}

$page = new page_action;
$page->onLoad();

?>
