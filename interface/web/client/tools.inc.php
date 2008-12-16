<?php
/*
Copyright (c) 2007-2008, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

function applyClientTemplates($clientId){
	global $app;
	/*
	 * Get the master-template for the client
	 */
	$sql = "SELECT template_master FROM client WHERE client_id = " . intval($clientId);
	$record = $app->db->queryOneRecord($sql);
	$masterTemplateId = $record['template_master'];

	/*
	 * if the master-Template is custom there is NO changing
	 */
	if ($masterTemplateId > 0){
		$sql = "SELECT * FROM client_template WHERE template_id = " . intval($masterTemplateId);
		$limits = $app->db->queryOneRecord($sql);
	}

	/*
	 * TODO: Process the additional tempaltes here (add them to the limits
	 * if != -1)
	 * (like $limits['limit_database'] += $limitAdditional)
	 */

	/*
	 * Write all back to the database
	 */
	$update = '';
	foreach($limits as $k => $v){
		if (strpos($k, 'limit') !== false){
			if ($update != '') $update .= ', ';
			$update .= '`' . $k . "`='" . $v . "'";
		}
	}
	$sql = 'UPDATE client SET ' . $update . " WHERE client_id = " . intval($clientId);
	$app->db->query($sql);
}
?>
