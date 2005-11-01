# phpMyAdmin MySQL-Dump
# version 2.4.0-rc1
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Erstellungszeit: 18. Oktober 2005 um 16:00
# Server Version: 4.0.22
# PHP-Version: 5.0.2
# Datenbank: `scrigo`
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `haendler`
#

DROP TABLE IF EXISTS haendler;
CREATE TABLE haendler (
  id bigint(20) NOT NULL auto_increment,
  KD_Nr varchar(15) NOT NULL default '',
  Typ varchar(255) NOT NULL default 'Verarbeiter',
  PLZ_Index varchar(5) NOT NULL default '',
  Name1 varchar(255) NOT NULL default '',
  Name2 varchar(255) NOT NULL default '',
  Strasse varchar(255) NOT NULL default '',
  PLZ varchar(20) NOT NULL default '',
  PLZ_Ort varchar(255) NOT NULL default '',
  Ort varchar(255) NOT NULL default '',
  Region varchar(255) NOT NULL default '',
  Land varchar(255) NOT NULL default '',
  Telefon varchar(255) NOT NULL default '',
  Fax varchar(255) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  Internet varchar(255) NOT NULL default '',
  Verkauft varchar(255) NOT NULL default '',
  bem text NOT NULL,
  Land_lt_Haendler varchar(255) NOT NULL default '',
  Land_dt varchar(255) NOT NULL default '',
  Land_int varchar(255) NOT NULL default '',
  LK varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Daten für Tabelle `haendler`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `media`
#

DROP TABLE IF EXISTS media;
CREATE TABLE media (
  media_id bigint(20) NOT NULL auto_increment,
  media_profile_id varchar(255) NOT NULL default '',
  media_cat_id varchar(255) NOT NULL default '',
  media_name varchar(255) NOT NULL default '',
  media_type varchar(30) NOT NULL default '',
  media_size varchar(255) NOT NULL default '',
  media_format varchar(255) NOT NULL default '',
  thumbnail int(11) NOT NULL default '0',
  path0 varchar(255) NOT NULL default '',
  path1 varchar(255) NOT NULL default '',
  path2 varchar(255) NOT NULL default '',
  path3 varchar(255) NOT NULL default '',
  path4 varchar(255) NOT NULL default '',
  path5 varchar(255) NOT NULL default '',
  PRIMARY KEY  (media_id)
) TYPE=MyISAM;

#
# Daten für Tabelle `media`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `media_cat`
#

DROP TABLE IF EXISTS media_cat;
CREATE TABLE media_cat (
  media_cat_id int(10) unsigned NOT NULL auto_increment,
  parent varchar(100) NOT NULL default '',
  sort varchar(10) NOT NULL default '9999',
  active char(1) NOT NULL default '1',
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (media_cat_id),
  UNIQUE KEY tree_id (media_cat_id),
  KEY sort (sort)
) TYPE=MyISAM PACK_KEYS=1;

#
# Daten für Tabelle `media_cat`
#

INSERT INTO media_cat VALUES (1, '0', '', '1', 'Images');
# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `media_profile`
#

DROP TABLE IF EXISTS media_profile;
CREATE TABLE media_profile (
  media_profile_id bigint(20) NOT NULL auto_increment,
  media_cat_id varchar(255) NOT NULL default '',
  profile_name varchar(255) NOT NULL default '',
  profile_description text NOT NULL,
  thumbnail int(11) NOT NULL default '1',
  original int(11) NOT NULL default '0',
  path0 varchar(255) NOT NULL default '',
  path1 varchar(255) NOT NULL default '',
  resize1 varchar(50) NOT NULL default '',
  options1 varchar(50) NOT NULL default '',
  path2 varchar(255) NOT NULL default '',
  resize2 varchar(50) NOT NULL default '',
  options2 varchar(50) NOT NULL default '',
  path3 varchar(255) NOT NULL default '',
  resize3 varchar(50) NOT NULL default '',
  options3 varchar(50) NOT NULL default '',
  path4 varchar(255) NOT NULL default '',
  resize4 varchar(50) NOT NULL default '',
  options4 varchar(50) NOT NULL default '',
  path5 varchar(255) NOT NULL default '',
  resize5 varchar(50) NOT NULL default '',
  options5 varchar(50) NOT NULL default '',
  PRIMARY KEY  (media_profile_id)
) TYPE=MyISAM;

#
# Daten für Tabelle `media_profile`
#

INSERT INTO media_profile VALUES (1, '2', 'Default Image', '', 1, 1, '[ROOT]/web/media/original/[ID].[EXT]', '[ROOT]/web/media/images/img_[ID]_80x110.[EXT]', '80x110', '-sharpen 2', '', '', '', '', '', '', '', '', '', '', '', '');
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

INSERT INTO sys_user VALUES (1, 1, 0, 'riud', 'riud', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin,clients,designer,resellers,sites', 'admin', 'default', 'admin', 1, '', 'Administrator', '', '', '', '', '', '', '', '', '', 'en', '1,2', 0);

