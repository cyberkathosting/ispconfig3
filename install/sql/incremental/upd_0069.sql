ALTER TABLE `cron` ADD `log` enum('n','y') NOT NULL default 'n' AFTER `run_wday`;
