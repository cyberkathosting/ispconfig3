<?php
/**
 * sites_web_domain_plugin plugin
 * 
 * @author Marius Cramer <m.cramer@pixcept.de> pixcept KG 2012, copied and adapted from web_domain plugin by:
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
 */
 
class sites_web_vhost_subdomain_plugin {

	var $plugin_name        = 'sites_web_vhost_subdomain_plugin';
	var $class_name         = 'sites_web_vhost_subdomain_plugin';
	
	// TODO: This function is a duplicate from the one in interface/web/sites/web_vhost_subdomain_edit.php
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
        // both event call the same function as the things to do do not differ here
        $app->plugin->registerEvent('sites:web_vhost_subdomain:on_after_insert','sites_web_vhost_subdomain_plugin','sites_web_vhost_subdomain_edit');
        $app->plugin->registerEvent('sites:web_vhost_subdomain:on_after_update','sites_web_vhost_subdomain_plugin','sites_web_vhost_subdomain_edit');
    }

    /*
		Function to create the sites_web_vhost_subdomain rule and insert it into the custom rules           
    */
    function sites_web_vhost_subdomain_edit($event_name, $page_form) {
        global $app, $conf;   
        
		// Get configuration for the web system
        $app->uses("getconf");        
		$web_rec = $app->tform->getDataRecord($page_form->id);
        $web_config = $app->getconf->get_server_config(intval($web_rec['server_id']),'web');            
        
        $parent_domain = $app->db->queryOneRecord("SELECT * FROM `web_domain` WHERE `domain_id` = '" . intval($web_rec['parent_domain_id']) . "'");
        
		// Set the values for document_root, system_user and system_group
		$system_user = $app->db->quote($parent_domain['system_user']);
		$system_group = $app->db->quote($parent_domain['system_group']);
		$document_root = $app->db->quote($parent_domain['document_root']);
		$php_open_basedir = str_replace("[website_path]/web",$document_root.'/'.$web_rec['web_folder'],$web_config["php_open_basedir"]);
		$php_open_basedir = str_replace("[website_domain]/web",$web_rec['domain'].'/'.$web_rec['web_folder'],$php_open_basedir);
		$php_open_basedir = str_replace("[website_path]",$document_root,$php_open_basedir);
		$php_open_basedir = $app->db->quote(str_replace("[website_domain]",$web_rec['domain'],$php_open_basedir));
		$htaccess_allow_override = $app->db->quote($parent_domain['allow_override']);

		$sql = "UPDATE web_domain SET sys_groupid = ".intval($parent_domain['sys_groupid']).",system_user = '$system_user', system_group = '$system_group', document_root = '$document_root', allow_override = '$htaccess_allow_override', php_open_basedir = '$php_open_basedir'  WHERE domain_id = ".$page_form->id;
		$app->db->query($sql);
	}
}              	