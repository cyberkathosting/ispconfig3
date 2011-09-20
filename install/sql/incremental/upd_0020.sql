ALTER TABLE `web_domain` ADD `seo_redirect` VARCHAR( 255 ) default NULL AFTER `redirect_path`;
ALTER TABLE `web_folder_user` ADD `server_id` INT( 11 ) NOT NULL DEFAULT '0' AFTER `sys_perm_other`;