-- database patch for displaying error logs for the sites.
ALTER TABLE `web_domain` ADD `logs` MEDIUMTEXT NOT NULL ;