<?php
/**
 * client_templates
 *
 * @author Marius Cramer <m.cramer@pixcept.de> pixcept KG
 * @author (original tools.inc.php) Till Brehm, projektfarm Gmbh
 * @author (original tools.inc.php) Oliver Vogel www.muv.com
 */


class client_templates {


	/**
	 *  - check for old-style templates and change to new style
	 *  - update assigned templates
	 */
	function update_client_templates($clientId, $templates = array()) {
		global $app, $conf;

		if(!is_array($templates)) return false;
		$new_tpl = array();
		$used_assigned = array();
		$needed_types = array();
		$old_style = true;
		foreach($templates as $item) {
			$item = trim($item);
			if($item == '') continue;

			$tpl_id = 0;
			$assigned_id = 0;
			if(strpos($item, ':') === false) {
				$tpl_id = $item;
			} else {
				$old_style = false; // has new-style assigns
				list($assigned_id, $tpl_id) = explode(':', $item, 2);
				if(substr($assigned_id, 0, 1) === 'n') $assigned_id = 0; // newly inserted items
			}
			if(array_key_exists($tpl_id, $needed_types) == false) $needed_types[$tpl_id] = 0;
			$needed_types[$tpl_id]++;

			if($assigned_id > 0) {
				$used_assigned[] = $assigned_id; // for comparison with database
			} else {
				$new_tpl[] = $tpl_id;
			}
		}

		if($old_style == true) {
			// we have to take care of this in an other way
			$in_db = $app->db->queryAllRecords('SELECT `assigned_template_id`, `client_template_id` FROM `client_template_assigned` WHERE `client_id` = ?', $clientId);
			if(is_array($in_db) && count($in_db) > 0) {
				foreach($in_db as $item) {
					if(array_key_exists($item['client_template_id'], $needed_types) == false) $needed_types[$item['client_template_id']] = 0;
					$needed_types[$item['client_template_id']]--;
				}
			}

			foreach($needed_types as $tpl_id => $count) {
				if($count > 0) {
					// add new template to client (includes those from old-style without assigned_template_id)
					for($i = $count; $i > 0; $i--) {
						$app->db->query('INSERT INTO `client_template_assigned` (`client_id`, `client_template_id`) VALUES (?, ?)', $clientId, $tpl_id);
					}
				} elseif($count < 0) {
					// remove old ones
					for($i = $count; $i < 0; $i++) {
						$app->db->query('DELETE FROM `client_template_assigned` WHERE client_id = ? AND client_template_id = ? LIMIT 1', $clientId, $tpl_id);
					}
				}
			}
		} else {
			// we have to take care of this in an other way
			$in_db = $app->db->queryAllRecords('SELECT `assigned_template_id`, `client_template_id` FROM `client_template_assigned` WHERE `client_id` = ?', $clientId);
			if(is_array($in_db) && count($in_db) > 0) {
				// check which templates were removed from this client
				foreach($in_db as $item) {
					if(in_array($item['assigned_template_id'], $used_assigned) == false) {
						// delete this one
						$app->db->query('DELETE FROM `client_template_assigned` WHERE `assigned_template_id` = ?', $item['assigned_template_id']);
					}
				}
			}

			if(count($new_tpl) > 0) {
				foreach($new_tpl as $item) {
					// add new template to client (includes those from old-style without assigned_template_id)
					$app->db->query('INSERT INTO `client_template_assigned` (`client_id`, `client_template_id`) VALUES (?, ?)', $clientId, $item);
				}
			}
		}

		unset($new_tpl);
		unset($in_db);
		unset($templates);
		unset($used_assigned);
		return true;
	}

	function apply_client_templates($clientId) {
		global $app;

		/*
         * Get the master-template for the client
         */
		$sql = "SELECT template_master, template_additional,limit_client FROM client WHERE client_id = ?";
		$record = $app->db->queryOneRecord($sql, $clientId);
		$masterTemplateId = $record['template_master'];
		$is_reseller = ($record['limit_client'] != 0)?true:false;

		include '../client/form/' . ($is_reseller ? 'reseller' : 'client') . '.tform.php';

		if($record['template_additional'] != '') {
			// we have to call the update_client_templates function
			$templates = explode('/', $record['template_additional']);
			$this->update_client_templates($clientId, $templates);
			$app->db->query('UPDATE `client` SET `template_additional` = \'\' WHERE `client_id` = ?', $clientId);
		}

		/*
         * if the master-Template is custom there is NO changing
         */
		if ($masterTemplateId > 0){
			$sql = "SELECT * FROM client_template WHERE template_id = ?";
			$limits = $app->db->queryOneRecord($sql, $masterTemplateId);
			if($is_reseller == true && $limits['limit_client'] == 0) $limits['limit_client'] = -1;
			elseif($is_reseller == false && $limits['limit_client'] != 0) $limits['limit_client'] = 0;
		} else {
			// if there is no master template it makes NO SENSE adding sub templates.
			// adding subtemplates are stored in client limits, so they would add up
			// on every save action for the client -> too high limits!
			return;
		}

		/*
         * Process the additional templates here (add them to the limits
         * if != -1)
         */
		$addTpl = explode('/', $additionalTemplateStr);
		$addTpls = $app->db->queryAllRecords('SELECT `client_template_id` FROM `client_template_assigned` WHERE `client_id` = ?', $clientId);
		foreach ($addTpls as $addTpl){
			$item = $addTpl['client_template_id'];
			$sql = "SELECT * FROM client_template WHERE template_id = ?";
			$addLimits = $app->db->queryOneRecord($sql, $item);
			$app->log('Template processing subtemplate ' . $item . ' for client ' . $clientId, LOGLEVEL_DEBUG);
			/* maybe the template is deleted in the meantime */
			if (is_array($addLimits)){
				foreach($addLimits as $k => $v){
					if($k == 'limit_client') {
						if($is_reseller == true && $v == 0) continue;
						elseif($is_reseller == false && $v != 0) continue;
					}
					
					// we need to do this, as e. g. a single server id in web_servers etc. would not be matching "is_string" below
					if(in_array($k, array('mail_servers', 'web_servers', 'dns_servers', 'db_servers'), true)) $v = strval($v);
					
					/* we can remove this condition, but it is easier to debug with it (don't add ids and other non-limit values) */
					if (strpos($k, 'limit') !== false or strpos($k, 'default') !== false or strpos($k, 'servers') !== false or $k == 'ssh_chroot' or $k == 'web_php_options' or $k == 'force_suexec'){
						$app->log('Template processing key ' . $k . ' for client ' . $clientId, LOGLEVEL_DEBUG);
						/* process the numerical limits */
						if (is_numeric($v) && in_array($k, array('mail_servers', 'web_servers', 'dns_servers', 'db_servers'), true) == false){
							/* switch for special cases */
							switch ($k){
							case 'limit_cron_frequency':
								if ($v < $limits[$k]) $limits[$k] = $v;
								/* silent adjustment of the minimum cron frequency to 1 minute */
								/* maybe this control test should be done via validator definition in tform.php file, but I don't know how */
								if ($limits[$k] < 1) $limits[$k] = 1;
								break;

							case 'default_mailserver':
							case 'default_webserver':
							case 'default_dnsserver':
							case 'default_slave_dnsserver':
							case 'default_dbserver':
								/* additional templates don't override default server from main template */
								if ($limits[$k] == 0) $limits[$k] = $v;
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
							switch ($form["tabs"]["limits"]["fields"][$k]['formtype']){
							case 'CHECKBOXARRAY':
								if (!isset($limits[$k])){
									$limits[$k] = array();
								}

								$limits_values = $limits[$k];
								if (is_string($limits[$k])){
									$limits_values = explode($form["tabs"]["limits"]["fields"][$k]["separator"], $limits[$k]);
								}
								$additional_values = explode($form["tabs"]["limits"]["fields"][$k]["separator"], $v);
								$app->log('Template processing key ' . $k . ' type CHECKBOXARRAY, lim / add: ' . implode(',', $limits_values) . ' / ' . implode(',', $additional_values) . ' for client ' . $clientId, LOGLEVEL_DEBUG);
								/* unification of limits_values (master template) and additional_values (additional template) */
								$limits_unified = array();
								foreach($form["tabs"]["limits"]["fields"][$k]["value"] as $key => $val){
									if (in_array($key, $limits_values) || in_array($key, $additional_values)) $limits_unified[] = $key;
								}
								$limits[$k] = implode($form["tabs"]["limits"]["fields"][$k]["separator"], $limits_unified);
								break;
							case 'MULTIPLE':
								if (!isset($limits[$k])){
									$limits[$k] = array();
								}
								
								$limits_values = $limits[$k];
								if (is_string($limits[$k])){
									if($limits[$k] != '') {
										$limits_values = explode($form["tabs"]["limits"]["fields"][$k]["separator"], $limits[$k]);
									} else {
										$limits_values = array();
									}
								}
								$additional_values = explode($form["tabs"]["limits"]["fields"][$k]["separator"], $v);
								$app->log('Template processing key ' . $k . ' type CHECKBOXARRAY, lim / add: ' . implode(',', $limits_values) . ' / ' . implode(',', $additional_values) . ' for client ' . $clientId, LOGLEVEL_DEBUG);
								/* unification of limits_values (master template) and additional_values (additional template) */
								if(in_array($k, array('mail_servers', 'web_servers', 'dns_servers', 'db_servers'), true)) {
									$limits_unified = array_unique(array_merge($limits_values, $additional_values));
								} else {
									$limits_unified = array();
									foreach($form["tabs"]["limits"]["fields"][$k]["value"] as $key => $val){
										if (in_array($key, $limits_values) || in_array($key, $additional_values)) $limits_unified[] = $key;
									}
								}
								$limits[$k] = implode($form["tabs"]["limits"]["fields"][$k]["separator"], $limits_unified);
								break;
							case 'CHECKBOX':
								if($k == 'force_suexec') {
									// 'n' is less limited than y
									if (!isset($limits[$k])){
										$limits[$k] = 'y';
									}
									if($limits[$k] == 'n' || $v == 'n') $limits[$k] = 'n';
								} else {
									// 'y' is less limited than n
									if (!isset($limits[$k])){
										$limits[$k] = 'n';
									}
									if($limits[$k] == 'y' || $v == 'y') $limits[$k] = 'y';
								}
								break;
							case 'SELECT':
								$limit_values = array_keys($form["tabs"]["limits"]["fields"][$k]["value"]);
								/* choose the lower index of the two SELECT items */
								$limits[$k] = $limit_values[min(array_search($limits[$k], $limit_values), array_search($v, $limit_values))];
								break;
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
		$update_values = array();
		if(!$is_reseller) unset($limits['limit_client']); // Only Resellers may have limit_client set in template to ensure that we do not convert a client to reseller accidently.
		foreach($limits as $k => $v){
			if (strpos($k, 'default') !== false and $v == 0) {
				continue; // template doesn't define default server, client's default musn't be changed
			}
			if ((strpos($k, 'limit') !== false or strpos($k, 'default') !== false or strpos($k, '_servers') !== false or $k == 'ssh_chroot' or $k == 'web_php_options' or $k == 'force_suexec') && !is_array($v)){
				if ($update != '') $update .= ', ';
				$update .= '?? = ?';
				$update_values[] = $k;
				$update_values[] = $v;
			}
		}
		$update_values[] = $clientId;
		$app->log('Template processed for client ' . $clientId . ', update string: ' . $update, LOGLEVEL_DEBUG);
		if($update != '') {
			$sql = 'UPDATE client SET ' . $update . " WHERE client_id = ?";
			$app->db->query($sql, true, $update_values);
		}
		unset($form);
	}

}
