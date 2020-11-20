-- drop old php column because new installations don't have them (fails in multi-server)
ALTER TABLE `web_domain` DROP COLUMN `fastcgi_php_version`;
