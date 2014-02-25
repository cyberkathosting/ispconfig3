ALTER TABLE `mail_user` ADD `disablesieve-filter` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'n' AFTER `disablesieve`;
