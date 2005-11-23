# phpMyAdmin MySQL-Dump
# version 2.4.0-rc1
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Erstellungszeit: 23. November 2005 um 18:15
# Server Version: 4.0.22
# PHP-Version: 5.0.2
# Datenbank: `ispconfig3`
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_blacklist`
#

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
  autoresponder enum('yes','no') NOT NULL default 'no',
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

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_domain`
#

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
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `sys_dbsync`
#

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

