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

$tform_def_file = "form/dns_naptr.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';
require_once './dns_edit_base.php';

// Loading classes
class page_action extends dns_page_action {

	function onSubmit() {
		// Combine and escape/format fields into the data to be saved
		$this->dataRecord['data'] = $this->dataRecord['pref'] .' '.
			'"'. $this->zoneFileEscape( $this->dataRecord['flags'] ) .'" '.
			'"'. $this->zoneFileEscape( $this->dataRecord['service'] ) .'" '.
			'"'. $this->zoneFileEscape( $this->dataRecord['regexp'] ) .'" '.
			$this->dataRecord['replacement'] . (substr( $this->dataRecord['replacement'], -1 ) == '.' ? '' : '.');

		$this->dataRecord['aux'] = $this->dataRecord['order'];

		parent::onSubmit();
	}


	function onShowEnd() {
		global $app, $conf;

		// Split the parts of NAPTR record, unescape (backslashes), and unquote to edit.
		//
		// Examples:
		// ;;       order pref flags service        regexp           replacement
		// IN NAPTR 100   10   ""    ""  "!^cid:.+@([^\.]+\.)(.*)$!\2!i"    .
		//
		// ;;       order pref flags   service  regexp     replacement
		// IN NAPTR 100  100  "s"   "thttp+L2R"   ""    thttp.example.com.
		// IN NAPTR 100  100  "s"   "ftp+L2R"    ""     ftp.example.com.
		//
		// 'order' in stored in 'aux' column,
		// all of 'pref "flags" "service" "regexp" replacement.' is here in 'data'
		//
		$matched = preg_match('/^\s*(\d+)\s+"([a-zA-Z0-9]*)"\s+"([^"]*)"\s+"(.*)"\s+([^\s]*\.)\s*$/', $this->dataRecord['data'], $matches);

		if ($matched === FALSE || is_array($matches) && count($matches) == 0) {
			if ( isset($app->tform->errorMessage) ) {
				$app->tform->errorMessage .= '<br/>' . $app->tform->wordbook["record_parse_error"];
			}
		} else {
			$app->tpl->setVar('pref',        $matches[1], true);
			$app->tpl->setVar('flags',       $this->zoneFileUnescape($matches[2]), true);
			$app->tpl->setVar('service',     $this->zoneFileUnescape($matches[3]), true);
			$app->tpl->setVar('regexp',      $this->zoneFileUnescape($matches[4]), true);
			$app->tpl->setVar('replacement', $matches[5], true);
		}

		parent::onShowEnd();
	}

}

$page = new page_action;
$page->onLoad();

?>
