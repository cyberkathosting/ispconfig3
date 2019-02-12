ALTER TABLE `web_domain` ADD COLUMN `php_fpm_chroot` enum('n','y') NOT NULL DEFAULT 'n' AFTER `php_fpm_use_socket`;
