--
-- Table structure for table `openvz_ip`
--

CREATE TABLE IF NOT EXISTS `openvz_ip` (
  `ip_address_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `ip_address` varchar(15) DEFAULT NULL,
  `vm_id` int(11) NOT NULL DEFAULT '0',
  `reserved` varchar(255) NOT NULL DEFAULT 'n',
  PRIMARY KEY (`ip_address_id`)
)  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `openvz_ip`
--

-- --------------------------------------------------------

--
-- Table structure for table `openvz_ostemplate`
--

CREATE TABLE IF NOT EXISTS `openvz_ostemplate` (
  `ostemplate_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `template_file` varchar(255) NOT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `allservers` varchar(255) NOT NULL DEFAULT 'y',
  `active` varchar(255) NOT NULL DEFAULT 'y',
  `description` text,
  PRIMARY KEY (`ostemplate_id`)
)  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `openvz_ostemplate`
--

INSERT INTO `openvz_ostemplate` (`ostemplate_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `template_name`, `template_file`, `server_id`, `allservers`, `active`, `description`) VALUES(1, 1, 1, 'riud', 'riud', '', 'Debian minimal', 'debian-minimal-x86', 1, 'y', 'y', 'Debain minmal image.');

-- --------------------------------------------------------

--
-- Table structure for table `openvz_template`
--

CREATE TABLE IF NOT EXISTS `openvz_template` (
  `template_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `diskspace` int(11) NOT NULL DEFAULT '0',
  `traffic` int(11) NOT NULL DEFAULT '-1',
  `bandwidth` int(11) NOT NULL DEFAULT '-1',
  `ram` int(11) NOT NULL DEFAULT '0',
  `ram_burst` int(11) NOT NULL DEFAULT '0',
  `cpu_units` int(11) NOT NULL DEFAULT '1000',
  `cpu_num` int(11) NOT NULL DEFAULT '4',
  `cpu_limit` int(11) NOT NULL DEFAULT '400',
  `io_priority` int(11) NOT NULL DEFAULT '4',
  `active` varchar(255) NOT NULL DEFAULT 'y',
  `description` text,
  `numproc` varchar(255) DEFAULT NULL,
  `numtcpsock` varchar(255) DEFAULT NULL,
  `numothersock` varchar(255) DEFAULT NULL,
  `vmguarpages` varchar(255) DEFAULT NULL,
  `kmemsize` varchar(255) DEFAULT NULL,
  `tcpsndbuf` varchar(255) DEFAULT NULL,
  `tcprcvbuf` varchar(255) DEFAULT NULL,
  `othersockbuf` varchar(255) DEFAULT NULL,
  `dgramrcvbuf` varchar(255) DEFAULT NULL,
  `oomguarpages` varchar(255) DEFAULT NULL,
  `privvmpages` varchar(255) DEFAULT NULL,
  `lockedpages` varchar(255) DEFAULT NULL,
  `shmpages` varchar(255) DEFAULT NULL,
  `physpages` varchar(255) DEFAULT NULL,
  `numfile` varchar(255) DEFAULT NULL,
  `avnumproc` varchar(255) DEFAULT NULL,
  `numflock` varchar(255) DEFAULT NULL,
  `numpty` varchar(255) DEFAULT NULL,
  `numsiginfo` varchar(255) DEFAULT NULL,
  `dcachesize` varchar(255) DEFAULT NULL,
  `numiptent` varchar(255) DEFAULT NULL,
  `swappages` varchar(255) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `nameserver` varchar(255) DEFAULT NULL,
  `create_dns` varchar(1) NOT NULL DEFAULT 'n',
  `capability` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`template_id`)
)  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `openvz_template`
--

INSERT INTO `openvz_template` (`template_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `template_name`, `diskspace`, `traffic`, `bandwidth`, `ram`, `ram_burst`, `cpu_units`, `cpu_num`, `cpu_limit`, `io_priority`, `active`, `description`, `numproc`, `numtcpsock`, `numothersock`, `vmguarpages`, `kmemsize`, `tcpsndbuf`, `tcprcvbuf`, `othersockbuf`, `dgramrcvbuf`, `oomguarpages`, `privvmpages`, `lockedpages`, `shmpages`, `physpages`, `numfile`, `avnumproc`, `numflock`, `numpty`, `numsiginfo`, `dcachesize`, `numiptent`, `swappages`, `hostname`, `nameserver`, `create_dns`, `capability`) VALUES(1, 1, 1, 'riud', 'riud', '', 'small', 10, -1, -1, 256, 512, 1000, 4, 400, 4, 'y', '', '999999:999999', '7999992:7999992', '7999992:7999992', '65536:65536', '2147483646:2147483646', '214748160:396774400', '214748160:396774400', '214748160:396774400', '214748160:396774400', '65536:65536', '131072:131072', '999999:999999', '65536:65536', '0:2147483647', '23999976:23999976', '180:180', '999999:999999', '500000:500000', '999999:999999', '2147483646:2147483646', '999999:999999', '256000:256000', 'v{VEID}.test.tld', '8.8.8.8 8.8.4.4', 'n', '');

-- --------------------------------------------------------

--
-- Table structure for table `openvz_traffic`
--

CREATE TABLE IF NOT EXISTS `openvz_traffic` (
  `veid` int(11) NOT NULL,
  `traffic_date` date NOT NULL,
  `traffic_bytes` bigint(32) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`veid`,`traffic_date`)
) DEFAULT CHARSET=utf8;

--
-- Dumping data for table `openvz_traffic`
--


-- --------------------------------------------------------

--
-- Table structure for table `openvz_vm`
--

CREATE TABLE IF NOT EXISTS `openvz_vm` (
  `vm_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `veid` int(10) unsigned NOT NULL,
  `ostemplate_id` int(11) NOT NULL DEFAULT '0',
  `template_id` int(11) NOT NULL DEFAULT '0',
  `ip_address` varchar(255) NOT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `vm_password` varchar(255) DEFAULT NULL,
  `start_boot` varchar(255) NOT NULL DEFAULT 'y',
  `active` varchar(255) NOT NULL DEFAULT 'y',
  `active_until_date` date NOT NULL,
  `description` text,
  `diskspace` int(11) NOT NULL DEFAULT '0',
  `traffic` int(11) NOT NULL DEFAULT '-1',
  `bandwidth` int(11) NOT NULL DEFAULT '-1',
  `ram` int(11) NOT NULL DEFAULT '0',
  `ram_burst` int(11) NOT NULL DEFAULT '0',
  `cpu_units` int(11) NOT NULL DEFAULT '1000',
  `cpu_num` int(11) NOT NULL DEFAULT '4',
  `cpu_limit` int(11) NOT NULL DEFAULT '400',
  `io_priority` int(11) NOT NULL DEFAULT '4',
  `nameserver` varchar(255) NOT NULL DEFAULT '8.8.8.8 8.8.4.4',
  `create_dns` varchar(1) NOT NULL DEFAULT 'n',
  `capability` text NOT NULL,
  `config` mediumtext NOT NULL,
  PRIMARY KEY (`vm_id`)
)  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `openvz_vm`
--

-- --------------------------------------------------------
