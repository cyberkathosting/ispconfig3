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


class tform_tpl_generator {
	
	function buildHTML($formDef,$tab) {
		
		global $app;
		
		$module = $_SESSION["s"]["module"]["name"];
		
		$html = '<table width="500" border="0" cellspacing="0" cellpadding="2">';
		$lang = array();
		
		foreach($formDef['tabs'][$tab]['fields'] as $key => $field) {
			switch ($field['formtype']) {
				case 'TEXT':
					$html .= "
  <tr>
    <td class=\"frmText11\">{tmpl_var name='".$key."_txt'}:</td>
    <td class=\"frmText11\"><input name=\"".$key."\" type=\"text\" class=\"text\" value=\"{tmpl_var name='".$key."'}\" size=\"".$field['width']."\" maxlength=\"".$field['maxlength']."\"></td>
  </tr>";
				break;
				case 'TEXTAREA':
					$html .= "
  <tr>
    <td class=\"frmText11\">{tmpl_var name='".$key."_txt'}:</td>
    <td class=\"frmText11\"><textarea name='$key' cols='".$field['cols']."' rows='".$field['rows']."'>{tmpl_var name='".$key."'}</textarea></td>
  </tr>";
				break;
				case 'SELECT':
				$html .= "
  <tr>
    <td class=\"frmText11\">{tmpl_var name='".$key."_txt'}:</td>
    <td class=\"frmText11\">
		<select name=\"".$key."\" class=\"text\">
			{tmpl_var name='".$key."'}
		</select>
	</td>
  </tr>";
				break;
				case 'MULTIPLE':
				$html .= "
  <tr>
    <td class=\"frmText11\">{tmpl_var name='".$key."_txt'}:</td>
    <td class=\"frmText11\">
		<select name=\"".$key."\" class=\"text\" size=\"".$field['rows']."\" multiple>
			{tmpl_var name='".$key."'}
		</select>
	</td>
  </tr>";
				break;
				case 'PASSWORD':
				$html .= "
  <tr>
    <td class=\"frmText11\">{tmpl_var name='".$key."_txt'}:</td>
    <td class=\"frmText11\"><input name=\"".$key."\" type=\"password\" class=\"text\" value=\"{tmpl_var name='".$key."'}\" size=\"".$field['width']."\" maxlength=\"".$field['maxlength']."\"></td>
  </tr>";
				break;
				case 'CHECKBOX':
				$html .= "
  <tr>
    <td class=\"frmText11\">{tmpl_var name='".$key."_txt'}:</td>
    <td class=\"frmText11\">{tmpl_var name='".$key."'}</td>
  </tr>";
				break;
				case 'CHECKBOXARRAY':
				$html .= "
  <tr>
    <td class=\"frmText11\">{tmpl_var name='".$key."_txt'}:</td>
    <td class=\"frmText11\">{tmpl_var name='".$key."'}</td>
  </tr>";
				break;
				case 'RADIO':
				$html .= "
  <tr>
    <td class=\"frmText11\">{tmpl_var name='".$key."_txt'}:</td>
    <td class=\"frmText11\">{tmpl_var name='".$key."'}</td>
  </tr>";
				break;
			}
			
			// Language File Eintrag für "Feld-Titel" anlegen
			$lang[$key."_txt"] = $key;
			
			// language File Eintrag, für error-Text anlegen
			if(isset($field["errmsg"]) && $field["errmsg"] != '') {
				$errmsg = $field["errmsg"];
				$lang[$errmsg] = $errmsg;
			}
			
			
		}
		
		$html .= "  <tr>
    <td class=\"frmText11\">&nbsp;</td>
    <td class=\"frmText11\">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><input name=\"btn_save\" type=\"button\" class=\"button\" value=\"{tmpl_var name='btn_save_txt'}\" onClick=\"submitForm('pageForm','".$module."/".$formDef["action"]."');\"><div class=\"buttonEnding\"></div>&nbsp;
      <input name=\"btn_cancel\" type=\"button\" class=\"button\" value=\"{tmpl_var name='btn_cancel_txt'}\" onClick=\"loadContent('".$module."/".$formDef["list_default"]."');\"><div class=\"buttonEnding\"></div>
    </td>
  </tr>";
		
		$lang['btn_save_txt'] = "Save";
		$lang['btn_cancel_txt'] = "Cancel";
		
		$html .= "\r\n</table>\r\n<input type=\"hidden\" name=\"id\" value=\"{tmpl_var name='id'}\">";
		
		// speichere Template
		if (!$handle = fopen($formDef['tabs'][$tab]['template'], 'w')) { 
        	print "Cannot open file ($filename)"; 
        	exit; 
   		} 
 
   		if (!fwrite($handle, $html)) { 
			print "Cannot write to file ($filename)"; 
			exit; 
		}
		fclose($handle);
		
		// speichere language Datei
		$this->lng_add($lang,$formDef);
		
		// überprüfe, ob es die Tabelle schon gibt,
		// ansonsten wird sie angelegt
		$tables = $app->db->getTables();
		
		if(!@in_array($formDef['db_table'],$tables)) {
			// Datenbank noch nicht vorhanden
			
			$columns = array();
			
			// füge ID Feld hinzu
			$col = array(	'action' 		=> 'add',
							'name'			=> $formDef["db_table_idx"],
							'type'			=> 'int64',
							'typeValue'		=> '',
							'defaultValue'	=> false,
							'notNull'		=> true,
							'autoInc'		=> true,
							'option'		=> 'primary'
						);
					
			$columns[] = $col;
			$app->db->show_error_messages = true;
			
			if($formDef["auth"] == 'yes') {
				
				$col = array(	'action' 		=> 'add',
								'name'			=> 'sys_userid',
								'type'			=> 'int32',
								'typeValue'		=> '',
								'defaultValue'	=> '0',
								'notNull'		=> true
							);
				$columns[] = $col;
				$col = array(	'action' 		=> 'add',
								'name'			=> 'sys_groupid',
								'type'			=> 'int32',
								'typeValue'		=> '',
								'defaultValue'	=> '0',
								'notNull'		=> true
							);
				$columns[] = $col;
				$col = array(	'action' 		=> 'add',
								'name'			=> 'sys_perm_user',
								'type'			=> 'varchar',
								'typeValue'		=> '5',
								'defaultValue'	=> 'NULL',
								'notNull'		=> true
							);
				$columns[] = $col;
				$col = array(	'action' 		=> 'add',
								'name'			=> 'sys_perm_group',
								'type'			=> 'varchar',
								'typeValue'		=> '5',
								'defaultValue'	=> 'NULL',
								'notNull'		=> true
							);
				$columns[] = $col;
				$col = array(	'action' 		=> 'add',
								'name'			=> 'sys_perm_other',
								'type'			=> 'varchar',
								'typeValue'		=> '5',
								'defaultValue'	=> 'NULL',
								'notNull'		=> true
							);
				$columns[] = $col;
			
			}
			
			
			foreach($formDef['tabs'] as $tab) {
				foreach($tab["fields"] as $name => $field) {
					/*
				       $columns = array(action =>   add | alter | drop
				                        name =>     Spaltenname
				                        name_new => neuer Spaltenname, nur bei 'alter' belegt
				                        type =>     42go-Meta-Type: int16, int32, int64, double, char, varchar, text, blob
				                        typeValue => Wert z.B. bei Varchar
				                        defaultValue =>  Default Wert
				                        notNull =>   true | false
				                        autoInc =>   true | false
				                        option =>   unique | primary | index)
				       
				       
					*/
					switch ($field["datatype"]) {
						case 'INTEGER':
							$type = 'int32';
							$typevalue = '';
							$defaultValue	= ($field["default"] != '')?$field["default"]:'0';
						break;
						case 'DOUBLE':
							$type = 'double';
							$typevalue = '';
							$defaultValue	= ($field["default"] != '')?$field["default"]:'0';
						break;
						case 'CURRENCY':
							$type = 'double';
							$typevalue = '';
							$defaultValue	= ($field["default"] != '')?$field["default"]:'0';
						break;
						case 'VARCHAR':
							$type = 'varchar';
							$typeValue = ($field["maxlength"] > 0 and $field["maxlength"] <= 256)?$field["maxlength"]:255;
							// $defaultValue	= ($field["default"] != '')?$field["default"]:'NOT NULL';
							$defaultValue	= ($field["default"] != '')?$field["default"]:'NULL';
						break;
						case 'TEXT':
							$type = 'text';
							$typevalue = '';
							$defaultValue = 'NULL';
						break;
						case 'DATE':
							$type = 'int64';
							$typevalue = '';
							$defaultValue	= ($field["default"] != '')?$field["default"]:'0';
						break;
					}
					
					
					$col = array(	'action' 		=> 'add',
									'name'			=> $name,
									'type'			=> $type,
									'typeValue'		=> $typeValue,
									'defaultValue'	=> $defaultValue,
									'notNull'		=> true
									);
					
					$columns[] = $col;
				}
			}
		
		$app->db->createTable($formDef["db_table"],$columns);
		
		}
    }
	
	function lng_add($lang,$formDef) {
		global $go_api, $go_info,$conf;
		
		$lng_file = "lib/lang/".$conf["language"]."_".$formDef['name'].".lng";
		if(is_file($lng_file)) {
			include($lng_file);
		} else {
			$wb = array();
		}
		
		$wb_out = array_merge($lang,$wb);
		
		if(is_array($wb_out)) {
			$fp = fopen ($lng_file, "w");
			fwrite($fp,"<?php\r\n");
			foreach($wb_out as $key => $val) {
				$new_line = '$wb["'.$key.'"] = '."'$val';\r\n";
				fwrite($fp,$new_line);
				
			}
			fwrite($fp,"?>");
			fclose($fp);
		}
	}
}

?>