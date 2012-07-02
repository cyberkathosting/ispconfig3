<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

/*
	This function returns a string that describes the installed
	Linux distribution. e.g. debian40 for Debian GNU/Linux 4.0
*/



/*
Comments to completion forever ;-)
commandline arguments
$argv[1]


<?
echo "Total argument passed are : $argc \n";
for( $i = 0 ; $i <= $argc -1 ;$i++)
{
echo "Argument $i : $argv[$i] \n";
}
?> 

*/
error_reporting(E_ALL|E_STRICT);


$FILE = realpath('../install.php');

//** Get distribution identifier
//** IMPORTANT!
//   This is the same code as in server/lib/classes/monitor_tools.inc.php
//   So if you change it here, you also have to change it in there!
function get_distname() {
	
	$distname = '';
	$distver = '';
	$distid = '';
	$distbaseid = '';
	
	//** Debian or Ubuntu
	if(file_exists('/etc/debian_version')) {
		if (strstr(trim(file_get_contents('/etc/issue')), 'Ubuntu')) {
			if (strstr(trim(file_get_contents('/etc/issue')), 'LTS')) {
				$lts=" LTS";
			} else {
				$lts="";
			}

			$issue=file_get_contents('/etc/issue');
			$distname = 'Ubuntu';
			$distid = 'debian40';
			$distbaseid = 'debian';
			$ver = explode(' ',$issue);
			$ver = array_filter($ver);
			$ver = next($ver);
			$mainver = explode('.',$ver);
			$mainver = array_filter($mainver);
			$mainver = current($mainver).'.'.next($mainver);
			switch ($mainver){
				case "12.10":
					$relname = "(Quantal Quetzal)";
				break;
				case "12.04":
					$relname = "(Precise Pangolin)";
				break;
				case "11.10":
					$relname = "(Oneiric Ocelot)";
				break;
				case "11.14":
					$relname = "(Natty Narwhal)";
				break;
				case "10.10":
					$relname = "(Maverick Meerkat)";
				break;
				case "10.04":
					$relname = "(Lucid Lynx)";
				break;
				case "9.10":
					$relname = "(Karmic Koala)";
				break;
				case "9.04":
					$relname = "(Jaunty Jackpole)";
				break;
				case "8.10":
				$relname = "(Intrepid Ibex)";
				break;
				case "8.04":
					$relname = "(Hardy Heron)";
				break;
				case "7.10":
					$relname = "(Gutsy Gibbon)";
				break;
				case "7.04":
					$relname = "(Feisty Fawn)";
				break;
				case "6.10":
					$relname = "(Edgy Eft)";
				break;
				case "6.06":
					$relname = "(Dapper Drake)";
				break;
				case "5.10":
					$relname = "(Breezy Badger)";
				break;
				case "5.04":
					$relname = "(Hoary Hedgehog)";
				break;
				case "4.10":
					$relname = "(Warty Warthog)";
				break;
				default:
					$relname = "UNKNOWN";
			}
			$distver = $ver.$lts." ".$relname;
			swriteln("Operating System: ".$distver."\n");		
		} elseif(trim(file_get_contents('/etc/debian_version')) == '4.0') {
			$distname = 'Debian';
			$distver = '4.0';
			$distid = 'debian40';
			$distbaseid = 'debian';
			swriteln("Operating System: Debian 4.0 or compatible\n");
		} elseif(strstr(trim(file_get_contents('/etc/debian_version')),'5.0')) {
			$distname = 'Debian';
			$distver = 'Lenny';
			$distid = 'debian40';
			$distbaseid = 'debian';
			swriteln("Operating System: Debian Lenny or compatible\n");
		} elseif(strstr(trim(file_get_contents('/etc/debian_version')),'6.0') || trim(file_get_contents('/etc/debian_version')) == 'squeeze/sid') {
			$distname = 'Debian';
			$distver = 'Squeeze/Sid';
			$distid = 'debian60';
			$distbaseid = 'debian';
			swriteln("Operating System: Debian 6.0 (Squeeze/Sid) or compatible\n");
		}  else {
			$distname = 'Debian';
			$distver = 'Unknown';
			$distid = 'debian40';
			$distbaseid = 'debian';
			swriteln("Operating System: Debian or compatible, unknown version.\n");
		}
	}
	
	//** OpenSuSE
	elseif(file_exists('/etc/SuSE-release')) {
		if(stristr(file_get_contents('/etc/SuSE-release'),'11.0')) {
			$distname = 'openSUSE';
			$distver = '11.0';
			$distid = 'opensuse110';
			$distbaseid = 'opensuse';
			swriteln("Operating System: openSUSE 11.0 or compatible\n");
		} elseif(stristr(file_get_contents('/etc/SuSE-release'),'11.1')) {
			$distname = 'openSUSE';
			$distver = '11.1';
			$distid = 'opensuse110';
			$distbaseid = 'opensuse';
			swriteln("Operating System: openSUSE 11.1 or compatible\n");
		} elseif(stristr(file_get_contents('/etc/SuSE-release'),'11.2')) {
			$distname = 'openSUSE';
			$distver = '11.2';
			$distid = 'opensuse112';
			$distbaseid = 'opensuse';
			swriteln("Operating System: openSUSE 11.2 or compatible\n");
		}  else {
			$distname = 'openSUSE';
			$distver = 'Unknown';
			$distid = 'opensuse112';
			$distbaseid = 'opensuse';
			swriteln("Operating System: openSUSE or compatible, unknown version.\n");
		}
	}
	
	
	//** Redhat
	elseif(file_exists('/etc/redhat-release')) {
		
		$content = file_get_contents('/etc/redhat-release');
		
		if(stristr($content,'Fedora release 9 (Sulphur)')) {
			$distname = 'Fedora';
			$distver = '9';
			$distid = 'fedora9';
			$distbaseid = 'fedora';
			swriteln("Operating System: Fedora 9 or compatible\n");
		} elseif(stristr($content,'Fedora release 10 (Cambridge)')) {
			$distname = 'Fedora';
			$distver = '10';
			$distid = 'fedora9';
			$distbaseid = 'fedora';
			swriteln("Operating System: Fedora 10 or compatible\n");
		} elseif(stristr($content,'Fedora release 10')) {
			$distname = 'Fedora';
			$distver = '11';
			$distid = 'fedora9';
			$distbaseid = 'fedora';
			swriteln("Operating System: Fedora 11 or compatible\n");
		} elseif(stristr($content,'CentOS release 5.2 (Final)')) {
			$distname = 'CentOS';
			$distver = '5.2';
			$distid = 'centos52';
			$distbaseid = 'fedora';
			swriteln("Operating System: CentOS 5.2 or compatible\n");
		} elseif(stristr($content,'CentOS release 5.3 (Final)')) {
			$distname = 'CentOS';
			$distver = '5.3';
			$distid = 'centos53';
			$distbaseid = 'fedora';
			swriteln("Operating System: CentOS 5.3 or compatible\n");
		} elseif(stristr($content,'CentOS release 5')) {
			$distname = 'CentOS';
			$distver = 'Unknown';
			$distid = 'centos53';
			$distbaseid = 'fedora';
			swriteln("Operating System: CentOS 5 or compatible\n");
		} else {
			$distname = 'Redhat';
			$distver = 'Unknown';
			$distid = 'fedora9';
			$distbaseid = 'fedora';
			swriteln("Operating System: Redhat or compatible, unknown version.\n");
		}
	}
	
	//** Gentoo
 	elseif(file_exists('/etc/gentoo-release')) {
 		
 		$content = file_get_contents('/etc/gentoo-release');
 
        preg_match_all('/([0-9]{1,2})/', $content, $version);
 		$distname = 'Gentoo';
 		$distver = $version[0][0].$version[0][1];
 		$distid = 'gentoo';
 		$distbaseid = 'gentoo';
 		swriteln("Operating System: Gentoo $distver or compatible\n");
		
	} else {
		die('Unrecognized GNU/Linux distribution');
	}
	
	return array('name' => $distname, 'version' => $distver, 'id' => $distid, 'baseid' => $distbaseid);
}

function sread() {
    $input = fgets(STDIN);
    return rtrim($input);
}

function swrite($text = '') {
	echo $text;
}

function swriteln($text = '') {
	echo $text."\n";
}

function ilog($msg){
  	exec("echo `date` \"- [ISPConfig] - \"".$msg.' >> '.ISPC_LOG_FILE);
}

function error($msg){
	ilog($msg);
	die($msg."\n");
}

function caselog($command, $file = '', $line = '', $success = '', $failure = ''){
	exec($command,$arr,$ret_val);
	$arr = NULL;
	if(!empty($file) && !empty($line)){
		$pre = $file.', Line '.$line.': ';
	} else {
		$pre = '';
	}
	if($ret_val != 0){
		if($failure == '') $failure = 'could not '.$command;
		ilog($pre.'WARNING: '.$failure);
	} else {
		if($success == '') $success = $command;
		ilog($pre.$success);
	}
}

function phpcaselog($ret_val, $msg, $file = '', $line = ''){
	if(!empty($file) && !empty($line)){
		$pre = $file.', Line '.$line.': ';
	} else {
		$pre = '';
	}
	if($ret_val == true){
		ilog($pre.$msg);
	} else {
		ilog($pre.'WARNING: could not '.$msg);
	}
	return $ret_val;
}

function mkdirs($strPath, $mode = '0755'){
	if(isset($strPath) && $strPath != ''){
		//* Verzeichnisse rekursiv erzeugen
		if(is_dir($strPath)){
			return true;
		}
		$pStrPath = dirname($strPath);
		if(!mkdirs($pStrPath, $mode)){
			return false;
		}
		$old_umask = umask(0);
		$ret_val = mkdir($strPath, octdec($mode));
		umask($old_umask);
		return $ret_val;
	}
	return false;
}

function rf($file){
	clearstatcache();
	if(is_file($file)) {
		if(!$fp = fopen ($file, 'rb')){
			ilog('WARNING: could not open file '.$file);
		}
		return filesize($file) > 0 ? fread($fp, filesize($file)) : '';
	} else {
		return '';
	}
}

function wf($file, $content){
	mkdirs(dirname($file));
	if(!$fp = fopen ($file, 'wb')){
		ilog('WARNING: could not open file '.$file);
	}
	fwrite($fp, $content);
	fclose($fp);
}

function af($file, $content){
	mkdirs(dirname($file));
	if(!$fp = fopen ($file, 'ab')){
		ilog('WARNING: could not open file '.$file);
	}
	fwrite($fp,$content);
	fclose($fp);
}

function aftsl($file, $content){
	if(!$fp = fopen ($file, 'ab')){
		ilog('WARNING: could not open file '.$file);
	}
	fwrite($fp,$content);
	fclose($fp);
}

function unix_nl($input){
	$output = str_replace("\r\n", "\n", $input);
	$output = str_replace("\r", "\n", $output);
	return $output;
}

function remove_blank_lines($input, $file = 1){
	//TODO ? Leerzeilen l�schen
	if($file){
		$content = unix_nl(rf($input)); // WTF -pedro !
	}else{
		$content = $input;
	}
	$lines = explode("\n", $content);
	if(!empty($lines)){
		foreach($lines as $line){
			if(trim($line) != '') $new_lines[] = $line;
		}
	}
	if(is_array($new_lines)){
		$content = implode("\n", $new_lines);
	} else {
		$content = '';
	}
	if($file){
		wf($input, $content);
	}else{
		return $content;
	}
}

function no_comments($file, $comment = '#'){
	$content = unix_nl(rf($file));
	$lines = explode("\n", $content);
	if(!empty($lines)){
		foreach($lines as $line){
			if(strstr($line, $comment)){
				$pos = strpos($line, $comment);
				if($pos != 0){
					$new_lines[] = substr($line,0,$pos);
				}else{
					$new_lines[] = '';
				}
			}else{
				$new_lines[] = $line;
			}
		}
	}
	if(is_array($new_lines)){
		$content_without_comments = implode("\n", $new_lines);
		$new_lines = NULL;
		return $content_without_comments;
	} else {
		return '';
	}
}

function comment_out($file, $string){
	$inhalt = no_comments($file);
	$gesamt_inhalt = rf($file);
	$modules = explode(',', $string);
	foreach($modules as $val){
		$val = trim($val);
		if(strstr($inhalt, $val)){
			$gesamt_inhalt = str_replace($val, '##ISPConfig INSTALL## '.$val, $gesamt_inhalt);
		}
	}
	wf($file, $gesamt_inhalt);
}

function is_word($string, $text, $params = ''){
  //* params: i ??
  return preg_match("/\b$string\b/$params", $text);
  /*
  if(preg_match("/\b$string\b/$params", $text)) {
    return true;
  } else {
    return false;
  }
  */
}

function grep($content, $string, $params = ''){
  // params: i, v, w
  $content = unix_nl($content);
  $lines = explode("\n", $content);
  foreach($lines as $line){
    if(!strstr($params, 'w')){
      if(strstr($params, 'i')){
        if(strstr($params, 'v')){
          if(!stristr($line, $string)) $find[] = $line;
        } else {
          if(stristr($line, $string)) $find[] = $line;
        }
      } else {
        if(strstr($params, 'v')){
          if(!strstr($line, $string)) $find[] = $line;
        } else {
          if(strstr($line, $string)) $find[] = $line;
        }
      }
    } else {
      if(strstr($params, 'i')){
        if(strstr($params, 'v')){
          if(!is_word($string, $line, 'i')) $find[] = $line;
        } else {
          if(is_word($string, $line, 'i')) $find[] = $line;
        }
      } else {
        if(strstr($params, 'v')){
          if(!is_word($string, $line)) $find[] = $line;
        } else {
          if(is_word($string, $line)) $find[] = $line;
        }
      }
    }
  }
  if(is_array($find)){
    $ret_val = implode("\n", $find);
    if(substr($ret_val,-1) != "\n") $ret_val .= "\n";
    $find = NULL;
    return $ret_val;
  } else {
    return false;
  }
}

function edit_xinetd_conf($service){
	$xinetd_conf = '/etc/xinetd.conf';
	$contents = unix_nl(rf($xinetd_conf));
	$lines = explode("\n", $contents);
	$j = sizeof($lines);
	for($i=0;$i<sizeof($lines);$i++){
		if(grep($lines[$i], $service, 'w')){
			$fundstelle_anfang = $i;
			$j = $i;
			$parts = explode($lines[$i], $contents);
		}
		if($j < sizeof($lines)){
			if(strstr($lines[$i], '}')){
				$fundstelle_ende = $i;
				$j = sizeof($lines);
			}
		}
	}
	if(isset($fundstelle_anfang) && isset($fundstelle_ende)){
		for($i=$fundstelle_anfang;$i<=$fundstelle_ende;$i++){
			if(strstr($lines[$i], 'disable')){
				$disable = explode('=', $lines[$i]);
				$disable[1] = ' yes';
				$lines[$i] = implode('=', $disable);
			}
		}
	}
	$fundstelle_anfang = NULL;
	$fundstelle_ende = NULL;
	$contents = implode("\n", $lines);
	wf($xinetd_conf, $contents);
}

//* Converts a ini string to array
function ini_to_array($ini) {
	$config = '';
	$ini = str_replace("\r\n", "\n", $ini);
	$lines = explode("\n", $ini);
	foreach($lines as $line) {
        $line = trim($line);                
		if($line != '') {
			if(preg_match("/^\[([\w\d_]+)\]$/", $line, $matches)) {
				$section = strtolower($matches[1]);
			} elseif(preg_match("/^([\w\d_]+)=(.*)$/", $line, $matches) && $section != null) {
				$item = trim($matches[1]);
				$config[$section][$item] = trim($matches[2]);
			}
		}
	}
	return $config;
}
	
	
//* Converts a config array to a string
function array_to_ini($config_array = '') {
	if($config_array == '') $config_array = $this->config;
	$content = '';
	foreach($config_array as $section => $data) {
		$content .= "[$section]\n";
		foreach($data as $item => $value) {
			if($item != ''){
                $content .= "$item=$value\n";
            }
		}
		$content .= "\n";
	}
	return $content;
}

function is_user($user){
  global $mod;
  $user_datei = '/etc/passwd';
  $users = no_comments($user_datei);
  $lines = explode("\n", $users);
  if(is_array($lines)){
    foreach($lines as $line){
      if(trim($line) != ''){
        list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode(':', $line);
        if($f1 == $user) return true;
      }
    }
  }
  return false;
}

function is_group($group){
  global $mod;
  $group_datei = '/etc/group';
  $groups = no_comments($group_datei);
  $lines = explode("\n", $groups);
  if(is_array($lines)){
    foreach($lines as $line){
      if(trim($line) != ''){
        list($f1, $f2, $f3, $f4) = explode(':', $line);
        if($f1 == $group) return true;
      }
    }
  }
  return false;
}

function replaceLine($filename,$search_pattern,$new_line,$strict = 0,$append = 1) {
	if($lines = @file($filename)) {
		$out = '';
		$found = 0;
		foreach($lines as $line) {
			if($strict == 0) {
				if(stristr($line,$search_pattern)) {
					$out .= $new_line."\n";
					$found = 1;
				} else {
					$out .= $line;
				}
			} else {
				if(trim($line) == $search_pattern) {
					$out .= $new_line."\n";
					$found = 1;
				} else {
					$out .= $line;
				}
			}
		}
		if($found == 0) {
			//* add \n if the last line does not end with \n or \r
			if(substr($out,-1) != "\n" && substr($out,-1) != "\r") $out .= "\n";
			//* add the new line at the end of the file
			if($append == 1) $out .= $new_line."\n";
		}
		file_put_contents($filename,$out);
	}
}
	
function removeLine($filename,$search_pattern,$strict = 0) {
	if($lines = @file($filename)) {
		$out = '';
		foreach($lines as $line) {
			if($strict == 0) {
				if(!stristr($line,$search_pattern)) {
					$out .= $line;
				}
			} else {
				if(!trim($line) == $search_pattern) {
					$out .= $line;
				}
			}
		}
		file_put_contents($filename,$out);
	}
}

function is_installed($appname) {
	exec('which '.escapeshellcmd($appname).' 2> /dev/null',$out,$returncode);
	if(isset($out[0]) && stristr($out[0],$appname) && $returncode == 0) {
		return true;
	} else {
		return false;
	}
}

/*
 * Compare ISPConfig version number.
 * return values:
 * -1 $current version is newer then $new version (downgrade)
 * 0 $current version = $new version
 * 1 $current version is older then new version (update)

*/
function compare_ispconfig_version($current,$new) {
	if( $current == $new) {
		return 0;
	}
	
	$p = explode('.',$current);
	$tmp = '';
	$tmp .= str_pad(intval($p[0]), 3, '0', STR_PAD_LEFT);
	$tmp .= (isset($p[1]))?str_pad(intval($p[1]), 3, '0', STR_PAD_LEFT):'000';
	$tmp .= (isset($p[2]))?str_pad(intval($p[2]), 3, '0', STR_PAD_LEFT):'000';
	$tmp .= (isset($p[3]))?str_pad(intval($p[3]), 3, '0', STR_PAD_LEFT):'000';
	$current = $tmp;
	
	$p = explode('.',$new);
	$tmp = '';
	$tmp .= str_pad(intval($p[0]), 3, '0', STR_PAD_LEFT);
	$tmp .= (isset($p[1]))?str_pad(intval($p[1]), 3, '0', STR_PAD_LEFT):'000';
	$tmp .= (isset($p[2]))?str_pad(intval($p[2]), 3, '0', STR_PAD_LEFT):'000';
	$tmp .= (isset($p[3]))?str_pad(intval($p[3]), 3, '0', STR_PAD_LEFT):'000';
	$new = $tmp;
	
	if($new > $current) {
		return 1;
	} else {
		return -1;
	}
	
}

/*
* Get the port number of the ISPConfig controlpanel vhost
*/

function get_ispconfig_port_number() {
	global $conf;
	if($conf['nginx']['installed'] == true){
		$ispconfig_vhost_file = $conf['nginx']['vhost_conf_dir'].'/ispconfig.vhost';
		$regex = '/listen (\d+)/';
	} else {
		$ispconfig_vhost_file = $conf['apache']['vhost_conf_dir'].'/ispconfig.vhost';
		$regex = '/\<VirtualHost.*\:(\d{1,})\>/';
	}
	
	if(is_file($ispconfig_vhost_file)) {
		$tmp = file_get_contents($ispconfig_vhost_file);
		preg_match($regex,$tmp,$matches);
		$port_number = intval($matches[1]);
		if($port_number > 0) {
			return $port_number;
		} else {
			return '8080';
		}
	}
}

/*
* Get the port number of the ISPConfig controlpanel vhost
*/

function is_ispconfig_ssl_enabled() {
	global $conf;
	$ispconfig_vhost_file = $conf['apache']['vhost_conf_dir'].'/ispconfig.vhost';

	if(is_file($ispconfig_vhost_file)) {
		$tmp = file_get_contents($ispconfig_vhost_file);
		if(stristr($tmp,'SSLCertificateFile')) {
			return true;
		} else {
			return false;
		}
	}
}



?>
