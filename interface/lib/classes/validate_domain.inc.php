<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

class validate_domain {
	
    function get_error($errmsg) {
        global $app;
        
        if(isset($app->tform->wordbook[$errmsg])) {
            return $app->tform->wordbook[$errmsg]."<br>\r\n";
        } else {
            return $errmsg."<br>\r\n";
        }
    }
    
    /* Validator function for domain (website) */
    function web_domain($field_name, $field_value, $validator) {
        if(empty($field_value)) return $this->get_error('domain_error_empty');
        
        // do not allow wildcards on website domains
        $result = $this->_regex_validate($field_value);
        if(!$result) return $this->get_error('domain_error_regex');
        
        $result = $this->_check_unique($field_value);
        if(!$result) return $this->get_error('domain_error_unique');
    }
    
    /* Validator function for sub domain */
    function sub_domain($field_name, $field_value, $validator) {
        if(empty($field_value)) return $this->get_error('domain_error_empty');
        
        $allow_wildcard = $this->_wildcard_limit();
        if($allow_wildcard == false && substr($field_value, 0, 2) === '*.') return $this->get_error('domain_error_wildcard');
        
        $result = $this->_regex_validate($field_value, $allow_wildcard);
        if(!$result) return $this->get_error('domain_error_regex');
        
        $result = $this->_check_unique($field_value);
        if(!$result) return $this->get_error('domain_error_unique');
    }
    
    /* Validator function for alias domain */
    function alias_domain($field_name, $field_value, $validator) {
        if(empty($field_value)) return $this->get_error('domain_error_empty');
        
        // do not allow wildcards on alias domains
        $result = $this->_regex_validate($field_value);
        if(!$result) return $this->get_error('domain_error_regex');
        
        $result = $this->_check_unique($field_value);
        if(!$result) return $this->get_error('domain_error_unique');
    }
    
    /* Validator function for checking the auto subdomain of a web/aliasdomain */
    function web_domain_autosub($field_name, $field_value, $validator) {
        global $app;
        if(empty($field_value) || $field_name != 'subdomain') return; // none set
        
        $check_domain = $_POST['domain'];
        $app->uses('ini_parser,getconf');
        $settings = $app->getconf->get_global_config('domains');
        if ($settings['use_domain_module'] == 'y') {
            $sql = "SELECT domain_id, domain FROM domain WHERE domain_id = " . $app->functions->intval($check_domain);
            $domain_check = $app->db->queryOneRecord($sql);
            if(!$domain_check) return;
            $check_domain = $domain_check['domain'];
        }
        
        $result = $this->_check_unique($field_value . '.' . $check_domain, true);
        if(!$result) return $this->get_error('domain_error_autosub');
    }
    
    /* internal validator function to match regexp */
    function _regex_validate($domain_name, $allow_wildcard = false) {
        $pattern = '/^' . ($allow_wildcard == true ? '(\*\.)?' : '') . '[\w\.\-]{2,255}\.[a-zA-Z0-9\-]{2,30}$/';
        return preg_match($pattern, $domain_name);
    }
    
    /* check if the domain hostname is unique (keep in mind the auto subdomains!) */
    function _check_unique($domain_name, $only_domain = false) {
        global $app, $page;
        
        if(isset($app->remoting_lib->primary_id)) {
            $primary_id = $app->remoting_lib->primary_id;
			$domain = $app->remoting_lib->dataRecord;
        } else {
            $primary_id = $app->tform->primary_id;
			$domain = $page->dataRecord;
        }

		if($domain['ip_address'] == '' || $domain['ipv6_address'] == ''){
			if($domain['parent_domain_id'] > 0){
				$parent_domain = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".$domain['parent_domain_id']);
			}
		}
		
		// check if domain has alias/subdomains - if we move a web to another IP, make sure alias/subdomains are checked as well
		$aliassubdomains = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE parent_domain_id = ".$app->functions->intval($primary_id)." AND (type = 'alias' OR type = 'subdomain')");
		$additional_sql1 = '';
		$additional_sql2 = '';
		if(is_array($aliassubdomains) && !empty($aliassubdomains)){
			foreach($aliassubdomains as $aliassubdomain){
				$additional_sql1 .= " OR `domain` = '".$app->db->quote($aliassubdomain['domain'])."'";
				$additional_sql2 .= " OR CONCAT(`subdomain`, '.', `domain`) = '".$app->db->quote($aliassubdomain['domain'])."'";
			}
		}
		
        //$check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_domain` WHERE `domain` = '" . $app->db->quote($domain_name) . "' AND `domain_id` != " . $app->functions->intval($primary_id));
		//if($check['cnt'] > 0) return false;
		
		// we can have the same domain on different servers or different IPs, so we have to check for identical domains on the same IP (or wildcard IPs)
		$checks = $app->db->queryAllRecords("SELECT * FROM `web_domain` WHERE (`domain` = '" . $app->db->quote($domain_name) . "'".$additional_sql1.") AND `server_id` = ".intval($domain['server_id'])." AND `domain_id` != " . $app->functions->intval($primary_id).($additional_sql1 != '' ? " AND `parent_domain_id` != ".$app->functions->intval($primary_id) : ""));

		if(is_array($checks) && !empty($checks)){
			foreach($checks as $check){
				if($domain['ip_address'] == '*') return false;
				if($check['ip_address'] == '*') return false;
				if($domain['ip_address'] != '' && $check['ip_address'] == $domain['ip_address']) return false;
				if($domain['ipv6_address'] != '' && $check['ipv6_address'] == $domain['ipv6_address']) return false;
				// if alias/subdomain: check IP addresses of parent domain
				if($check['ip_address'] == '' || $check['ipv6_address'] == ''){
					if($check['parent_domain_id'] > 0){
						$check_parent_domain = $app->db->queryOneRecord("SELECT * FROM `web_domain` WHERE `domain_id` = ".$check['parent_domain_id']);
					}
				}
					
				if($domain['ip_address'] == '' && $check['ip_address'] != ''){
					if(is_array($parent_domain) && !empty($parent_domain)){
						if($parent_domain['ip_address'] == '*') return false;
						if($parent_domain['ip_address'] != '' && $check['ip_address'] == $parent_domain['ip_address']) return false;
					}
				}
				
				if($domain['ip_address'] == '' && $check['ip_address'] == ''){
					if($check['parent_domain_id'] > 0){
						if(is_array($check_parent_domain) && !empty($check_parent_domain)){
							if($check_parent_domain['ip_address'] == '*') return false;
						}
					}
					if(is_array($parent_domain) && !empty($parent_domain)){
						if($parent_domain['ip_address'] == '*') return false;
						if($parent_domain['ip_address'] != '' && $check_parent_domain['ip_address'] == $parent_domain['ip_address']) return false;
					}
				}
				
				if($check['ip_address'] == '' && $domain['ip_address'] != ''){
					if($check['parent_domain_id'] > 0){
						if(is_array($check_parent_domain) && !empty($check_parent_domain)){
							if($check_parent_domain['ip_address'] == '*') return false;
							if($check_parent_domain['ip_address'] != '' && $check_parent_domain['ip_address'] == $domain['ip_address']) return false;
						}
					}
				}
				
				if($domain['ipv6_address'] == '' && $check['ipv6_address'] != ''){
					if(is_array($parent_domain) && !empty($parent_domain)){
						if($parent_domain['ipv6_address'] != '' && $check['ipv6_address'] == $parent_domain['ipv6_address']) return false;
					}
				}
				
				if($domain['ipv6_address'] == '' && $check['ipv6_address'] == ''){
					if(is_array($parent_domain) && !empty($parent_domain)){
						if($parent_domain['ipv6_address'] != '' && $check_parent_domain['ipv6_address'] == $parent_domain['ipv6_address']) return false;
					}
				}
				
				if($check['ipv6_address'] == '' && $domain['ipv6_address'] != ''){
					if($check['parent_domain_id'] > 0){
						if(is_array($check_parent_domain) && !empty($check_parent_domain)){
							if($check_parent_domain['ipv6_address'] != '' && $check_parent_domain['ipv6_address'] == $domain['ipv6_address']) return false;
						}
					}
				}
			}
		}
        
        
        if($only_domain == false) {
            //$check = $app->db->queryOneRecord("SELECT COUNT(*) as `cnt` FROM `web_domain` WHERE CONCAT(`subdomain`, '.', `domain`) = '" . $app->db->quote($domain_name) . "' AND `domain_id` != " . $app->functions->intval($primary_id));
			//if($check['cnt'] > 0) return false;
			// we can have the same domain on different servers or different IPs, so we have to check for identical domains on the same IP (or wildcard IPs)
			$checks = $app->db->queryAllRecords("SELECT * FROM `web_domain` WHERE (CONCAT(`subdomain`, '.', `domain`) = '" . $app->db->quote($domain_name) . "'".$additional_sql2.") AND `server_id` = ".intval($domain['server_id'])." AND `domain_id` != " . $app->functions->intval($primary_id).($additional_sql2 != '' ? " AND `parent_domain_id` != ".$app->functions->intval($primary_id) : ""));
			if(is_array($checks) && !empty($checks)){
				foreach($checks as $check){
					if($domain['ip_address'] == '*') return false;
					if($check['ip_address'] == '*') return false;
					if($domain['ip_address'] != '' && $check['ip_address'] == $domain['ip_address']) return false;
					if($domain['ipv6_address'] != '' && $check['ipv6_address'] == $domain['ipv6_address']) return false;
					// if alias/subdomain: check IP addresses of parent domain
					if($check['ip_address'] == '' || $check['ipv6_address'] == ''){
						if($check['parent_domain_id'] > 0){
							$check_parent_domain = $app->db->queryOneRecord("SELECT * FROM `web_domain` WHERE `domain_id` = ".$check['parent_domain_id']);
						}
					}
					
					if($domain['ip_address'] == '' && $check['ip_address'] != ''){
						if(is_array($parent_domain) && !empty($parent_domain)){
							if($parent_domain['ip_address'] == '*') return false;
							if($parent_domain['ip_address'] != '' && $check['ip_address'] == $parent_domain['ip_address']) return false;
						}
					}
				
					if($domain['ip_address'] == '' && $check['ip_address'] == ''){
						if($check['parent_domain_id'] > 0){
							if(is_array($check_parent_domain) && !empty($check_parent_domain)){
								if($check_parent_domain['ip_address'] == '*') return false;
							}
						}
						if(is_array($parent_domain) && !empty($parent_domain)){
							if($parent_domain['ip_address'] == '*') return false;
							if($parent_domain['ip_address'] != '' && $check_parent_domain['ip_address'] == $parent_domain['ip_address']) return false;
						}
					}
				
					if($check['ip_address'] == '' && $domain['ip_address'] != ''){
						if($check['parent_domain_id'] > 0){
							if(is_array($check_parent_domain) && !empty($check_parent_domain)){
								if($check_parent_domain['ip_address'] == '*') return false;
								if($check_parent_domain['ip_address'] != '' && $check_parent_domain['ip_address'] == $domain['ip_address']) return false;
							}
						}
					}
				
					if($domain['ipv6_address'] == '' && $check['ipv6_address'] != ''){
						if(is_array($parent_domain) && !empty($parent_domain)){
							if($parent_domain['ipv6_address'] != '' && $check['ipv6_address'] == $parent_domain['ipv6_address']) return false;
						}
					}
				
					if($domain['ipv6_address'] == '' && $check['ipv6_address'] == ''){
						if(is_array($parent_domain) && !empty($parent_domain)){
							if($parent_domain['ipv6_address'] != '' && $check_parent_domain['ipv6_address'] == $parent_domain['ipv6_address']) return false;
						}
					}
				
					if($check['ipv6_address'] == '' && $domain['ipv6_address'] != ''){
						if($check['parent_domain_id'] > 0){
							if(is_array($check_parent_domain) && !empty($check_parent_domain)){
								if($check_parent_domain['ipv6_address'] != '' && $check_parent_domain['ipv6_address'] == $domain['ipv6_address']) return false;
							}
						}
					}
				}
			}
            
        }
        
        return true;
    }
    
    /* check if the client may add wildcard domains */
    function _wildcard_limit() {
        global $app;
        
        if($_SESSION["s"]["user"]["typ"] != 'admin') {
            // Get the limits of the client
            $client_group_id = $_SESSION["s"]["user"]["default_group"];
            $client = $app->db->queryOneRecord("SELECT limit_wildcard FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
        
            if($client["limit_wildcard"] == 'y') return true;
            else return false;
        }
        return true; // admin may always add wildcard domain
    }
}