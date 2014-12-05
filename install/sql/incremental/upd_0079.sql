ALTER TABLE `directive_snippets` ADD `customer_viewable` ENUM('n','y') NOT NULL DEFAULT 'n' AFTER `snippet`;
ALTER TABLE `web_domain` ADD `directive_snippets_id` int(11) unsigned NOT NULL default '0';