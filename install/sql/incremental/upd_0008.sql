-- database patch for rsa-key based shell access.
ALTER TABLE `sys_user` ADD `id_rsa` VARCHAR( 2000 ) NOT NULL ;
ALTER TABLE `sys_user` ADD `ssh_rsa` VARCHAR( 600 ) NOT NULL ;
ALTER TABLE `shell_user` ADD `ssh_rsa` VARCHAR( 600 ) NOT NULL ;
