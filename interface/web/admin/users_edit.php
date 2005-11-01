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

$tform_def_file = "form/users.tform.php";

/******************************************
* End Form configuration
******************************************/

// Checke Berechtigungen für Modul
if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
	header("Location: ../index.php");
	exit;
}

// Lade Template
$app->uses('tpl,tform');
$app->tpl->newTemplate("tabbed_form.tpl.htm");

// Tabellendefinition und Formdefinition laden
$app->tform->loadFormDef($tform_def_file);

// ID importieren
$id = intval($_REQUEST["id"]);

if(count($_POST) > 1) {

	// Bestimme aktion
	if($id > 0) {
		$action = 'UPDATE';
	} else {
		$action = 'INSERT';
	}

	$sql = $app->tform->getSQL($_POST,$_SESSION["s"]["form"]["tab"],$action,$id,$ext_where);
	if($app->tform->errorMessage == '') {
		$app->db->query($sql);
		if($action == "INSERT") $id = $app->db->insertID();
			
		// Liste anzeigen, wenn speichern geklickt wurde
    	if($_REQUEST["next_tab"] == '') {
    		header("Location: ".$app->tform->formDef['list_default']);
        	exit;
    	}
			
	} else {
		$app->tpl->setVar("error","<b>Fehler:</b><br>".$app->tform->errorMessage);
		$app->tpl->setVar($_POST);
	}
}

// Welcher Tab wird angezeigt
if($app->tform->errorMessage == '') {
    // wenn kein Fehler vorliegt
	if($_REQUEST["next_tab"] != '') {
		// wenn nächster Tab bekannt
		$active_tab = $_REQUEST["next_tab"];
    } else {
        // ansonsten ersten tab nehmen
        $active_tab = $app->tform->formDef['tab_default'];
    }
} else {
    // bei Fehlern den gleichen Tab nochmal anzeigen
    $active_tab = $_SESSION["s"]["form"]["tab"];
}


if($id > 0) {
	// bestehenden Datensatz anzeigen
	if($app->tform->errorMessage == '') {
		if($app->tform->formDef['auth'] == 'no') {
			$sql = "SELECT * FROM ".$app->tform->formDef['db_table']." WHERE ".$app->tform->formDef['db_table_idx']." = $id";
		} else {
			$sql = "SELECT * FROM ".$app->tform->formDef['db_table']." WHERE ".$app->tform->formDef['db_table_idx']." = $id AND ".$app->tform->getAuthSQL('u');
		}
		if(!$record = $app->db->queryOneRecord($sql)) die("You dont have the permission to view this record or this record does not exist.");
	} else {
		$record = $app->tform->encode($_POST,$active_tab);
	}
	
    // Userdaten umwandeln
	$record = $app->tform->getHTML($record, $active_tab,'EDIT');
	$record['id'] = $id;
} else {
	if($app->tform->errorMessage == '') {
		$record = array();
		$record = $app->tform->getHTML($record, $app->tform->formDef['tab_default'],'NEW');
	} else {
		$record = $app->tform->getHTML($app->tform->encode($_POST,$active_tab),$active_tab,'EDIT');
	}
}

$app->tpl->setVar($record);

// Formular und Tabs erzeugen
$app->tform->showForm();

// Defaultwerte setzen
$app->tpl_defaults();

// Template parsen
$app->tpl->pparse();

?>