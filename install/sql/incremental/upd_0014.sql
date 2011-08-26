-- database patch for getmail read_all option
ALTER TABLE `mail_get` ADD ( `source_read_all` varchar(255) NOT NULL default 'y');