-- MySQL dump 10.11
--
-- Host: localhost    Database: dbispconfig
-- ------------------------------------------------------
-- Server version	5.0.51a-3ubuntu5

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `attempts_login`
--

LOCK TABLES `attempts_login` WRITE;
/*!40000 ALTER TABLE `attempts_login` DISABLE KEYS */;
/*!40000 ALTER TABLE `attempts_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `client`
--

LOCK TABLES `client` WRITE;
/*!40000 ALTER TABLE `client` DISABLE KEYS */;
INSERT INTO `client` (`client_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `company_name`, `contact_name`, `street`, `zip`, `city`, `state`, `country`, `telephone`, `mobile`, `fax`, `email`, `internet`, `icq`, `notes`, `default_mailserver`, `limit_maildomain`, `limit_mailbox`, `limit_mailalias`, `limit_mailforward`, `limit_mailcatchall`, `limit_mailrouting`, `limit_mailfilter`, `limit_fetchmail`, `limit_mailquota`, `limit_spamfilter_wblist`, `limit_spamfilter_user`, `limit_spamfilter_policy`, `default_webserver`, `limit_web_ip`, `limit_web_domain`, `limit_web_subdomain`, `limit_web_aliasdomain`, `limit_ftp_user`, `limit_shell_user`, `default_dnsserver`, `limit_dns_zone`, `limit_dns_record`, `limit_client`, `parent_client_id`, `username`, `password`, `language`, `usertheme`) VALUES (1,1,1,'riud','riud','','','Daniel Rossi','','','','','','','','','','http://','','',1,-1,-1,-1,-1,-1,0,-1,-1,-1,0,0,0,1,NULL,-1,-1,-1,-1,0,1,-1,-1,0,0,'danielr','4afc1f4d0d0b0ead0738753c382bc02d','en','default');
/*!40000 ALTER TABLE `client` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dns_rr`
--

LOCK TABLES `dns_rr` WRITE;
/*!40000 ALTER TABLE `dns_rr` DISABLE KEYS */;
/*!40000 ALTER TABLE `dns_rr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `dns_soa`
--

LOCK TABLES `dns_soa` WRITE;
/*!40000 ALTER TABLE `dns_soa` DISABLE KEYS */;
/*!40000 ALTER TABLE `dns_soa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `ftp_user`
--

LOCK TABLES `ftp_user` WRITE;
/*!40000 ALTER TABLE `ftp_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `ftp_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_access`
--

LOCK TABLES `mail_access` WRITE;
/*!40000 ALTER TABLE `mail_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_content_filter`
--

LOCK TABLES `mail_content_filter` WRITE;
/*!40000 ALTER TABLE `mail_content_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_content_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_domain`
--

LOCK TABLES `mail_domain` WRITE;
/*!40000 ALTER TABLE `mail_domain` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_domain` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_forwarding`
--

LOCK TABLES `mail_forwarding` WRITE;
/*!40000 ALTER TABLE `mail_forwarding` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_forwarding` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_get`
--

LOCK TABLES `mail_get` WRITE;
/*!40000 ALTER TABLE `mail_get` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_get` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_greylist`
--

LOCK TABLES `mail_greylist` WRITE;
/*!40000 ALTER TABLE `mail_greylist` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_greylist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_mailman_domain`
--

LOCK TABLES `mail_mailman_domain` WRITE;
/*!40000 ALTER TABLE `mail_mailman_domain` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_mailman_domain` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_traffic`
--

LOCK TABLES `mail_traffic` WRITE;
/*!40000 ALTER TABLE `mail_traffic` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_traffic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_transport`
--

LOCK TABLES `mail_transport` WRITE;
/*!40000 ALTER TABLE `mail_transport` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_transport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `mail_user`
--

LOCK TABLES `mail_user` WRITE;
/*!40000 ALTER TABLE `mail_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `remote_session`
--

LOCK TABLES `remote_session` WRITE;
/*!40000 ALTER TABLE `remote_session` DISABLE KEYS */;
/*!40000 ALTER TABLE `remote_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `remote_user`
--

LOCK TABLES `remote_user` WRITE;
/*!40000 ALTER TABLE `remote_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `remote_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `server`
--

LOCK TABLES `server` WRITE;
/*!40000 ALTER TABLE `server` DISABLE KEYS */;
INSERT INTO `server` (`server_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `db_server`, `vserver_server`, `config`, `updated`, `active`) VALUES (1,1,1,'riud','riud','r','Kubuntu',1,1,1,1,1,1,'[global]\nwebserver=apache\nmailserver=postfix\ndnsserver=mydns\n\n[server]\nip_address=192.168.0.105\nnetmask=255.255.255.0\ngateway=192.168.0.1\nhostname=server1.example.com\nnameservers=193.174.32.18,145.253.2.75\n\n[mail]\nmodule=postfix_mysql\nmaildir_path=/home/vmail/[domain]/[localpart]/\nhomedir_path=/home/vmail/\nmailuser_uid=5000\nmailuser_gid=5000\nmailuser_name=vmail\nmailuser_group=vmail\nrelayhost=\nrelayhost_user=\nrelayhost_password=\nmailbox_size_limit=0\nmessage_size_limit=0\n\n[getmail]\ngetmail_config_dir=/etc/getmail\n\n[web]\nwebsite_path=/var/clients/client[client_id]/web[website_id]\nwebsite_symlinks=/var/www/[website_domain]/:/var/clients/client[client_id]/[website_domain]/\nvhost_conf_dir=/etc/apache2/sites-available\nvhost_conf_enabled_dir=/etc/apache2/sites-enabled\n\n[fastcgi]\nfastcgi_starter_path=/var/www/php-fcgi-scripts/[system_user]/\nfastcgi_starter_script=.php-fcgi-starter\nfastcgi_alias=/php/\nfastcgi_phpini_path=/etc/php5/cgi/\nfastcgi_children=8\nfastcgi_max_requests=5000\nfastcgi_bin=/usr/bin/php-cgi\n\n[jailkit]\njailkit_chroot_home=/home/[username]\njailkit_chroot_app_sections=basicshell editors extendedshell netutils ssh sftp scp groups jk_lsh\njailkit_chroot_app_programs=/usr/bin/groups /usr/bin/id /usr/bin/dircolors /usr/bin/lesspipe /usr/bin/basename /usr/bin/dirname /usr/bin/nano /usr/bin/pico\n',0,1);
/*!40000 ALTER TABLE `server` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `server_ip`
--

LOCK TABLES `server_ip` WRITE;
/*!40000 ALTER TABLE `server_ip` DISABLE KEYS */;
INSERT INTO `server_ip` (`server_ip_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `ip_address`, `virtualhost`) VALUES (1,1,1,'riud','riud','',1,'192.168.5.101','y');
/*!40000 ALTER TABLE `server_ip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `shell_user`
--

LOCK TABLES `shell_user` WRITE;
/*!40000 ALTER TABLE `shell_user` DISABLE KEYS */;
INSERT INTO `shell_user` (`shell_user_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `parent_domain_id`, `username`, `password`, `quota_size`, `active`, `puser`, `pgroup`, `shell`, `dir`, `chroot`) VALUES (15,1,1,'riud','riud','',1,6,'danweb','$1$UTue}CsC$kuZQQXa6ZeuSBEOrk7LNy1',-1,'y','web6','client0','/bin/bash','/var/clients/client0/web6','jailkit');
/*!40000 ALTER TABLE `shell_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `spamfilter_policy`
--

LOCK TABLES `spamfilter_policy` WRITE;
/*!40000 ALTER TABLE `spamfilter_policy` DISABLE KEYS */;
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES (1,1,0,'riud','riud','r','Non-paying','N','N','N','N','Y','Y','Y','N','Y','','','','','','',3,7,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,1,0,'riud','riud','r','Uncensored','Y','Y','Y','Y','N','N','N','N','N',NULL,NULL,NULL,NULL,NULL,NULL,3,999,999,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,1,0,'riud','riud','r','Wants all spam','N','Y','N','N','N','N','N','N','Y',NULL,NULL,NULL,NULL,NULL,NULL,3,999,999,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,1,0,'riud','riud','r','Wants viruses','Y','N','Y','Y','N','N','N','N','Y',NULL,NULL,NULL,NULL,NULL,NULL,3,6.9,6.9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,1,0,'riud','riud','r','Normal','N','N','N','N','N','N','N','N','Y',NULL,NULL,NULL,NULL,NULL,NULL,3,6.9,6.9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,1,0,'riud','riud','r','Trigger happy','N','N','N','N','N','N','N','N','Y',NULL,NULL,NULL,NULL,NULL,NULL,3,5,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,1,0,'riud','riud','r','Permissive','N','N','N','Y','N','N','N','N','Y',NULL,NULL,NULL,NULL,NULL,NULL,3,10,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `spamfilter_policy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `spamfilter_users`
--

LOCK TABLES `spamfilter_users` WRITE;
/*!40000 ALTER TABLE `spamfilter_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `spamfilter_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `spamfilter_wblist`
--

LOCK TABLES `spamfilter_wblist` WRITE;
/*!40000 ALTER TABLE `spamfilter_wblist` DISABLE KEYS */;
/*!40000 ALTER TABLE `spamfilter_wblist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `support_message`
--

LOCK TABLES `support_message` WRITE;
/*!40000 ALTER TABLE `support_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_datalog`
--

LOCK TABLES `sys_datalog` WRITE;
/*!40000 ALTER TABLE `sys_datalog` DISABLE KEYS */;
INSERT INTO `sys_datalog` (`datalog_id`, `server_id`, `dbtable`, `dbidx`, `action`, `tstamp`, `user`, `data`) VALUES (1,0,'client','client_id:1','i',1212765060,'admin','a:2:{s:3:\"new\";a:49:{s:9:\"client_id\";s:1:\"1\";s:10:\"sys_userid\";s:1:\"1\";s:11:\"sys_groupid\";s:1:\"1\";s:13:\"sys_perm_user\";s:4:\"riud\";s:14:\"sys_perm_group\";s:4:\"riud\";s:14:\"sys_perm_other\";s:0:\"\";s:12:\"company_name\";s:0:\"\";s:12:\"contact_name\";s:12:\"Daniel Rossi\";s:6:\"street\";s:0:\"\";s:3:\"zip\";s:0:\"\";s:4:\"city\";s:0:\"\";s:5:\"state\";s:0:\"\";s:7:\"country\";s:0:\"\";s:9:\"telephone\";s:0:\"\";s:6:\"mobile\";s:0:\"\";s:3:\"fax\";s:0:\"\";s:5:\"email\";s:0:\"\";s:8:\"internet\";s:7:\"http://\";s:3:\"icq\";s:0:\"\";s:5:\"notes\";s:0:\"\";s:18:\"default_mailserver\";s:1:\"1\";s:16:\"limit_maildomain\";s:2:\"-1\";s:13:\"limit_mailbox\";s:2:\"-1\";s:15:\"limit_mailalias\";s:2:\"-1\";s:17:\"limit_mailforward\";s:2:\"-1\";s:18:\"limit_mailcatchall\";s:2:\"-1\";s:17:\"limit_mailrouting\";s:1:\"0\";s:16:\"limit_mailfilter\";s:2:\"-1\";s:15:\"limit_fetchmail\";s:2:\"-1\";s:15:\"limit_mailquota\";s:2:\"-1\";s:23:\"limit_spamfilter_wblist\";s:1:\"0\";s:21:\"limit_spamfilter_user\";s:1:\"0\";s:23:\"limit_spamfilter_policy\";s:1:\"0\";s:17:\"default_webserver\";s:1:\"1\";s:12:\"limit_web_ip\";N;s:16:\"limit_web_domain\";s:2:\"-1\";s:19:\"limit_web_subdomain\";s:2:\"-1\";s:21:\"limit_web_aliasdomain\";s:2:\"-1\";s:14:\"limit_ftp_user\";s:2:\"-1\";s:16:\"limit_shell_user\";s:1:\"0\";s:17:\"default_dnsserver\";s:1:\"1\";s:14:\"limit_dns_zone\";s:2:\"-1\";s:16:\"limit_dns_record\";s:2:\"-1\";s:12:\"limit_client\";s:1:\"0\";s:16:\"parent_client_id\";s:1:\"0\";s:8:\"username\";s:7:\"danielr\";s:8:\"password\";s:32:\"4afc1f4d0d0b0ead0738753c382bc02d\";s:8:\"language\";s:2:\"en\";s:9:\"usertheme\";s:7:\"default\";}s:3:\"old\";a:49:{s:9:\"client_id\";N;s:10:\"sys_userid\";N;s:11:\"sys_groupid\";N;s:13:\"sys_perm_user\";N;s:14:\"sys_perm_group\";N;s:14:\"sys_perm_other\";s:0:\"\";s:12:\"company_name\";s:0:\"\";s:12:\"contact_name\";N;s:6:\"street\";s:0:\"\";s:3:\"zip\";s:0:\"\";s:4:\"city\";s:0:\"\";s:5:\"state\";s:0:\"\";s:7:\"country\";s:0:\"\";s:9:\"telephone\";s:0:\"\";s:6:\"mobile\";s:0:\"\";s:3:\"fax\";s:0:\"\";s:5:\"email\";s:0:\"\";s:8:\"internet\";N;s:3:\"icq\";s:0:\"\";s:5:\"notes\";s:0:\"\";s:18:\"default_mailserver\";N;s:16:\"limit_maildomain\";N;s:13:\"limit_mailbox\";N;s:15:\"limit_mailalias\";N;s:17:\"limit_mailforward\";N;s:18:\"limit_mailcatchall\";N;s:17:\"limit_mailrouting\";N;s:16:\"limit_mailfilter\";N;s:15:\"limit_fetchmail\";N;s:15:\"limit_mailquota\";N;s:23:\"limit_spamfilter_wblist\";N;s:21:\"limit_spamfilter_user\";N;s:23:\"limit_spamfilter_policy\";N;s:17:\"default_webserver\";N;s:12:\"limit_web_ip\";N;s:16:\"limit_web_domain\";N;s:19:\"limit_web_subdomain\";N;s:21:\"limit_web_aliasdomain\";N;s:14:\"limit_ftp_user\";N;s:16:\"limit_shell_user\";N;s:17:\"default_dnsserver\";N;s:14:\"limit_dns_zone\";N;s:16:\"limit_dns_record\";N;s:12:\"limit_client\";N;s:16:\"parent_client_id\";N;s:8:\"username\";N;s:8:\"password\";N;s:8:\"language\";N;s:9:\"usertheme\";N;}}');
/*!40000 ALTER TABLE `sys_datalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_dbsync`
--

LOCK TABLES `sys_dbsync` WRITE;
/*!40000 ALTER TABLE `sys_dbsync` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_dbsync` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_filesync`
--

LOCK TABLES `sys_filesync` WRITE;
/*!40000 ALTER TABLE `sys_filesync` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_filesync` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_group`
--

LOCK TABLES `sys_group` WRITE;
/*!40000 ALTER TABLE `sys_group` DISABLE KEYS */;
INSERT INTO `sys_group` (`groupid`, `name`, `description`, `client_id`) VALUES (1,'admin','Administrators group',0),(4,'danielr','',1);
/*!40000 ALTER TABLE `sys_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sys_user`
--

LOCK TABLES `sys_user` WRITE;
/*!40000 ALTER TABLE `sys_user` DISABLE KEYS */;
INSERT INTO `sys_user` (`userid`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `username`, `passwort`, `modules`, `startmodule`, `app_theme`, `typ`, `active`, `language`, `groups`, `default_group`, `client_id`) VALUES (1,1,0,'riud','riud','','admin','21232f297a57a5a743894a0e4a801fc3','admin,client,mail,monitor,sites,dns','mail','default','admin',1,'en','1,2',1,0),(3,1,1,'riud','riud','','danielr','4afc1f4d0d0b0ead0738753c382bc02d','mail,sites,dns','mail','default','user',1,'en','4',4,1);
/*!40000 ALTER TABLE `sys_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `web_domain`
--

LOCK TABLES `web_domain` WRITE;
/*!40000 ALTER TABLE `web_domain` DISABLE KEYS */;
INSERT INTO `web_domain` (`domain_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `ip_address`, `domain`, `type`, `parent_domain_id`, `vhost_type`, `document_root`, `system_user`, `system_group`, `hd_quota`, `traffic_quota`, `cgi`, `ssi`, `suexec`, `php`, `redirect_type`, `redirect_path`, `ssl`, `ssl_state`, `ssl_locality`, `ssl_organisation`, `ssl_organisation_unit`, `ssl_country`, `ssl_request`, `ssl_cert`, `ssl_bundle`, `ssl_action`, `apache_directives`, `active`) VALUES (6,1,4,'riud','ru','',1,'192.168.5.101','test.com','vhost',0,'name','/var/clients/client0/web6','web6','client0',-1,-1,'y','y','y','fast-cgi',NULL,NULL,'y',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'y');
/*!40000 ALTER TABLE `web_domain` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-06-06 18:06:32
