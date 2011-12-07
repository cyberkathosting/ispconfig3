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

/**
* Formularbehandlung
*
* Funktionen zur Umwandlung von Formulardaten
* sowie zum vorbereiten von HTML und SQL
* Ausgaben
*
*	Tabellendefinition
*	
*	Datentypen:
*	- INTEGER (Wandelt Ausdrücke in Int um)
*	- DOUBLE
*	- CURRENCY (Formatiert Zahlen nach Währungsnotation)
*	- VARCHAR (kein weiterer Format Check)
*	- DATE (Datumsformat, Timestamp Umwandlung)
*	
*	Formtype:
*	- TEXT (normales Textfeld)
*	- PASSWORD (Feldinhalt wird nicht angezeigt)
*	- SELECT (Gibt Werte als option Feld aus)
*	- MULTIPLE (Select-Feld mit nehreren Werten)
*	
*	VALUE:
*	- Wert oder Array
*	
*	SEPARATOR
*	- Trennzeichen für multiple Felder
*
*	Hinweis:
*	Das ID-Feld ist nicht bei den Table Values einzufügen.
*
* @package form
* @author Till Brehm
* @version 1.1
*/

class form {
	
	/**
	* Definition der Tabelle (array)
	* @var tableDef
	*/
	var $tableDef;
	
	/**
	* Private
	* @var action
	*/
	var $action;
	
	/**
	* Tabellenname (String)
	* @var table_name
	*/
	var $table_name;
	
	/**
	* Debug Variable
	* @var debug
	*/
	var $debug = 0;
	
	/**
	* name des primary Field der Tabelle (string)
	* @var table_index
	*/
	var $table_index;
	
	/**
	* enthält die Fehlermeldung bei Überprüfung
	* der Variablen mit Regex
	* @var errorMessage
	*/
	var $errorMessage;
	
	var $dateformat = "d.m.Y";
    var $formDef;
	
	/**
	* Laden der Tabellendefinition
	*
	* @param file: Pfad zur Tabellendefinition
	* @return true
	*/
	function loadTableDef($file) {
		global $app,$conf;
		
		include_once($file);
		$this->tableDef = $table;
		$this->table_name = $table_name;
		$this->table_index = $table_index;
		return true;
	}
    
    function loadFormDef($file) {
		global $app,$conf;
		
		include_once($file);
		$this->formDef = $form;
		return true;
	}
	
	
	/**
	* Konvertiert die Daten des übergebenen assoziativen
	* Arrays in "menschenlesbare" Form.
	* Datentyp Konvertierung, z.B. für Ausgabe in Listen.
	*
	* @param record
	* @return record
	*/
	function decode($record) {
		if(is_array($record)) {
			foreach($record as $key => $val) {
				switch ($this->tableDef[$key]['datatype']) {
				case 'VARCHAR':
					$new_record[$key] = stripslashes($val);
				break;
				
				case 'DATE':
					if($val > 0) {
						$new_record[$key] = date($this->dateformat,$val);
					}
				break;
				
				case 'INTEGER':
					$new_record[$key] = intval($val);
				break;
				
				case 'DOUBLE':
					$new_record[$key] = $val;
				break;
				
				case 'CURRENCY':
					$new_record[$key] = number_format($val, 2, ',', '');
				break;
				
				default:
					$new_record[$key] = stripslashes($val);
				}
			}
			
		}
	return $new_record;
	}
	
	/**
	* Record für Ausgabe in Formularen vorbereiten.
	*
	* @param record = Datensatz als Array
	* @param action = NEW oder EDIT 
	* @return record
	*/
	function getHTML($record,$action = 'NEW') {
		
		global $app;
		
		if(!is_array($this->tableDef)) $app->error("Keine Tabellendefinition vorhanden.");
		
		$new_record = array();
		if($action == 'EDIT') {
			$record = $this->decode($record);
			if(is_array($record)) {
				foreach($record as $key => $val) {
					switch ($this->tableDef[$key]['formtype']) {
					case 'SELECT':
						if(is_array($this->tableDef[$key]['value'])) {
							$out = '';
							foreach($this->tableDef[$key]['value'] as $k => $v) {
								$selected = ($k == $val)?' SELECTED':'';
								$out .= "<option value='$k'$selected>$v</option>\r\n";
							}
						}
						$new_record[$key] = $out;
					break;
					case 'MULTIPLE':
						if(is_array($this->tableDef[$key]['value'])) {
							
							// aufsplitten ergebnisse
							$vals = explode($this->tableDef[$key]['separator'],$val);
							
							// HTML schreiben
							$out = '';
							foreach($this->tableDef[$key]['value'] as $k => $v) {
								
								$selected = '';
								foreach($vals as $tvl) {
									if(trim($tvl) == trim($k)) $selected = ' SELECTED';
								}
								
								$out .= "<option value='$k'$selected>$v</option>\r\n";
							}
						}
						$new_record[$key] = $out;
					break;
					
					case 'PASSWORD':
						$new_record[$key] = '';
					break;
					
					default:
						$new_record[$key] = htmlspecialchars($val);
					}
				}
			}
		} else {
			foreach($this->tableDef as $key => $val) {
				switch ($this->tableDef[$key]['formtype']) {
				case 'SELECT':
					if(is_array($this->tableDef[$key]['value'])) {
						$out = '';
						foreach($this->tableDef[$key]['value'] as $k => $v) {
							$selected = ($k == $val)?' SELECTED':'';
							$out .= "<option value='$k'$selected>$v</option>\r\n";
						}
					}
					$new_record[$key] = $out;
				break;
				case 'MULTIPLE':
						if(is_array($this->tableDef[$key]['value'])) {
							
							// aufsplitten ergebnisse
							$vals = explode($this->tableDef[$key]['separator'],$val);
							
							// HTML schreiben
							$out = '';
							foreach($this->tableDef[$key]['value'] as $k => $v) {
								
								$out .= "<option value='$k'>$v</option>\r\n";
							}
						}
						$new_record[$key] = $out;
					break;
				
				case 'PASSWORD':
					$new_record[$key] = '';
				break;
				
				default:
					$new_record[$key] = htmlspecialchars($this->tableDef[$key]['value']);
				}
			}
		
		}
		
		if($this->debug == 1) $this->dbg($new_record);
		
		return $new_record;
	}
	
	/**
	* Record in "maschinen lesbares" Format überführen
	* und Werte gegen reguläre Ausdrücke prüfen.
	*
	* @param record = Datensatz als Array
	* @return record
	*/
	function encode($record) {
		global $app;
		$this->errorMessage = '';
		
		if(is_array($record)) {
			foreach($record as $key => $val) {
				switch ($this->tableDef[$key]['datatype']) {
				case 'VARCHAR':
					if(!is_array($val)) {
						$new_record[$key] = $app->db->quote($val);
					} else {
						$new_record[$key] = implode($this->tableDef[$key]['separator'],$val);
					}
				break;
				case 'DATE':
					if($val > 0) {
						list($tag,$monat,$jahr) = explode('.',$val);
						$new_record[$key] = mktime(0,0,0,$monat,$tag,$jahr);
					}
				break;
				case 'INTEGER':
					$new_record[$key] = intval($val);
				break;
				case 'DOUBLE':
					$new_record[$key] = $app->db->quote($val);
				break;
				case 'CURRENCY':
					$new_record[$key] = str_replace(",",".",$val);
				break;
				}
				
				if($this->tableDef[$key]['regex'] != '') {
					// Enable that "." matches also newlines
					$this->tableDef[$key]['regex'] .= 's';
					if(!preg_match($this->tableDef[$key]['regex'], $val)) {
						$this->errorMessage .= $this->tableDef[$key]['errmsg']."<br>\r\n";
					}
				}
			}
			
		}
		return $new_record;
	}
	
	/**
	* SQL Statement für Record erzeugen.
	*
	* @param record = Datensatz als Array
	* @param action = INSERT oder UPDATE
	* @param primary_id
	* @return record
	*/
	function getSQL($record, $action = 'INSERT', $primary_id = 0, $sql_ext_where = '') {
		
		global $app;
		
		$record = $this->encode($record);
		$sql_insert_key = '';
		$sql_insert_val = '';
		$sql_update = '';
		
		if(!is_array($this->tableDef)) $app->error("Keine Tabellendefinition vorhanden.");
		
		// gehe durch alle Felder des Records
		if(is_array($record)) {
        foreach($record as $key => $val) {
			// Wenn es kein leeres Passwortfeld ist
			if (!($this->tableDef[$key]['formtype'] == 'PASSWORD' and $val == '')) {
				// gehe durch alle Felder der TableDef
				foreach($this->tableDef as $tk => $tv) {
					// Wenn Feld in TableDef enthalten ist
					if($tk == $key) {
						// Erzeuge Insert oder Update Quelltext
						if($action == "INSERT") {
							
							if($this->tableDef[$key]['formtype'] == 'PASSWORD') {
								$sql_insert_key .= "`$key`, ";
								$sql_insert_val .= "md5('$val'), ";
							//} elseif($this->tableDef[$key]['formtype'] == 'MULTIPLE') {
							//	$val = implode($this->tableDef[$key]['separator'],$val);
							//	$sql_insert_key .= "`$key`, ";
							//	$sql_insert_val .= "'$val', ";
							} else {
								$sql_insert_key .= "`$key`, ";
								$sql_insert_val .= "'$val', ";
							}
							
						} else {
							
							if($this->tableDef[$key]['formtype'] == 'PASSWORD') {
								$sql_update .= "`$key` = md5('$val'), ";
							//} elseif($this->tableDef[$key]['formtype'] == 'MULTIPLE') {
							//	$val = implode($this->tableDef[$key]['separator'],$val);
							//	$sql_update .= "`$key` = '$val', ";
							} else {
								$sql_update .= "`$key` = '$val', ";
							}
							
						}
					}
				}
			}
		}
        }
		
		// Füge Backticks nur bei unvollständigen Tabellennamen ein
		if(stristr($this->table_name,'.')) {
			$escape = '';
		} else {
			$escape = '`';
		}
		
		
		if($action == "INSERT") {
			$sql_insert_key = substr($sql_insert_key,0,-2);
			$sql_insert_val = substr($sql_insert_val,0,-2);
			$sql = "INSERT INTO ".$escape.$this->table_name.$escape." ($sql_insert_key) VALUES ($sql_insert_val)";
		} else {
			if($primary_id != 0) {
				$sql_update = substr($sql_update,0,-2);
				$sql = "UPDATE ".$escape.$this->table_name.$escape." SET ".$sql_update." WHERE ".$this->table_index ." = ".$primary_id;
				if($sql_ext_where != '') $sql .= " and ".$sql_ext_where;
			} else {
				$app->error("Primary ID fehlt!");
			}
		}
		
		return $sql;
	}
	
	/**
	* Debugging arrays.
	*
	* @param array_data
	*/
	function dbg($array_data) {
		
		echo "<pre>";
		print_r($array_data);
		echo "</pre>";
		
	}
    
    
    function showForm() {
    	global $app,$conf;
        
        if(!is_array($this->formDef)) die("Form Definition wurde nicht geladen.");
        
        if($this->errorMessage == '') {
        	// wenn kein Fehler vorliegt
			if($_REQUEST["next_tab"] != '') {
            	// wenn nächster Tab bekannt
            	$active_tab = $_REQUEST["next_tab"];
            } else {
            	// ansonsten ersten tab nehmen
            	$active_tab = $this->formDef["tabs"][0]["name"];
            }
		} else {
        	// bei Fehlern den gleichen Tab nochmal anzeigen
            $active_tab = $_SESSION["s"]["form"]["tab"];
		}
        
        // definiere Tabs
        foreach( $this->formDef["tabs"] as $tab) {
            
            if($tab["name"] == $active_tab) {
            	$app->tpl->setInclude('content_tpl',$tab["template"]);
                $tab["active"] = 1;
                $_SESSION["s"]["form"]["tab"] = $tab["name"];
            } else {
            	$tab["active"] = 0;
            }
			
            $frmTab[] = $tab;
        }
        
        // setze Loop
        $app->tpl->setLoop("formTab", $frmTab);

		// Formular action setzen
		$app->tpl->setVar('form_action',$this->formDef["action"]);
    }
	
	
}

?>
