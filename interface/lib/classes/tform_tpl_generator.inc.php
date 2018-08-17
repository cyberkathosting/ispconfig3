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

class tform_tpl_generator {

	function buildHTML($formDef, $tab) {

		global $app;

		$module = $_SESSION["s"]["module"]["name"];

		$html = '<h2><tmpl_var name="list_head_txt"></h2>
<p><tmpl_var name="list_desc_txt"></p>

<div class="panel panel_'.$formDef['name'].'">

  <div class="pnl_formsarea">
    <fieldset class="inlineLabels"><legend>'.$formDef['tabs'][$tab]['title'].'</legend>';

		$lang = array();
		$html_reqestedelement = "<em>*</em> ";

		foreach($formDef['tabs'][$tab]['fields'] as $key => $field) {
			if ($field['required'] == true ) { $html_reqcode = $html_reqestedelement; } else { $html_reqcode = ''; }

			switch ($field['formtype']) {
			case 'TEXT':
				$html .= "
      <div class=\"ctrlHolder\">
      	<label for=\"".$key."\">".$html_reqcode."{tmpl_var name='".$key."_txt'}</label>
        <input name=\"".$key."\" id=\"".$key."\" value=\"{tmpl_var name='".$key."'}\" size=\"".$field['width']."\" maxlength=\"".$field['maxlength']."\" type=\"text\" class=\"textInput\" />
			</div>";
				break;
			case 'TEXTAREA':
				$html .= "
      <div class=\"ctrlHolder\">
      	<label for=\"".$key."\">".$html_reqcode."{tmpl_var name='".$key."_txt'}</label>
        <textarea name=\"".$key."\" id=\"".$key."\" rows='".$field['rows']."' cols='".$field['cols']."'>{tmpl_var name='".$key."'}</textarea>
      </div>";
				break;
			case 'SELECT':
				$html .= "
      <div class=\"ctrlHolder\">
      	<label for=\"".$key."\">".$html_reqcode."{tmpl_var name='".$key."_txt'}</label>
        <select name=\"".$key."\" id=\"".$key."\" class=\"selectInput\">
					{tmpl_var name='".$key."'}
				</select>
      </div>";
				break;
			case 'MULTIPLE':
				$html .= "
      <div class=\"ctrlHolder\">
      	<label for=\"".$key."\">".$html_reqcode."{tmpl_var name='".$key."_txt'}</label>
        <select multiple name=\"".$key."\" id=\"".$key."\" class=\"selectInput\">
					{tmpl_var name='".$key."'}
				</select>
      </div>";
				break;
			case 'PASSWORD':
				$html .= "
      <div class=\"ctrlHolder\">
      	<label for=\"".$key."\">".$html_reqcode."{tmpl_var name='".$key."_txt'}</label>
        <input name=\"".$key."\" id=\"".$key."\" value=\"{tmpl_var name='".$key."'}\" size=\"".$field['width']."\" maxlength=\"".$field['maxlength']."\" type=\"password\" class=\"textInput\" />
			</div>";
				break;
			case 'CHECKBOX':
				$html .= "
      <div class=\"ctrlHolder\">
				<p class=\"label\">".$html_reqcode."{tmpl_var name='".$key."_txt'}</p>
					<div class=\"multiField\">
						{tmpl_var name='".$key."'}
					</div>
			</div>";
				break;
			case 'CHECKBOXARRAY':
				$html .= "
      <div class=\"ctrlHolder\">
				<p class=\"label\">".$html_reqcode."{tmpl_var name='".$key."_txt'}</p>
					<div class=\"multiField\">
						{tmpl_var name='".$key."'}
					</div>
			</div>";
				break;
			case 'RADIO':
				$html .= "
      <div class=\"ctrlHolder\">
				<p class=\"label\">".$html_reqcode."{tmpl_var name='".$key."_txt'}</p>
					<div class=\"multiField\">
						{tmpl_var name='".$key."'}
					</div>
			</div>";
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

		$html .= "
    </fieldset>

    <input type=\"hidden\" name=\"id\" value=\"{tmpl_var name='id'}\">

    <div class=\"buttonHolder buttons\">
      <button class=\"positive iconstxt icoPositive\" type=\"button\" value=\"{tmpl_var name='btn_save_txt'}\" onclick=\"ISPConfig.submitForm('pageForm','".$module."/".$formDef["action"]."');\"><span>{tmpl_var name='btn_save_txt'}</span></button>
      <button class=\"negative iconstxt icoNegative\" type=\"button\" value=\"{tmpl_var name='btn_cancel_txt'}\" data-load-content=\"".$module."/".$formDef["list_default"]."\"><span>{tmpl_var name='btn_cancel_txt'}</span></button>
    </div>
  </div>

</div>
";


		// speichere Template
		if (!$handle = fopen($formDef['tabs'][$tab]['template'], 'w')) {
			print "Cannot open file (".$formDef['tabs'][$tab]['template'].")";
			exit;
		}

		if (!fwrite($handle, $html)) {
			print "Cannot write to file ($filename)";
			exit;
		}
		fclose($handle);

		$this->lng_add($lang, $formDef);

		// überprüfe, ob es die Tabelle schon gibt,
		// ansonsten wird sie angelegt
		$tables = $app->db->getTables();

		if(!@in_array($formDef['db_table'], $tables)) {
			// Datenbank noch nicht vorhanden

			$columns = array();

			// füge ID Feld hinzu
			$col = array( 'action'   => 'add',
				'name'   => $formDef["db_table_idx"],
				'type'   => 'int64',
				'typeValue'  => '',
				'defaultValue' => false,
				'notNull'  => true,
				'autoInc'  => true,
				'option'  => 'primary'
			);

			$columns[] = $col;
			$app->db->show_error_messages = true;

			if($formDef["auth"] == 'yes') {

				$col = array( 'action'   => 'add',
					'name'   => 'sys_userid',
					'type'   => 'int32',
					'typeValue'  => '',
					'defaultValue' => '0',
					'notNull'  => true
				);
				$columns[] = $col;
				$col = array( 'action'   => 'add',
					'name'   => 'sys_groupid',
					'type'   => 'int32',
					'typeValue'  => '',
					'defaultValue' => '0',
					'notNull'  => true
				);
				$columns[] = $col;
				$col = array( 'action'   => 'add',
					'name'   => 'sys_perm_user',
					'type'   => 'varchar',
					'typeValue'  => '5',
					'defaultValue' => 'NULL',
					'notNull'  => true
				);
				$columns[] = $col;
				$col = array( 'action'   => 'add',
					'name'   => 'sys_perm_group',
					'type'   => 'varchar',
					'typeValue'  => '5',
					'defaultValue' => 'NULL',
					'notNull'  => true
				);
				$columns[] = $col;
				$col = array( 'action'   => 'add',
					'name'   => 'sys_perm_other',
					'type'   => 'varchar',
					'typeValue'  => '5',
					'defaultValue' => 'NULL',
					'notNull'  => true
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
						$defaultValue = ($field["default"] != '')?$field["default"]:'0';
						break;
					case 'DOUBLE':
						$type = 'double';
						$typevalue = '';
						$defaultValue = ($field["default"] != '')?$field["default"]:'0';
						break;
					case 'CURRENCY':
						$type = 'double';
						$typevalue = '';
						$defaultValue = ($field["default"] != '')?$field["default"]:'0';
						break;
					case 'VARCHAR':
						$type = 'varchar';
						$typeValue = ($field["maxlength"] > 0 and $field["maxlength"] <= 256)?$field["maxlength"]:255;
						// $defaultValue = ($field["default"] != '')?$field["default"]:'NOT NULL';
						$defaultValue = ($field["default"] != '')?$field["default"]:'NULL';
						break;
					case 'TEXT':
						$type = 'text';
						$typevalue = '';
						$defaultValue = NULL;
						break;
					case 'DATE':
						$type = 'date';
						$typevalue = '';
						$defaultValue = ($field["default"] != '')?$field["default"]:null;
						break;
					}


					$col = array( 'action'   => 'add',
						'name'   => $name,
						'type'   => $type,
						'typeValue'  => $typeValue,
						'defaultValue' => $defaultValue,
						'notNull'  => true
					);

					$columns[] = $col;
				}
			}

			$app->db->createTable($formDef["db_table"], $columns);

		}
	}

	function lng_add($lang, $formDef) {
		global $go_api, $go_info, $conf;

		$lng_file = "lib/lang/".$app->functions->check_language($conf["language"])."_".$formDef['name'].".lng";
		if(is_file($lng_file)) {
			include $lng_file;
		} else {
			$wb = array();
		}

		$wb_out = array_merge($lang, $wb);

		if(is_array($wb_out)) {
			$fp = fopen($lng_file, "w");
			fwrite($fp, "<?php\n");
			foreach($wb_out as $key => $val) {
				$val = str_replace("'", "\\'",$val);
				$key = str_replace("'", "\\'",$key);
				$new_line = '$wb["'.$key.'"] = '."'$val';\n";
				fwrite($fp, $new_line);

			}
			fwrite($fp, "?>");
			fclose($fp);
		}
	}

}

?>
