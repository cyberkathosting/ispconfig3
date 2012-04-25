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
	global $app,$page;
	/*
	 * Get the master-template for the client
	 */
	$sql = "SELECT template_master, template_additional FROM client WHERE client_id = " . intval($clientId);
	$record = $app->db->queryOneRecord($sql);
	$masterTemplateId = $record['template_master'];
	$additionalTemplateStr = $record['template_additional'];

	/*
	 * if the master-Template is custom there is NO changing
	 */
	if ($masterTemplateId > 0){
		$sql = "SELECT * FROM client_template WHERE template_id = " . intval($masterTemplateId);
		$limits = $app->db->queryOneRecord($sql);
	} else {
		$limits = $page->dataRecord;
	}

	/*
	 * Process the additional tempaltes here (add them to the limits
	 * if != -1)
	 */
	$addTpl = explode('/', $additionalTemplateStr);
	foreach ($addTpl as $item){
		if (trim($item) != ''){
			$sql = "SELECT * FROM client_template WHERE template_id = " . intval($item);
			$addLimits = $app->db->queryOneRecord($sql);
			/* maybe the template is deleted in the meantime */
			if (is_array($addLimits)){
				foreach($addLimits as $k => $v){
					/* we can remove this condition, but it is easier to debug with it (don't add ids and other non-limit values) */
					if (strpos($k, 'limit') !== false){
						/* process the numerical limits */
						if (is_numeric($v)){
							/* switch for special cases */
							switch ($k){
							case 'limit_cron_frequency':
								if ($v < $limits[$k]) $limits[$k] = $v;
								/* silent adjustment of the minimum cron frequency to 1 minute */
								/* maybe this control test should be done via validator definition in tform.php file, but I don't know how */
								if ($limits[$k] < 1) $limits[$k] = 1;
							break;

							default:
								if ($limits[$k] > -1){
									if ($v == -1){
										$limits[$k] = -1;
									}
									else {
										$limits[$k] += $v;
									}
								}
							}
						}
						/* process the string limits (CHECKBOXARRAY, SELECT etc.) */
						elseif (is_string($v)){
							switch ($app->tform->formDef["tabs"]["limits"]["fields"][$k]['formtype']){
							case 'CHECKBOXARRAY':
								if (!isset($limits[$k])){
									$limits[$k] = array();
								}

								$limits_values = $limits[$k];
								if (is_string($limits[$k])){
									$limits_values = explode($app->tform->formDef["tabs"]["limits"]["fields"][$k]["separator"],$limits[$k]);
								}
								$additional_values = explode($app->tform->formDef["tabs"]["limits"]["fields"][$k]["separator"],$v);

								/* unification of limits_values (master template) and additional_values (additional template) */
								$limits_unified = array();
								foreach($app->tform->formDef["tabs"]["limits"]["fields"][$k]["value"] as $key => $val){
									if (in_array($key,$limits_values) || in_array($key,$additional_values)) $limits_unified[] = $key;
								}
								$limits[$k] = implode($app->tform->formDef["tabs"]["limits"]["fields"][$k]["separator"],$limits_unified);
							break;
							
							case 'SELECT':
								$limit_values = array_keys($app->tform->formDef["tabs"]["limits"]["fields"][$k]["value"]);
								/* choose the lower index of the two SELECT items */
								$limits[$k] = $limit_values[min(array_search($limits[$k], $limit_values), array_search($v, $limit_values))];
							break;
							}
						}
					}
				}
			}
		}
	}

	/*
	 * Write all back to the database
	 */
	$update = '';
	foreach($limits as $k => $v){
		if ((strpos($k, 'limit') !== false or $k == 'ssh_chroot' or $k == 'web_php_options') && !is_array($v)){
			if ($update != '') $update .= ', ';
			$update .= '`' . $k . "`='" . $v . "'";
		}
	}
	if($update != '') $sql = 'UPDATE client SET ' . $update . " WHERE client_id = " . intval($clientId);
	$app->db->query($sql);
}
?>
