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

$tform_def_file = "form/xmpp_domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mail');

// Loading classes
$app->uses('tpl,tform,tform_actions,tools_sites');
$app->load('tform_actions');

class page_action extends tform_actions {
    var $_xmpp_type = 'domain';

    function onLoad() {
        $show_type = 'server';
        if(isset($_GET['type']) && $_GET['type'] == 'modules') {
            $show_type = 'modules';
        } elseif(isset($_GET['type']) && $_GET['type'] == 'muc') {
            $show_type = 'muc';
        }

        $_SESSION['s']['var']['xmpp_type'] = $show_type;
        $this->_xmpp_type = $show_type;

        parent::onLoad();
    }

	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_xmppdomain')) {
				$app->error($app->tform->wordbook["limit_xmppdomain_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_xmppdomain')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_xmppdomain_txt"]);
			}
		} else {
			$settings = $app->getconf->get_global_config('xmpp');
			$app->tform->formDef['tabs']['domain']['fields']['server_id']['default'] = intval($settings['default_xmppserver']);
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		$app->uses('ini_parser,getconf');
		$settings = $app->getconf->get_global_config('domains');

		if($_SESSION["s"]["user"]["typ"] == 'admin' && $settings['use_domain_module'] != 'y') {
			// Getting Clients of the user
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY client.company_name, client.contact_name, sys_group.name";

			$clients = $app->db->queryAllRecords($sql);
			$client_select = '';
			if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id", $client_select);

		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, client.default_xmppserver, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id order by client.contact_name");

			// Set the xmppserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_xmppserver]");
			$app->tpl->setVar("server_id", "<option value='$client[default_xmppserver]'>$tmp[server_name]</option>");
			unset($tmp);

			if ($settings['use_domain_module'] != 'y') {
				// Fill the client select field
				$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$app->functions->intval($client['client_id'])." ORDER BY client.company_name, client.contact_name, sys_group.name";
				$clients = $app->db->queryAllRecords($sql);
				$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$app->functions->intval($client['client_id']));
				$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contactname'].'</option>';
				//$tmp_data_record = $app->tform->getDataRecord($this->id);
				if(is_array($clients)) {
					foreach( $clients as $client) {
						$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
						$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
					}
				}
				$app->tpl->setVar("client_group_id", $client_select);
			}
		}

		if($_SESSION["s"]["user"]["typ"] != 'admin')
		{
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client_xmpp = $app->db->queryOneRecord("SELECT xmpp_servers FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			$client_xmpp['xmpp_servers_ids'] = explode(',', $client_xmpp['xmpp_servers']);

			$only_one_server = count($client_xmpp['xmpp_servers_ids']) === 1;
			$app->tpl->setVar('only_one_server', $only_one_server);

			if ($only_one_server) {
				$app->tpl->setVar('server_id_value', $client_xmpp['xmpp_servers_ids'][0]);
			}

			$sql = "SELECT server_id, server_name FROM server WHERE server_id IN (" . $client_xmpp['xmpp_servers'] . ");";
			$xmpp_servers = $app->db->queryAllRecords($sql);

			$options_xmpp_servers = "";

			foreach ($xmpp_servers as $xmpp_server) {
				$options_xmpp_servers .= "<option value='$xmpp_server[server_id]'>$xmpp_server[server_name]</option>";
			}

			$app->tpl->setVar("client_server_id", $options_xmpp_servers);
			unset($options_xmpp_servers);

		}

		/*
		 * Now we have to check, if we should use the domain-module to select the domain
		 * or not
		 */
		if ($settings['use_domain_module'] == 'y') {
			/*
			 * The domain-module is in use.
			*/
			$domains = $app->tools_sites->getDomainModuleDomains("xmpp_domain", $this->dataRecord["domain"]);
			$domain_select = '';
			if(is_array($domains) && sizeof($domains) > 0) {
				/* We have domains in the list, so create the drop-down-list */
				foreach( $domains as $domain) {
					$domain_select .= "<option value=" . $domain['domain_id'] ;
					if ($domain['domain'] == $this->dataRecord["domain"]) {
						$domain_select .= " selected";
					}
					$domain_select .= ">" . $app->functions->idn_decode($domain['domain']) . "</option>\r\n";
				}
			}
			else {
				/*
				 * We have no domains in the domain-list. This means, we can not add ANY new domain.
				 * To avoid, that the variable "domain_option" is empty and so the user can
				 * free enter a domain, we have to create a empty option!
				*/
				$domain_select .= "<option value=''></option>\r\n";
			}
			$app->tpl->setVar("domain_option", $domain_select);
			$app->tpl->setVar("domain_module", 1);
		} else {
			$app->tpl->setVar("domain_module", 0);
		}


		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}


		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		/* check if the domain module is used - and check if the selected domain can be used! */
		$app->uses('ini_parser,getconf');
		$settings = $app->getconf->get_global_config('domains');
		if ($settings['use_domain_module'] == 'y') {
			if ($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
				$this->dataRecord['client_group_id'] = $app->tools_sites->getClientIdForDomain($this->dataRecord['domain']);
			}
			$domain_check = $app->tools_sites->checkDomainModuleDomain($this->dataRecord['domain']);
			if(!$domain_check) {
				// invalid domain selected
				$app->tform->errorMessage .= $app->tform->lng("domain_error_empty")."<br />";
			} else {
				$this->dataRecord['domain'] = $domain_check;
			}
		}

		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_xmppdomain, default_xmppserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM xmpp_domain WHERE domain_id = ".$app->functions->intval($this->id));
				$this->dataRecord["server_id"] = $tmp["server_id"];
				unset($tmp);
				// When the record is inserted
			} else {
				$client['xmpp_servers_ids'] = explode(',', $client['xmpp_servers']);

				// Check if chosen server is in authorized servers for this client
				if (!(is_array($client['xmpp_servers_ids']) && in_array($this->dataRecord["server_id"], $client['xmpp_servers_ids']))) {
					$app->error($app->tform->wordbook['error_not_allowed_server_id']);
				}

				if($client["limit_xmppdomain"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM xmpp_domain WHERE sys_groupid = $client_group_id");
					if($tmp["number"] >= $client["limit_xmppdomain"]) {
						$app->error($app->tform->wordbook["limit_xmppdomain_txt"]);
					}
				}
			}

			// Clients may not set the client_group_id, so we unset them if user is not a admin
			if(!$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);
		}

		//* make sure that the xmpp domain is lowercase
		if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);

        // Read auth method
        if(isset($this->dataRecord["auth_method"]))
            switch($this->dataRecord["auth_method"]){
                case 0:
                    $this->dataRecord["auth_method"] = 'plain';
                    break;
                case 1:
                    $this->dataRecord["auth_method"] = 'hashed';
                    break;
                case 2:
                    $this->dataRecord["auth_method"] = 'isp';
                    break;
            }
        // vjud opt mode
        if(isset($this->dataRecord["vjud_opt_mode"]))
            $this->dataRecord["vjud_opt_mode"] = $this->dataRecord["vjud_opt_mode"] == 0 ? 'in' : 'out';
        if(isset($this->dataRecord["muc_restrict_room_creation"])){
            switch($this->dataRecord["muc_restrict_room_creation"]){
                case 0:
                    $this->dataRecord["muc_restrict_room_creation"] = 'false';
                    break;
                case 1:
                    $this->dataRecord["muc_restrict_room_creation"] = 'member';
                    break;
                case 2:
                    $this->dataRecord["muc_restrict_room_creation"] = 'true';
                    break;
            }
        }

		parent::onSubmit();
	}

	function onAfterInsert() {
        global $app, $conf;

        // make sure that the record belongs to the client group and not the admin group when admin inserts it
        // also make sure that the user can not delete domain created by a admin
        if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
            $client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
            $app->db->query("UPDATE xmpp_domain SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE domain_id = ".$this->id);
        }
        if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
            $client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
            $app->db->query("UPDATE xmpp_domain SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE domain_id = ".$this->id);
        }

        //* make sure that the xmpp domain is lowercase
        if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);

        // Insert DNS Records
        $soa = $app->db->queryOneRecord("SELECT id AS zone, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, server_id, ttl, serial FROM dns_soa WHERE active = 'Y' AND origin = ?", $this->dataRecord['domain'].'.');
        if ( isset($soa) && !empty($soa) ) $this->update_dns($this->dataRecord, $soa);
	}

	function onBeforeUpdate() {
        global $app, $conf;

        if($this->_xmpp_type == 'server') {
            //* Check if the server has been changed
            // We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
            if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
                if (isset($this->dataRecord["server_id"])) {
                    $rec = $app->db->queryOneRecord("SELECT server_id from xmpp_domain WHERE domain_id = ".$this->id);
                    if($rec['server_id'] != $this->dataRecord["server_id"]) {
                        //* Add a error message and switch back to old server
                        $app->tform->errorMessage .= $app->lng('The Server can not be changed.');
                        $this->dataRecord["server_id"] = $rec['server_id'];
                    }
                    unset($rec);
                }
                //* If the user is neither admin nor reseller
            } else {
                //* We do not allow users to change a domain which has been created by the admin
                $rec = $app->db->queryOneRecord("SELECT sys_perm_group, domainfrom xmpp_domain WHERE domain_id = ".$this->id);
                if(isset($this->dataRecord["domain"]) && $rec['domain'] != $this->dataRecord["domain"] && $app->tform->checkPerm($this->id, 'u')) {
                    //* Add a error message and switch back to old server
                    $app->tform->errorMessage .= $app->lng('The Domain can not be changed. Please ask your Administrator if you want to change the domain name.');
                    $this->dataRecord["domain"] = $rec['domain'];
                }
                unset($rec);
            }
        }

        //* make sure that the xmpp domain is lowercase
        if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);

	}

	function onAfterUpdate() {
		global $app, $conf;

        // Update DNS Records
        // TODO: Update gets only triggered from main form. WHY?
        // TODO: if(in_array($this->_xmpp_type, array('muc', 'modules'))){
            $soa = $app->db->queryOneRecord("SELECT id AS zone, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, server_id, ttl, serial FROM dns_soa WHERE active = 'Y' AND origin = ?", $this->dataRecord['domain'].'.');
            if ( isset($soa) && !empty($soa) ) $this->update_dns($this->dataRecord, $soa);
        //}
	}



    private function update_dns($dataRecord, $new_rr) {
        global $app, $conf;

        $rec = $app->db->queryOneRecord("SELECT use_pubsub, use_proxy, use_anon_host, use_vjud, use_muc_host from xmpp_domain WHERE domain_id = ".$this->id);
        $required_hosts = array('xmpp');
        if($rec['use_pubsub']=='y')
            $required_hosts[] = 'pubsub';
        if($rec['use_proxy']=='y')
            $required_hosts[] = 'proxy';
        if($rec['use_anon_host']=='y')
            $required_hosts[] = 'anon';
        if($rec['use_vjud']=='y')
            $required_hosts[] = 'vjud';
        if($rec['use_muc_host']=='y')
            $required_hosts[] = 'muc';

        // purge old rr-record
        $sql = "SELECT * FROM dns_rr WHERE zone = ? AND (name IN ? AND type = 'CNAME' OR name LIKE ? AND type = 'SRV')  AND ? ORDER BY serial DESC";
        $rec = $app->db->queryAllRecords($sql, $new_rr['zone'], array('xmpp', 'pubsub', 'proxy', 'anon', 'vjud', 'muc'), '_xmpp-%', $app->tform->getAuthSQL('r'));
        if (is_array($rec[1])) {
            for ($i=0; $i < count($rec); ++$i)
                $app->db->datalogDelete('dns_rr', 'id', $rec[$i]['id']);
        }

        // create new cname rr-records
        foreach($required_hosts AS $h){
            $rr = $new_rr;
            $rr['name'] = $h;
            $rr['type'] = 'CNAME';
            $rr['data'] = 'jalapeno.spicyweb.de.';
            $rr['aux'] = 0;
            $rr['active'] = 'Y';
            $rr['stamp'] = date('Y-m-d H:i:s');
            $rr['serial'] = $app->validate_dns->increase_serial($new_rr['serial']);
            $app->db->datalogInsert('dns_rr', $rr, 'id', $rr['zone']);
        }

        //create new srv rr-records
        $rr = $new_rr;
        $rr['name'] = '_xmpp-client._tcp.'.$dataRecord['domain'].'.';
        $rr['type'] = 'SRV';
        $rr['data'] = '5 5222 jalapeno.spicyweb.de.';
        $rr['aux'] = 0;
        $rr['active'] = 'Y';
        $rr['stamp'] = date('Y-m-d H:i:s');
        $rr['serial'] = $app->validate_dns->increase_serial($new_rr['serial']);
        $app->db->datalogInsert('dns_rr', $rr, 'id', $rr['zone']);
        $rr = $new_rr;
        $rr['name'] = '_xmpp-server._tcp.'.$dataRecord['domain'].'.';
        $rr['type'] = 'SRV';
        $rr['data'] = '5 5269 jalapeno.spicyweb.de.';
        $rr['aux'] = 0;
        $rr['active'] = 'Y';
        $rr['stamp'] = date('Y-m-d H:i:s');
        $rr['serial'] = $app->validate_dns->increase_serial($new_rr['serial']);
        $app->db->datalogInsert('dns_rr', $rr, 'id', $rr['zone']);

        // Refresh zone
        $zone = $app->db->queryOneRecord("SELECT id, serial FROM dns_soa WHERE active = 'Y' AND id = ?", $new_rr['zone']);
        $new_serial = $app->validate_dns->increase_serial($zone['serial']);
        $app->db->datalogUpdate('dns_soa', "serial = '".$new_serial."'", 'id', $zone['id']);
    }


}

$page = new page_action;
$page->onLoad();

?>
