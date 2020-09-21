ALTER TABLE `web_domain` ADD  `jailkit_chroot_app_sections` mediumtext NULL DEFAULT NULL;
ALTER TABLE `web_domain` ADD  `jailkit_chroot_app_programs` mediumtext NULL DEFAULT NULL;
ALTER TABLE `web_domain` ADD  `delete_unused_jailkit` enum('n','y') NOT NULL DEFAULT 'n';
ALTER TABLE `web_domain` ADD  `last_jailkit_update` date NULL DEFAULT NULL;
ALTER TABLE `web_domain` ADD  `last_jailkit_hash` varchar(255) DEFAULT NULL;