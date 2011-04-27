-- database patch for separate login for mailbox users
ALTER TABLE `mail_user` ADD `login` VARCHAR( 255 ) NOT NULL ;
UPDATE `mail_user` SET `login` = `email` WHERE 1 ;