ALTER TABLE `client` ADD `paypal_email` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `bank_account_swift` ;
ALTER TABLE `web_domain` ADD `proxy_directives` MEDIUMTEXT NULL DEFAULT NULL ;


