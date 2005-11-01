<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

// Checke Berechtigungen für Modul
//if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
//	header("Location: ../index.php");
//	exit;
//}

// TODO: Permission Check in Media manager

// getting variables
$filenum = intval($_REQUEST["filenum"]);
$media_profile_id = intval($_REQUEST["media_profile_id"]);
$media_cat_id = intval($_REQUEST["media_cat_id"]);
if($filenum < 1 or $filenum > 20) $filenum = 1;

$message = '';

if(count($_FILES['files']['tmp_name']) > 0) {
	
	if($media_profile_id == 0) {
		// Without media profile
		$uploaddir = $_REQUEST["path"];
		if(substr($uploaddir,-1) != '/') $uploaddir .= "/";
		
		// gespeicherte Pfade sind immer unix slashes, auch unter win32
		$uploaddir = str_replace($conf["fs_div"],"/",$uploaddir);

		for($n = 0; $n < count($_FILES['files']['tmp_name']); $n++) {
			$uploadfile = $uploaddir. $_FILES['files']['name'][$n];
			$media_type = addslashes($_FILES["files"]["type"][$n]);
			$media_name = addslashes($_FILES["files"]["name"][$n]);
			if (@move_uploaded_file($_FILES['files']['tmp_name'][$n], $uploadfile)) { 
				// Insert record in media DB
				$app->db->query("INSERT INTO media (media_profile_id,media_cat_id,media_name,media_type,path0) VALUES ($media_profile_id, $media_cat_id,'$media_name','$media_type','$uploadfile')");
				$media_id = $app->db->insertID();
				$message .= "Uploaded file: $uploadfile<br />";
			} else { 
 				$message .= "Error uploading file: ".$_FILES['files']['name'][$n]."<br />";
			}
		}
	} else {
		// With mediaprofile
		$profile = $app->db->queryOneRecord("SELECT * FROM media_profile WHERE media_profile_id = ".$media_profile_id);
		// first upload the files to temp directory
		$uploaddir = $conf["temppath"].$conf["fs_div"];
		for($n = 0; $n < count($_FILES['files']['tmp_name']); $n++) {
			$path_parts = pathinfo($_FILES['files']['name'][$n]); 
			$tmp_filename = md5(uniqid(rand(), true)).".".$path_parts["extension"];
			$uploadfile = $uploaddir. $tmp_filename;
			$media_type = addslashes($_FILES["files"]["type"][$n]);
			$media_name = addslashes($_FILES["files"]["name"][$n]);
			list($width, $height) = getimagesize($_FILES['files']['tmp_name'][$n]);
			$media_size = $width."x".$height;
			
			if (@move_uploaded_file($_FILES['files']['tmp_name'][$n], $uploadfile)) { 
				// insert Data into media DB
				$app->db->query("INSERT INTO media (media_profile_id,media_cat_id,media_name,media_type,media_size) VALUES ($media_profile_id, $media_cat_id,'$media_name','$media_type','$media_size')");
				$media_id = $app->db->insertID();
				
				// Store original file
				if($profile["original"] == 1) {
					$path = $profile["path0"];
					$path = str_replace("[ID]",$media_id,$path);
					$path = str_replace("[EXT]",$path_parts["extension"],$path);
					$path = str_replace("[NAME]",$_FILES["files"]["name"][$n],$path);
					$path = str_replace("[ROOT]",$conf["rootpath"],$path);
					//$path0 = escapeshellcmd($path);
					$path0 = str_replace("/",$conf["fs_div"],$path);
					// $path0 = "../media/original/file_".$media_id.".".$path_parts["extension"];
					// $path0 = escapeshellcmd($path0);
					@copy($uploadfile,$path0);
				}
				
				// Make Thumbnail
				if($profile["thumbnail"] == 1) {
					$tmp_command = $conf["programs"]["convert"]." $uploadfile -resize 100x120 -sharpen 2 ..".$conf["fs_div"]."media".$conf["fs_div"]."thumbnails".$conf["fs_div"]."thumb_".$media_id.".png";
					exec($tmp_command);
				}
				
				for($p = 1; $p <= 5; $p++) {
					if($profile["path".$p] != '') {
						// parse variables in path
						$path = $profile["path".$p];
						$path = str_replace("[ID]",$media_id,$path);
						$path = str_replace("[EXT]",$path_parts["extension"],$path);
						$path = str_replace("[NAME]",$_FILES["files"]["name"][$n],$path);
						$path = str_replace("[ROOT]",$conf["rootpath"],$path);
						//$path = escapeshellcmd($path);
						
						// set a variable like path1 path2 etc.
						$tmp = "path".$p;
						$$tmp = $path;
						
						// In case we are under win32, replace linux slashes with win32 slashes
						$path = str_replace("/",$conf["fs_div"],$path0);
					
						if($profile["resize".$p] == '' and $profile["options".$p] == '') {
							copy($uploadfile,$path);
						} else {
							$tmp_command = $conf["programs"]["convert"]." ";
							if($profile["resize".$p] != '') $tmp_command .= "-resize ".$profile["resize".$p]." ";
							if($profile["options".$p] != '') $tmp_command .= $profile["options".$p]." ";
							$tmp_command .= $uploadfile . " ".$path;
							exec($tmp_command);
						}
					}
				}
				
				// remove root-path replace win32 slashes with linux slashes
				$path0 = str_replace($conf["rootpath"],'',$path0);
				$path0 = str_replace("\\","/",$path0);
				
				$path1 = str_replace($conf["rootpath"],'',$path1);
				$path1 = str_replace("\\","/",$path1);
				
				$path2 = str_replace($conf["rootpath"],'',$path2);
				$path2 = str_replace("\\","/",$path2);
				
				$path3 = str_replace($conf["rootpath"],'',$path3);
				$path3 = str_replace("\\","/",$path3);
				
				$path4 = str_replace($conf["rootpath"],'',$path4);
				$path4 = str_replace("\\","/",$path4);
				
				$path5 = str_replace($conf["rootpath"],'',$path5);
				$path5 = str_replace("\\","/",$path5);
				
				
				// Update media record in database
				$app->db->query("UPDATE media SET thumbnail = '".$profile["thumbnail"]."', path0 = '$path0', path1 = '$path1', path2 = '$path2', path3 = '$path3', path4 = '$path4', path5 = '$path5' WHERE media_id = $media_id");
				
				$message .= "Uploaded file: $uploadfile<br />";
				unlink($uploadfile);
			} else { 
 				$message .= "Error uploading file: ".$_FILES['files']['name'][$n]."<br />";
				die($message);
			}
			
			
		}
	}
	/*
	if($_REQUEST["table"] != '' and $_REQUEST["field"] != '' and $media_id > 0) {
		$table = $app->db->quote($_REQUEST["table"]);
		$field = $app->db->quote($_REQUEST["field"]);
		$idx_field = $app->db->quote($_REQUEST["idx_field"]);
		$idx_val = $app->db->quote($_REQUEST["idx_val"]);
		$app->db->query("UPDATE $table SET $field = '$media_id' WHERE $idx_field = $idx_val");
	}
	*/
	
	
	if($_REQUEST["action"] != '' and $media_id > 0) {
		if($_REQUEST["action"] == 'closewin_submit') {
			$field = $_REQUEST["field"];
			echo 
'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>empty</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="JavaScript" type="text/JavaScript">
	function action() {
    	var position = null;
		if (document.all) position = opener.document.myform.'.$field.'.length;
  		var Eintrag = opener.document.createElement("option");
  		Eintrag.text = '."'$media_name'".';
  		Eintrag.value = '."'$media_id'".';
  		position = opener.document.myform.'.$field.'.add(Eintrag, position);
		opener.document.myform.'.$field.".value = '$media_id'".';
		window.close();
	}
</script>
</head>

<body onLoad="action();">

</body>
</html>';
			exit;
		}
	}


}

// Lade Template
$app->uses('tpl,tree');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/media_add.htm');



$vars["filenum"] = $filenum;

// read in media profiles
$tmp_records = $app->db->queryAllRecords("SELECT media_profile_id, profile_name, media_cat_id FROM media_profile ORDER BY profile_name");
$vars["media_profile"] = "";
$vars["media_profile"] .= "<option value='0'>Kein Profil</option>\r\n";
foreach($tmp_records as $tmp) {
	if($tmp["media_profile_id"] == $media_profile_id) {
		$vars["media_profile"] .= "<option value='".$tmp["media_profile_id"]."' SELECTED>".$tmp["profile_name"]."</option>\r\n";
		if($media_cat_id == 0) $media_cat_id = $tmp["media_cat_id"];
	} else {
		$vars["media_profile"] .= "<option value='".$tmp["media_profile_id"]."'>".$tmp["profile_name"]."</option>\r\n";
	}
}
unset($tmp_records);

// read media categories
$parents = $app->db->queryAllRecords("SELECT * FROM media_cat ORDER BY name");
$app->tree->loadFromArray($parents);
$parents = $app->tree->optionlist();

$vars["media_cat"] = "";
$vars["media_cat"] .= "<option value='0'>Medienkatalog</option>\r\n";
if(is_array($parents)) {
	foreach($parents as $tmp) {
		if($tmp["id"] == $media_cat_id) {
			$vars["media_cat"] .= "<option value='".$tmp["id"]."' SELECTED>".$tmp["data"]."</option>\r\n";
		} else {
			$vars["media_cat"] .= "<option value='".$tmp["id"]."'>".$tmp["data"]."</option>\r\n";
		}
	}
}

$vars["show_path"] = ($media_profile_id == 0)?1:0;


$records = array();
for($n = 1; $n <= $filenum; $n++) {
	$records[]["n"] = $n;
}
$app->tpl->setLoop('file',$records);

if($_REQUEST["action"] != '') {
	$vars["cancel_action"] = "window.close();";
} else {
	$vars["cancel_action"] = "self.location.href='index.php';";
}

$app->tpl->setVar($_GET);
$app->tpl->setVar($vars);


// Defaultwerte setzen
$app->tpl_defaults();

// Template parsen
$app->tpl->pparse();

?>