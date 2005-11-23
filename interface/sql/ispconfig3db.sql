# phpMyAdmin MySQL-Dump
# version 2.4.0-rc1
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Erstellungszeit: 24. November 2005 um 00:31
# Server Version: 4.0.23
# PHP-Version: 5.0.3
# Datenbank: `ispconfig3`
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_blacklist`
#

DROP TABLE IF EXISTS mail_blacklist;
CREATE TABLE mail_blacklist (
  blacklist_id int(11) NOT NULL auto_increment,
  server_id int(11) NOT NULL default '0',
  address varchar(255) NOT NULL default '',
  PRIMARY KEY  (blacklist_id),
  KEY server_id (server_id,address)
) TYPE=MyISAM;

#
# Daten für Tabelle `mail_blacklist`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_box`
#

DROP TABLE IF EXISTS mail_box;
CREATE TABLE mail_box (
  mailbox_id int(11) NOT NULL auto_increment,
  sys_userid int(11) NOT NULL default '0',
  sys_groupid int(11) NOT NULL default '0',
  sys_perm_user varchar(5) NOT NULL default '',
  sys_perm_group varchar(5) NOT NULL default '',
  sys_perm_other varchar(5) NOT NULL default '',
  server_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  cryptpwd varchar(128) NOT NULL default '',
  clearpwd varchar(128) NOT NULL default '',
  name varchar(128) NOT NULL default '',
  uid int(10) unsigned NOT NULL default '0',
  gid int(10) unsigned NOT NULL default '0',
  maildir varchar(255) NOT NULL default '',
  quota varchar(255) NOT NULL default '',
  cc varchar(50) NOT NULL default '',
  forward varchar(50) NOT NULL default '',
  autoresponder enum('0','1') NOT NULL default '0',
  autoresponder_text tinytext NOT NULL,
  active enum('0','1') NOT NULL default '1',
  antivirus enum('yes','no') NOT NULL default 'no',
  spamscan enum('yes','no') NOT NULL default 'no',
  spamdelete enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (mailbox_id),
  KEY server_id (server_id,email)
) TYPE=MyISAM;

#
# Daten für Tabelle `mail_box`
#

INSERT INTO mail_box VALUES (1, 1, 0, 'riud', 'riud', '', 1, 'till@test.de', '$1$ye3.TQ1.$v/RvqbuU.Gh7UrLlA6HqX/', '', '', 0, 0, '', '', '', '', '0', '', '1', 'no', 'no', 'no');
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_domain`
#

DROP TABLE IF EXISTS mail_domain;
CREATE TABLE mail_domain (
  domain_id int(11) NOT NULL auto_increment,
  sys_userid int(11) NOT NULL default '0',
  sys_groupid int(11) NOT NULL default '0',
  sys_perm_user varchar(5) NOT NULL default '',
  sys_perm_group varchar(5) NOT NULL default '',
  sys_perm_other varchar(5) NOT NULL default '',
  server_id int(11) NOT NULL default '0',
  domain varchar(255) NOT NULL default '',
  type enum('local','relay','alias') NOT NULL default 'local',
  relay_host varchar(255) NOT NULL default '',
  destination varchar(255) NOT NULL default '',
  active tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (domain_id),
  KEY server_id (server_id,domain,type)
) TYPE=MyISAM;

#
# Daten für Tabelle `mail_domain`
#

INSERT INTO mail_domain VALUES (1, 1, 0, 'riud', 'riud', '', 1, 'test.de', 'local', '', '', 1);
INSERT INTO mail_domain VALUES (2, 1, 0, 'riud', 'riud', '', 1, 'test2.de', 'alias', '', 'test.de', 1);
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_domain_catchall`
#

DROP TABLE IF EXISTS mail_domain_catchall;
CREATE TABLE mail_domain_catchall (
  virtual_default_id int(11) NOT NULL auto_increment,
  server_id int(11) NOT NULL default '0',
  domain varchar(255) NOT NULL default '',
  dest varchar(255) NOT NULL default '',
  PRIMARY KEY  (virtual_default_id),
  KEY server_id (server_id,domain)
) TYPE=MyISAM;

#
# Daten für Tabelle `mail_domain_catchall`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_greylist`
#

DROP TABLE IF EXISTS mail_greylist;
CREATE TABLE mail_greylist (
  greylist_id int(11) NOT NULL auto_increment,
  relay_ip varchar(64) default NULL,
  from_domain varchar(255) default NULL,
  block_expires datetime NOT NULL default '0000-00-00 00:00:00',
  record_expires datetime NOT NULL default '0000-00-00 00:00:00',
  origin_type enum('MANUAL','AUTO') NOT NULL default 'AUTO',
  create_time datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (greylist_id)
) TYPE=MyISAM;

#
# Daten für Tabelle `mail_greylist`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_mailman_domain`
#

DROP TABLE IF EXISTS mail_mailman_domain;
CREATE TABLE mail_mailman_domain (
  mailman_id int(11) NOT NULL auto_increment,
  server_id int(11) NOT NULL default '0',
  domain varchar(255) NOT NULL default '',
  mm_home varchar(255) NOT NULL default '',
  mm_wrap varchar(255) NOT NULL default '',
  mm_user varchar(50) NOT NULL default '',
  mm_group varchar(50) NOT NULL default '',
  PRIMARY KEY  (mailman_id,server_id,domain)
) TYPE=MyISAM;

#
# Daten für Tabelle `mail_mailman_domain`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_redirect`
#

DROP TABLE IF EXISTS mail_redirect;
CREATE TABLE mail_redirect (
  email_id int(11) NOT NULL auto_increment,
  sys_userid int(11) NOT NULL default '0',
  sys_groupid int(11) NOT NULL default '0',
  sys_perm_user varchar(5) NOT NULL default '',
  sys_perm_group varchar(5) NOT NULL default '',
  sys_perm_other varchar(5) NOT NULL default '',
  server_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  destination varchar(255) NOT NULL default '',
  type enum('alias','forward') NOT NULL default 'alias',
  enabled enum('yes','no') NOT NULL default 'yes',
  PRIMARY KEY  (email_id),
  KEY server_id (server_id,email)
) TYPE=MyISAM;

#
# Daten für Tabelle `mail_redirect`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_whitelist`
#

DROP TABLE IF EXISTS mail_whitelist;
CREATE TABLE mail_whitelist (
  whitelist_id int(11) NOT NULL auto_increment,
  server_id int(11) NOT NULL default '0',
  address varchar(255) NOT NULL default '',
  PRIMARY KEY  (whitelist_id),
  KEY server_id (server_id,address)
) TYPE=MyISAM;

#
# Daten für Tabelle `mail_whitelist`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `reseller`
#

DROP TABLE IF EXISTS reseller;
CREATE TABLE reseller (
  reseller_id bigint(20) NOT NULL auto_increment,
  sys_userid int(11) NOT NULL default '0',
  sys_groupid int(11) NOT NULL default '0',
  sys_perm_user varchar(5) NOT NULL default '',
  sys_perm_group varchar(5) NOT NULL default '',
  sys_perm_other varchar(5) NOT NULL default '',
  company varchar(255) NOT NULL default '',
  title varchar(255) NOT NULL default '',
  firstname varchar(255) NOT NULL default '',
  surname varchar(255) NOT NULL default '',
  street varchar(255) NOT NULL default '',
  zip varchar(255) NOT NULL default '',
  city varchar(255) NOT NULL default '',
  country varchar(255) NOT NULL default '',
  telephone varchar(255) NOT NULL default '',
  mobile varchar(255) NOT NULL default '',
  fax varchar(255) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  internet varchar(255) NOT NULL default '',
  icq varchar(255) NOT NULL default '',
  notes text NOT NULL,
  limit_client int(11) NOT NULL default '-1',
  limit_domain int(11) NOT NULL default '-1',
  limit_subdomain int(11) NOT NULL default '-1',
  limit_mailbox int(11) NOT NULL default '-1',
  limit_mailalias int(11) NOT NULL default '-1',
  limit_webquota int(11) NOT NULL default '-1',
  limit_mailquota int(11) NOT NULL default '-1',
  limit_database int(11) NOT NULL default '-1',
  ip_address text NOT NULL,
  PRIMARY KEY  (reseller_id)
) TYPE=MyISAM;

#
# Daten für Tabelle `reseller`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `server`
#

DROP TABLE IF EXISTS server;
CREATE TABLE server (
  server_id bigint(20) NOT NULL auto_increment,
  sys_userid int(11) NOT NULL default '0',
  sys_groupid int(11) NOT NULL default '0',
  sys_perm_user varchar(5) NOT NULL default '',
  sys_perm_group varchar(5) NOT NULL default '',
  sys_perm_other varchar(5) NOT NULL default '',
  server_name varchar(255) NOT NULL default '',
  mail_server int(11) NOT NULL default '0',
  web_server int(11) NOT NULL default '0',
  dns_server int(11) NOT NULL default '0',
  file_server int(11) NOT NULL default '0',
  mysql_server int(11) NOT NULL default '0',
  postgresql_server int(11) NOT NULL default '0',
  firebird_server int(11) NOT NULL default '0',
  active int(11) NOT NULL default '1',
  PRIMARY KEY  (server_id)
) TYPE=MyISAM;

#
# Daten für Tabelle `server`
#

INSERT INTO server VALUES (1, 1, 1, 'riud', 'riud', '', 'Server 1', 1, 0, 0, 0, 0, 0, 0, 1);
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `sys_datalog`
#

DROP TABLE IF EXISTS sys_datalog;
CREATE TABLE sys_datalog (
  datalog_id bigint(20) NOT NULL auto_increment,
  dbtable varchar(255) NOT NULL default '',
  dbidx varchar(255) NOT NULL default '',
  action char(1) NOT NULL default '',
  tstamp bigint(20) NOT NULL default '0',
  user varchar(255) NOT NULL default '',
  data text NOT NULL,
  PRIMARY KEY  (datalog_id)
) TYPE=MyISAM;

#
# Daten für Tabelle `sys_datalog`
#

INSERT INTO sys_datalog VALUES (1, 'mail_domain', 'domain_id:0', 'i', 1132758298, 'admin', 'a:5:{s:9:"server_id";a:2:{s:3:"old";N;s:3:"new";i:1;}s:6:"domain";a:2:{s:3:"old";N;s:3:"new";s:7:"test.de";}s:11:"destination";a:2:{s:3:"old";N;s:3:"new";s:8:"hallo.de";}s:4:"type";a:2:{s:3:"old";N;s:3:"new";s:5:"alias";}s:6:"active";a:2:{s:3:"old";N;s:3:"new";i:1;}}');
INSERT INTO sys_datalog VALUES (2, 'mail_domain', 'domain_id:2', 'u', 1132759303, 'admin', 'a:1:{s:6:"domain";a:2:{s:3:"old";s:7:"test.de";s:3:"new";s:8:"test2.de";}}');
INSERT INTO sys_datalog VALUES (3, 'mail_domain', 'domain_id:2', 'u', 1132759328, 'admin', 'a:1:{s:11:"destination";a:2:{s:3:"old";s:8:"hallo.de";s:3:"new";s:7:"test.de";}}');
INSERT INTO sys_datalog VALUES (4, 'mail_box', 'mailbox_id:0', 'i', 1132775402, 'admin', 'a:3:{s:5:"email";a:2:{s:3:"old";N;s:3:"new";s:12:"till@test.de";}s:8:"cryptpwd";a:2:{s:3:"old";N;s:3:"new";s:5:"hallo";}s:6:"active";a:2:{s:3:"old";N;s:3:"new";i:1;}}');
INSERT INTO sys_datalog VALUES (5, 'mail_box', 'mailbox_id:1', 'u', 1132775575, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (6, 'mail_box', 'mailbox_id:1', 'u', 1132775587, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (7, 'mail_box', 'mailbox_id:1', 'u', 1132775898, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (8, 'mail_box', 'mailbox_id:1', 'u', 1132775901, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (9, 'mail_box', 'mailbox_id:1', 'u', 1132777011, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (10, 'mail_box', 'mailbox_id:1', 'u', 1132777757, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (11, 'mail_box', 'mailbox_id:1', 'u', 1132777760, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (12, 'mail_box', 'mailbox_id:1', 'u', 1132777764, 'admin', 'a:2:{s:5:"email";a:2:{s:3:"old";s:12:"till@test.de";s:3:"new";s:13:"till2@test.de";}s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (13, 'mail_box', 'mailbox_id:1', 'u', 1132777768, 'admin', 'a:2:{s:5:"email";a:2:{s:3:"old";s:13:"till2@test.de";s:3:"new";s:12:"till@test.de";}s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (14, 'mail_box', 'mailbox_id:1', 'u', 1132778380, 'admin', 'a:2:{s:9:"server_id";a:2:{s:3:"old";s:1:"0";s:3:"new";i:1;}s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (15, 'mail_box', 'mailbox_id:1', 'u', 1132784990, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (16, 'mail_box', 'mailbox_id:0', 'i', 1132785424, 'admin', 'a:3:{s:9:"server_id";a:2:{s:3:"old";N;s:3:"new";i:1;}s:5:"email";a:2:{s:3:"old";N;s:3:"new";s:8:"@test.de";}s:6:"active";a:2:{s:3:"old";N;s:3:"new";i:1;}}');
INSERT INTO sys_datalog VALUES (17, 'mail_box', 'mailbox_id:1', 'u', 1132786068, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (18, 'mail_box', 'mailbox_id:1', 'u', 1132786083, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (19, 'mail_box', 'mailbox_id:1', 'u', 1132786772, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (20, 'mail_box', 'mailbox_id:1', 'u', 1132786777, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:32:"598d4c200461b81522a3328565c25f7c";s:3:"new";s:4:"test";}}');
INSERT INTO sys_datalog VALUES (21, 'mail_box', 'mailbox_id:1', 'u', 1132786796, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:0:"";s:3:"new";s:4:"test";}}');
INSERT INTO sys_datalog VALUES (22, 'mail_box', 'mailbox_id:1', 'u', 1132786860, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:0:"";s:3:"new";s:4:"test";}}');
INSERT INTO sys_datalog VALUES (23, 'mail_box', 'mailbox_id:1', 'u', 1132787252, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:0:"";s:3:"new";s:4:"test";}}');
INSERT INTO sys_datalog VALUES (24, 'mail_box', 'mailbox_id:1', 'u', 1132787548, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:34:"$1$ye3.TQ1.$v/RvqbuU.Gh7UrLlA6HqX/";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (25, 'mail_box', 'mailbox_id:1', 'u', 1132787761, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:34:"$1$ye3.TQ1.$v/RvqbuU.Gh7UrLlA6HqX/";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (26, 'mail_box', 'mailbox_id:0', 'i', 1132787775, 'admin', 'a:3:{s:9:"server_id";a:2:{s:3:"old";N;s:3:"new";i:1;}s:5:"email";a:2:{s:3:"old";N;s:3:"new";s:12:"test@test.de";}s:6:"active";a:2:{s:3:"old";N;s:3:"new";i:1;}}');
INSERT INTO sys_datalog VALUES (27, 'mail_box', 'mailbox_id:1', 'u', 1132788121, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:34:"$1$ye3.TQ1.$v/RvqbuU.Gh7UrLlA6HqX/";s:3:"new";s:0:"";}}');
INSERT INTO sys_datalog VALUES (28, 'mail_box', 'mailbox_id:1', 'u', 1132788482, 'admin', 'a:1:{s:8:"cryptpwd";a:2:{s:3:"old";s:34:"$1$ye3.TQ1.$v/RvqbuU.Gh7UrLlA6HqX/";s:3:"new";s:0:"";}}');
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `sys_dbsync`
#

DROP TABLE IF EXISTS sys_dbsync;
CREATE TABLE sys_dbsync (
  id bigint(20) NOT NULL auto_increment,
  jobname varchar(255) NOT NULL default '',
  sync_interval_minutes int(11) NOT NULL default '0',
  db_type varchar(255) NOT NULL default '',
  db_host varchar(255) NOT NULL default '',
  db_name varchar(255) NOT NULL default '',
  db_username varchar(255) NOT NULL default '',
  db_password varchar(255) NOT NULL default '',
  db_tables varchar(255) NOT NULL default 'admin,forms',
  empty_datalog int(11) NOT NULL default '0',
  sync_datalog_external int(11) NOT NULL default '0',
  active int(11) NOT NULL default '1',
  last_datalog_id bigint(20) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY last_datalog_id (last_datalog_id)
) TYPE=MyISAM;

#
# Daten für Tabelle `sys_dbsync`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `sys_filesync`
#

DROP TABLE IF EXISTS sys_filesync;
CREATE TABLE sys_filesync (
  id bigint(20) NOT NULL auto_increment,
  jobname varchar(255) NOT NULL default '',
  sync_interval_minutes int(11) NOT NULL default '0',
  ftp_host varchar(255) NOT NULL default '',
  ftp_path varchar(255) NOT NULL default '',
  ftp_username varchar(255) NOT NULL default '',
  ftp_password varchar(255) NOT NULL default '',
  local_path varchar(255) NOT NULL default '',
  wput_options varchar(255) NOT NULL default '--timestamping --reupload --dont-continue',
  active int(11) NOT NULL default '1',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Daten für Tabelle `sys_filesync`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `sys_group`
#

DROP TABLE IF EXISTS sys_group;
CREATE TABLE sys_group (
  groupid int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description text NOT NULL,
  PRIMARY KEY  (groupid)
) TYPE=MyISAM;

#
# Daten für Tabelle `sys_group`
#

INSERT INTO sys_group VALUES (1, 'admin', 'Administrators group');
INSERT INTO sys_group VALUES (2, 'user', 'Users Group');
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `sys_user`
#

DROP TABLE IF EXISTS sys_user;
CREATE TABLE sys_user (
  userid int(11) NOT NULL auto_increment,
  sys_userid int(11) NOT NULL default '0',
  sys_groupid int(11) NOT NULL default '0',
  sys_perm_user varchar(5) NOT NULL default '',
  sys_perm_group varchar(5) NOT NULL default '',
  sys_perm_other varchar(5) NOT NULL default '',
  username varchar(100) NOT NULL default '',
  passwort varchar(100) NOT NULL default '',
  modules varchar(255) NOT NULL default '',
  startmodule varchar(255) NOT NULL default '',
  app_theme varchar(100) NOT NULL default 'default',
  typ varchar(20) NOT NULL default 'user',
  active tinyint(4) NOT NULL default '1',
  name varchar(100) NOT NULL default '',
  vorname varchar(100) NOT NULL default '',
  unternehmen varchar(100) NOT NULL default '',
  strasse varchar(100) NOT NULL default '',
  ort varchar(100) NOT NULL default '',
  plz varchar(10) NOT NULL default '',
  land varchar(50) NOT NULL default '',
  email varchar(100) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  telefon varchar(100) NOT NULL default '',
  fax varchar(100) NOT NULL default '',
  language varchar(10) NOT NULL default 'de',
  groups varchar(255) NOT NULL default '',
  default_group int(11) NOT NULL default '0',
  PRIMARY KEY  (userid)
) TYPE=MyISAM;

#
# Daten für Tabelle `sys_user`
#

INSERT INTO sys_user VALUES (1, 1, 0, 'riud', 'riud', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin,designer,sites', 'admin', 'default', 'admin', 1, '', 'Administrator', '', '', '', '', '', '', '', '', '', 'en', '1,2', 0);

