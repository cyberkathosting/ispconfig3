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

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/media.list.php";

/******************************************
* End Form configuration
******************************************/

// Checke Berechtigungen für Modul
if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
	header("Location: ../index.php");
	exit;
}

$app->uses('tpl,listform');

// Listen Definition laden
$app->listform->loadListDef($list_def_file);

if(!is_file('templates/'.$app->listform->listDef["name"].'_list.htm')) {
	$app->uses('listform_tpl_generator');
	$app->listform_tpl_generator->buildHTML($app->listform->listDef);
}

$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/'.$app->listform->listDef["name"].'_list.htm');

// SQL für Suche generieren
if($app->listform->listDef["name"] != 'no') {
	if($_SESSION["s"]["user"]["typ"] == "admin") {
		$sql_where = "";
	} else {
		$sql_where = "userid = ".$_SESSION["s"]["user"]["userid"]." and";
	}
}

$sql_where = $app->listform->getSearchSQL($sql_where);
$app->tpl->setVar($app->listform->searchValues);

// SQL für Paging generieren
$limit_sql = $app->listform->getPagingSQL($sql_where);
$app->tpl->setVar("paging",$app->listform->pagingHTML);

// hole alle Datensätze
$records = $app->db->queryAllRecords("SELECT * FROM ".$app->listform->listDef["table"]." WHERE $sql_where $limit_sql");

$bgcolor = "#FFFFFF";

if(is_array($records)) {
	$idx_key = $app->listform->listDef["table_idx"]; 
	foreach($records as $rec) {
	
		$rec = $app->listform->decode($rec);

		// Farbwechsel
		$bgcolor = ($bgcolor == "#FFFFFF")?"#EEEEEE":"#FFFFFF";
		$rec["bgcolor"] = $bgcolor;
		$rec["id"] = $rec[$idx_key];

		$records_new[] = $rec;
	}
}

$app->tpl->setLoop('records',$records_new);

// Language File setzen
$lng_file = "lib/lang/".$_SESSION["s"]["language"]."_".$app->listform->listDef['name']."_list.lng";
include($lng_file);
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();



?>