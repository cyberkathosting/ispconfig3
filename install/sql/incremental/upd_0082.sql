ALTER TABLE `mail_user` ADD COLUMN `sender_cc` varchar(255) NOT NULL default '' AFTER `cc`;
