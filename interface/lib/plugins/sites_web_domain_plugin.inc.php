<?php
/**
 * sites_web_domain_plugin plugin
 * 
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
 */
 
class sites_web_domain_plugin {

	var $plugin_name        = 'sites_web_domain_plugin';
	var $class_name         = 'sites_web_domain_plugin';
	
	// TODO: This function is a duplicate from the one in interface/web/sites/web_domain_edit.php
	//       There should be a single "token replacement" function to be called from modules and
	//	 from the main code.
	// Returna a "3/2/1" path hash from a numeric id '123'
	function id_hash($id,$levels) {
		$hash = "" . $id % 10 ;
		$id /= 10 ;
		$levels -- ;
		while ( $levels > 0 ) {
			$hash .= "/" . $id % 10 ;
			$id /= 10 ;
			$levels-- ;
		}
		return $hash;
	}

    /*
            This function is called when the plugin is loaded
    */
    function onLoad() {
        global $app;
        //Register for the events        
        $app->plugin->registerEvent('sites:web_domain:on_after_insert','sites_web_domain_plugin','sites_web_domain_edit');
    }

    /*
		Function to create the sites_web_domain rule and insert it into the custom rules           
    */
    function sites_web_domain_edit($event_name, $page_form) {
        global $app, $conf;   
        // make sure that the record belongs to the clinet group and not the admin group when a dmin inserts it
        // also make sure that the user can not delete domain created by a admin
        if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($page_form->dataRecord["client_group_id"])) {
            $client_group_id = intval($page_form->dataRecord["client_group_id"]);
            $app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE domain_id = ".$page_form->id);
        }
        if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($page_form->dataRecord["client_group_id"])) {
            $client_group_id = intval($page_form->dataRecord["client_group_id"]);
            $app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE domain_id = ".$page_form->id);
        }
        // Get configuration for the web system
        $app->uses("getconf");        
        $web_config = $app->getconf->get_server_config(intval($page_form->dataRecord['server_id']),'web');            
        $document_root = str_replace("[website_id]",$page_form->id,$web_config["website_path"]);
		$document_root = str_replace("[website_idhash_1]",$this->id_hash($page_form->id,1),$document_root);
		$document_root = str_replace("[website_idhash_2]",$this->id_hash($page_form->id,1),$document_root);
		$document_root = str_replace("[website_idhash_3]",$this->id_hash($page_form->id,1),$document_root);
		$document_root = str_replace("[website_idhash_4]",$this->id_hash($page_form->id,1),$document_root);
		
        // get the ID of the client
        if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {                    
            $client_group_id = $_SESSION["s"]["user"]["default_group"];
            $client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = $client_group_id");
            $client_id = intval($client["client_id"]);
        } else {                
            //$client_id = intval($this->dataRecord["client_group_id"]);
            $client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ".intval($page_form->dataRecord["client_group_id"]));
            $client_id = intval($client["client_id"]);
        }

        // Set the values for document_root, system_user and system_group
        $system_user 				= $app->db->quote('web'.$page_form->id);
        $system_group 				= $app->db->quote('client'.$client_id);
		
		$document_root 				= str_replace("[client_id]",$client_id,$document_root);
		$document_root				= str_replace("[client_idhash_1]",$this->id_hash($client_id,1),$document_root);
		$document_root				= str_replace("[client_idhash_2]",$this->id_hash($client_id,2),$document_root);
		$document_root				= str_replace("[client_idhash_3]",$this->id_hash($client_id,3),$document_root);
		$document_root				= str_replace("[client_idhash_4]",$this->id_hash($client_id,4),$document_root);
		$document_root 				= $app->db->quote($document_root);
        
		$php_open_basedir 			= str_replace("[website_path]",$document_root,$web_config["php_open_basedir"]);
        $php_open_basedir 			= $app->db->quote(str_replace("[website_domain]",$page_form->dataRecord['domain'],$php_open_basedir));
		
		$htaccess_allow_override 	= $app->db->quote($web_config["htaccess_allow_override"]);
        
		$sql = "UPDATE web_domain SET system_user = '$system_user', system_group = '$system_group', document_root = '$document_root', allow_override = '$htaccess_allow_override', php_open_basedir = '$php_open_basedir'  WHERE domain_id = ".$page_form->id;
		$app->db->query($sql);
	}
}              	