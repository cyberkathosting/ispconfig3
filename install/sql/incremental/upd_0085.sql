ALTER TABLE `web_domain` CHANGE `folder_directive_snippets` `folder_directive_snippets` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE `web_domain` ADD `log_retention` INT NOT NULL DEFAULT '30' AFTER `https_port`;
ALTER TABLE `web_domain` CHANGE `stats_type` `stats_type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'awstats';
ALTER TABLE `spamfilter_policy` 
CHANGE `virus_lover` `virus_lover` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `spam_lover` `spam_lover` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `banned_files_lover` `banned_files_lover` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bad_header_lover` `bad_header_lover` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bypass_virus_checks` `bypass_virus_checks` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bypass_spam_checks` `bypass_spam_checks` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bypass_banned_checks` `bypass_banned_checks` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `bypass_header_checks` `bypass_header_checks` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `spam_modifies_subj` `spam_modifies_subj` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `warnvirusrecip` `warnvirusrecip` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `warnbannedrecip` `warnbannedrecip` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
CHANGE `warnbadhrecip` `warnbadhrecip` ENUM('N','Y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N';
ALTER TABLE `dns_rr` CHANGE `data` `data` TEXT NOT NULL;
ALTER TABLE `web_database` CHANGE `database_quota` `database_quota` INT(11) NULL DEFAULT NULL;
ALTER TABLE `web_domain` ADD `log_retention` INT NOT NULL DEFAULT '30' ;
ALTER TABLE spamfilter_policy CHANGE spam_tag_level spam_tag_level DECIMAL(5,2) NULL DEFAULT NULL, CHANGE spam_tag2_level spam_tag2_level DECIMAL(5,2) NULL DEFAULT NULL, CHANGE spam_kill_level spam_kill_level DECIMAL(5,2) NULL DEFAULT NULL, CHANGE spam_dsn_cutoff_level spam_dsn_cutoff_level DECIMAL(5,2) NULL DEFAULT NULL, CHANGE spam_quarantine_cutoff_level spam_quarantine_cutoff_level DECIMAL(5,2) NULL DEFAULT NULL;
UPDATE `web_database` as d LEFT JOIN `web_domain` as w ON (w.domain_id = d.parent_domain_id) SET d.parent_domain_id = 0 WHERE w.domain_id IS NULL AND d.parent_domain_id != 0 AND (SELECT EXISTS(SELECT * FROM web_domain)); 