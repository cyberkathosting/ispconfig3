ALTER TABLE `web_domain` ADD `pm_max_children` INT NOT NULL DEFAULT '50' AFTER `php_fpm_use_socket` ,
ADD `pm_start_servers` INT NOT NULL DEFAULT '20' AFTER `pm_max_children` ,
ADD `pm_min_spare_servers` INT NOT NULL DEFAULT '5' AFTER `pm_start_servers` ,
ADD `pm_max_spare_servers` INT NOT NULL DEFAULT '35' AFTER `pm_min_spare_servers`;