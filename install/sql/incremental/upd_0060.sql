ALTER TABLE  `client` ADD  `customer_no_template` VARCHAR( 255 ) NULL DEFAULT  'C[CUSTOMER_NO]' AFTER  `ssh_rsa` ,
ADD  `customer_no_start` INT NOT NULL DEFAULT  '1' AFTER  `customer_no_template` ,
ADD  `customer_no_counter` INT NOT NULL DEFAULT  '0' AFTER  `customer_no_start` ,
ADD  `added_date` DATE NOT NULL default '0000-00-00' AFTER  `customer_no_counter` ,
ADD  `added_by` VARCHAR( 255 ) NULL AFTER  `added_date` ;
ALTER TABLE  `web_domain` ADD  `added_date` DATE NOT NULL default '0000-00-00' AFTER  `rewrite_rules` ,
ADD  `added_by` VARCHAR( 255 ) NULL AFTER  `added_date` ;
ALTER TABLE `sys_session` ADD `permanent` ENUM('n','y') NOT NULL DEFAULT 'n' AFTER `last_updated`;