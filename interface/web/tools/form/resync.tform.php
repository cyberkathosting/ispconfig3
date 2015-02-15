<?php
$form["title"]    = "Resync Tool";
$form["description"]  = "";
$form["name"]    = "resync";
$form["action"]   = "resync.php";
$form["db_history"]  = "no";
$form["tab_default"] = "resync";
$form["list_default"] = "resync.php";
$form["auth"]   = 'yes'; 

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['resync'] = array (
	'title'  => "Resync",
	'width'  => 100,
	'template'  => "templates/resync.htm",
);


?>
