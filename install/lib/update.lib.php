<?php
/*
Copyright (c) 2007-2010, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

function prepareDBDump() {
	global $conf;

	//** load the pre update sql script do perform modifications on the database before the database is dumped
	if(is_file(ISPC_INSTALL_ROOT."/install/sql/pre_update.sql")) {
		if($conf['mysql']['admin_password'] == '') {
			caselog("mysql --default-character-set=".$conf['mysql']['charset']." -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' '".$conf['mysql']['database']."' < '".ISPC_INSTALL_ROOT."/install/sql/pre_update.sql' &> /dev/null", __FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in ispconfig3.sql');
		} else {
			caselog("mysql --default-character-set=".$conf['mysql']['charset']." -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' -p'".$conf['mysql']['admin_password']."' '".$conf['mysql']['database']."' < '".ISPC_INSTALL_ROOT."/install/sql/pre_update.sql' &> /dev/null", __FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in ispconfig3.sql');
		}
	}

	//** export the current database data
	if( !empty($conf["mysql"]["admin_password"]) ) {

		system("mysqldump -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' -p'".$conf['mysql']['admin_password']."' -c -t --add-drop-table --create-options --quick --result-file=existing_db.sql ".$conf['mysql']['database']);
	}
	else {

		system("mysqldump -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' -c -t --add-drop-table --create-options --quick --result-file=existing_db.sql ".$conf['mysql']['database']);
	}

	if(filesize('existing_db.sql') < 30000) die('Possible problem with dumping the database. We will stop here. Please check the file existing_db.sql');

	// create a backup copy of the ispconfig database in the root folder
	$backup_db_name = '/root/ispconfig_db_backup_'.@date('Y-m-d_h-i').'.sql';
	copy('existing_db.sql',$backup_db_name);
	exec("chmod 700 $backup_db_name");
	exec("chown root:root $backup_db_name");
}

function updateDbAndIni() {
	global $inst, $conf;

	//* Update $conf array with values from the server.ini that shall be preserved
	$tmp = $inst->db->queryOneRecord("SELECT * FROM ".$conf["mysql"]["database"].".server WHERE server_id = ".$conf['server_id']);
	$ini_array = ini_to_array(stripslashes($tmp['config']));

	if(count($ini_array) == 0) die('Unable to read server configuration from database.');

	$conf['services']['mail'] = ($tmp['mail_server'] == 1)?true:false;
	$conf['services']['web'] = ($tmp['web_server'] == 1)?true:false;
	$conf['services']['dns'] = ($tmp['dns_server'] == 1)?true:false;
	$conf['services']['file'] = ($tmp['file_server'] == 1)?true:false;
	$conf['services']['db'] = ($tmp['db_server'] == 1)?true:false;
	$conf['services']['vserver'] = ($tmp['vserver_server'] == 1)?true:false;
	$conf['postfix']['vmail_mailbox_base'] = $ini_array['mail']['homedir_path'];

	//** Delete the old database
	if( !$inst->db->query('DROP DATABASE IF EXISTS '.$conf['mysql']['database']) ) {

		$inst->error('Unable to drop MySQL database: '.$conf['mysql']['database'].'.');
	}

	//** Create the mysql database
	$inst->configure_database();

	//** empty all databases
	$db_tables = $inst->db->getTables();

	foreach($db_tables as $table) {

		$inst->db->query("TRUNCATE $table");
	}

	//** load old data back into database
	if( !empty($conf["mysql"]["admin_password"]) ) {

		system("mysql --default-character-set=".$conf['mysql']['charset']." -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' -p'".$conf['mysql']['admin_password']."' ".$conf['mysql']['database']." < existing_db.sql");
	} else {

		system("mysql --default-character-set=".$conf['mysql']['charset']." -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' ".$conf['mysql']['database']." < existing_db.sql");
	}


	//** Update server ini
	$tmp_server_rec = $inst->db->queryOneRecord("SELECT config FROM server WHERE server_id = ".$conf['server_id']);
	$old_ini_array = ini_to_array(stripslashes($tmp_server_rec['config']));
	unset($tmp_server_rec);
	$tpl_ini_array = ini_to_array(rf('tpl/server.ini.master'));

	// update the new template with the old values
	if(is_array($old_ini_array)) {
		foreach($old_ini_array as $tmp_section_name => $tmp_section_content) {
			foreach($tmp_section_content as $tmp_var_name => $tmp_var_content) {
				$tpl_ini_array[$tmp_section_name][$tmp_var_name] = $tmp_var_content;
			}
		}
	}

	$new_ini = array_to_ini($tpl_ini_array);
	$inst->db->query("UPDATE server SET config = '".mysql_real_escape_string($new_ini)."' WHERE server_id = ".$conf['server_id']);
	unset($old_ini_array);
	unset($tpl_ini_array);
	unset($new_ini);


	//** Update system ini
	$tmp_server_rec = $inst->db->queryOneRecord("SELECT config FROM sys_ini WHERE sysini_id = 1");
	$old_ini_array = ini_to_array(stripslashes($tmp_server_rec['config']));
	unset($tmp_server_rec);
	$tpl_ini_array = ini_to_array(rf('tpl/system.ini.master'));

	// update the new template with the old values
	if(is_array($old_ini_array)) {
		foreach($old_ini_array as $tmp_section_name => $tmp_section_content) {
			foreach($tmp_section_content as $tmp_var_name => $tmp_var_content) {
				$tpl_ini_array[$tmp_section_name][$tmp_var_name] = $tmp_var_content;
			}
		}
	}

	$new_ini = array_to_ini($tpl_ini_array);
	$tmp = $inst->db->queryOneRecord('SELECT count(sysini_id) as number FROM sys_ini WHERE 1');
	if($tmp['number'] == 0) {
		$inst->db->query("INSERT INTO sys_ini (sysini_id, config) VALUES (1,'".mysql_real_escape_string($new_ini)."')");
	} else {
		$inst->db->query("UPDATE sys_ini SET config = '".mysql_real_escape_string($new_ini)."' WHERE sysini_id = 1");
	}
	unset($old_ini_array);
	unset($tpl_ini_array);
	unset($new_ini);
}

?>
