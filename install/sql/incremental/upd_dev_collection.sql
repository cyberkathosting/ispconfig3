ALTER TABLE `mail_mailinglist` ADD `list_type` enum('open','closed') NOT NULL DEFAULT 'open';
ALTER TABLE `mail_mailinglist` ADD `subject_prefix` varchar(50) NOT NULL DEFAULT '';
ALTER TABLE `mail_mailinglist` ADD `admins` mediumtext;
ALTER TABLE `mail_mailinglist` ADD `digestinterval` int(11) NOT NULL DEFAULT '7';
ALTER TABLE `mail_mailinglist` ADD `digestmaxmails` int(11) NOT NULL DEFAULT '50';
ALTER TABLE `mail_mailinglist` ADD `archive` enum('n','y') NOT NULL DEFAULT 'n';
ALTER TABLE `mail_mailinglist` ADD `digesttext` ENUM('n','y') NOT NULL DEFAULT 'n';
ALTER TABLE `mail_mailinglist` ADD `digestsub` ENUM('n','y') NOT NULL DEFAULT 'n';
ALTER TABLE `mail_mailinglist` ADD `mail_footer` mediumtext;
ALTER TABLE `mail_mailinglist` ADD `subscribe_policy` enum('disabled','confirm','approval','both','none') NOT NULL DEFAULT 'confirm';
ALTER TABLE `mail_mailinglist` ADD `posting_policy` enum('closed','moderated','free') NOT NULL DEFAULT 'free';
ALTER TABLE `sys_user` ADD `last_login_ip` VARCHAR(50) NULL AFTER `lost_password_reqtime`;
ALTER TABLE `sys_user` ADD `last_login_at` BIGINT(20) NULL AFTER `last_login_ip`;
ALTER TABLE `sys_remoteaction` CHANGE `action_state` `action_state` ENUM('pending','processing','ok','warning','error') NOT NULL DEFAULT 'pending';
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
