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
if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
	header("Location: ../index.php");
	exit;
}

// Lade Template
$app->uses('tpl,cmstree');
$app->tpl->newTemplate("templates/treenavi.htm");

$kat = $_REQUEST["kat"];

if($kat > 0) {
	// nur eine Kategorie ist offen
	// bestimme Level
	/*
	$parent = '';
	$level = -1;
	$kat_path = '';
	$temp_kat = $kat;
	while( $level < 20 and $temp_kat != 'root') {
		$tmp_node = $db->queryOneRecord("SELECT parent,name,media_cat_id FROM media_cat WHERE media_cat_id = $temp_kat");
		$temp_kat = $tmp_node["parent"];
		$kat_path = $tmp_node["kategorie"]." > ".$kat_path;
		$level++;
	}
	
	if($level < 2) unset($_SESSION["kat_open"]);
	$_SESSION["kat_open"][$kat] = 1;
	
	$tpl->setVar("kategorie_pfad",substr($kat_path,0,-2));
	*/
	
	// oder Kategorien lassen sich öffnen und schliessen
	
	if($_SESSION["s"]["cat_open"][$kat] == 1) {
		unset($_SESSION["s"]["cat_open"][$kat]);
	} else {
		$_SESSION["s"]["cat_open"][$kat] = 1;
	}
	
}


// $tree = new tree;
$media_cat = $app->cmstree->node_list();
$app->tpl->setLoop("media_cat",$media_cat);


// Template parsen
$app->tpl->pparse();

?>