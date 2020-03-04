-- add new proxy_protocol column
ALTER TABLE `web_domain`
    ADD COLUMN `proxy_protocol` ENUM('n','y') NOT NULL DEFAULT 'y' AFTER `log_retention`;
