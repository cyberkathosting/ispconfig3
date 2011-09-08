ALTER TABLE  `cron` CHANGE  `command`  `command` TEXT NOT NULL;
ALTER TABLE  `client` ADD  `limit_openvz_vm` int(11) NOT NULL DEFAULT '0' AFTER  `limit_mailmailinglist` ,
ADD  `limit_openvz_vm_template_id` int(11) NOT NULL DEFAULT '0' AFTER  `limit_openvz_vm`;