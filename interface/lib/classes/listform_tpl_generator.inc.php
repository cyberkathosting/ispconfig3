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

class listform_tpl_generator {

	function buildHTML($listDef, $module = '') {

		global $app;

		if($module == '') $module = $_SESSION["s"]["module"]["name"];

		$lang = array();
		$html = '<h2><tmpl_var name="list_head_txt"></h2>

<div class="panel panel_list_'.$listDef["name"].'">

  <div class="pnl_toolsarea">
    <fieldset><legend>{tmpl_var name="toolsarea_head_txt"}</legend>
      <div class="buttons">
        <button class="iconstxt icoAdd" type="button" data-load-content="'.$module."/".$listDef["edit_file"].'">
          <span>{tmpl_var name="add_new_record_txt"}</span>
        </button>
      </div>
    </fieldset>
  </div>

  <div class="pnl_listarea">
    <fieldset><legend><tmpl_var name="list_head_txt"></legend>
      <table class="list">
        <thead>
          <tr>
';

		$lang["list_head_txt"] = $listDef["name"];
		$colcount = 0;
		foreach($listDef["item"] as $field) {
			$key = $field["field"];
			$html .= "            <th class=\"tbl_col_".$key."\" scope=\"col\"><tmpl_var name=\"".$key."_txt\"></th>\n";
			$lang[$key."_txt"] = $key;
			$colcount++;
		}

		$html .= '            <th class="tbl_col_buttons" scope="col">&nbsp;</th>
          </tr>
          <tr>
';

		foreach($listDef["item"] as $field) {
			$key = $field["field"];
			if($field["formtype"] == 'SELECT') {
				$html .= "            <td class=\"tbl_col_".$key."\"><select name=\"".$listDef["search_prefix"].$key."\" onChange=\"ISPConfig.submitForm('pageForm','".$module."/".$listDef["file"]."');\">{tmpl_var name='".$listDef["search_prefix"].$key."'}</select></td>\n";
			} else {
				$html .= "            <td class=\"tbl_col_".$key."\"><input type=\"text\" name=\"".$listDef["search_prefix"].$key."\" value=\"{tmpl_var name='".$listDef["search_prefix"].$key."'}\" /></td>\n";
			}
		}

		$html .= '            <td class="tbl_col_buttons"><div class="buttons"><button type="button" class="icons16 icoFilter" name="Filter" id="Filter" value="{tmpl_var name="filter_txt"}" onclick="'."ISPConfig.submitForm('pageForm','".$module."/".$listDef["file"]."');".'"><span>{tmpl_var name="filter_txt"}</span></button></div></td>
          </tr>
        </thead>
        <tbody>
          <tmpl_loop name="records">
          <tr class="tbl_row_<tmpl_if name=\'__EVEN__\'}even<tmpl_else>uneven</tmpl_if>">
';

		foreach($listDef["item"] as $field) {
			$key = $field["field"];
			$html .= "            <td class=\"tbl_col_".$key."\"><a href=\"#\" data-load-content=\"".$module."/".$listDef["edit_file"]."?id={tmpl_var name='id'}\">{tmpl_var name=\"".$key."\"}</a></td>\n";
		}

		$html .= "            <td class=\"tbl_col_buttons\">
              <div class=\"buttons icons16\">
                <a class=\"button icons16 icoDelete\" href=\"javascript: del_record('".$module."/".$listDef["delete_file"]."?id={tmpl_var name='id'}&phpsessid={tmpl_var name='phpsessid'}','{tmpl_var name='delete_confirmation'}');\"><span>{tmpl_var name='delete_txt'}</span></a>
              </div>
            </td>
          </tr>
          </tmpl_loop>
          <tmpl_unless name=\"records\">
              <tr class=\"tbl_row_noresults tbl_row_<tmpl_if name='__EVEN__'}even<tmpl_else>uneven</tmpl_if>\">
                  <td colspan=\"".$colcount."\">{tmpl_var name='globalsearch_noresults_text_txt'}</td>
              </tr>
          </tmpl_unless>
        </tbody>";
		$html .= '
        <tfoot>
          <tr>
            <td class="tbl_footer tbl_paging" colspan="'.(count($listDef["item"])+1).'"><tmpl_var name="paging"></td>
          </tr>
        </tfoot>
      </table>
    </fieldset>
  </div>

</div>
';

		if($module == '') {
			$filename = 'templates/'.$listDef["name"].'_list.htm';
		} else {
			$filename = '../'.$module.'/templates/'.$listDef["name"].'_list.htm';
		}


		// save template
		if (!$handle = fopen($filename, 'w')) {
			print "Cannot open file ($filename)";
			exit;
		}

		if (!fwrite($handle, $html)) {
			print "Cannot write to file ($filename)";
			exit;
		}
		fclose($handle);

		/*$lang["page_txt"] = 'Page';
		$lang["page_of_txt"] = 'of';
		$lang["page_next_txt"] = 'Next';
		$lang["page_back_txt"] = 'Back';
		$lang["delete_txt"] = 'Delete';
		$lang["filter_txt"] = 'Filter';
		$lang["add_new_record_txt"] = 'Add new record';
		*/

		// save language file
		$this->lng_add($lang, $listDef, $module);
	}

	function lng_add($lang, $listDef, $module = '') {
		global $app, $conf;

		if($module == '') {
			$lng_file = "lib/lang/".$app->functions->check_language($conf["language"])."_".$listDef['name']."_list.lng";
		} else {
			$lng_file = '../'.$module."/lib/lang/en_".$listDef['name']."_list.lng";
		}

		if(is_file($lng_file)) {
			include_once $lng_file;
		} else {
			$wb = array();
		}

		$wb_out = array_merge($lang, $wb);

		if(is_array($wb_out)) {
			$fp = fopen($lng_file, "w");
			fwrite($fp, "<?php\n");
			foreach($wb_out as $key => $val) {
				$new_line = '$wb["'.$key.'"] = '."'$val';\n";
				fwrite($fp, $new_line);

			}
			fwrite($fp, "?>");
			fclose($fp);
		}
	}

}

?>
