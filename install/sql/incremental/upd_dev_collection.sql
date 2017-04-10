ALTER TABLE `web_domain` CHANGE `folder_directive_snippets` `folder_directive_snippets` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `web_domain` ADD `log_retention` INT NOT NULL DEFAULT '30' AFTER `https_port`;
