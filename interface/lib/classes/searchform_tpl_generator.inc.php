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

class searchform_tpl_generator {
	
	function buildHTML($listDef,$module = '') {
		
		global $app;
		
		$lang = array();
		$html = '<form name="myform" action="'.$listDef["file"].'" method="POST">
<div class="frmTextHead"><tmpl_var name="list_head_txt"></div><br />
<tmpl_if name="searchform_visible">
<table border="0" cellspacing="0" cellpadding="4">';

		$lang["list_head_txt"] = $listDef["name"];
		foreach($listDef["item"] as $field) {
			$key = $field["field"];
			
			if($field["formtype"] == 'SELECT') {
				$html .= "
  <tr>
    <td class=\"frmText11\"><tmpl_var name=\"".$key."_txt\">:</td>
	<td><select name=\"".$listDef["search_prefix"].$key."\">{tmpl_var name='".$listDef["search_prefix"].$key."'}</select></td>
  </tr>";
  			} else {
				$html .= "
  <tr>
    <td class=\"frmText11\"><tmpl_var name=\"".$key."_txt\">:</td>
	<td><input type=\"text\" name=\"".$listDef["search_prefix"].$key."\" value=\"{tmpl_var name='".$listDef["search_prefix"].$key."'}\" class=\"text\" /></td>
  </tr>";
  			}
		}

$html .= '
  <tr>
    <td colspan="2" align="center"><input name="Filter" type="image" id="Filter" src="../themes/iprg/images/btn_filter.png"></td>
  </tr>
</table>
</tmpl_if>
<tmpl_if name="searchresult_visible">
<p>
[<a class="frmText11" href="domain_search.php?searchform_visible=yes&searchresult_visible=no&empty_searchfields=yes">Neue Suche</a>] 
[<a class="frmText11" href="domain_search.php?searchform_visible=yes&searchresult_visible=no&empty_searchfields=no">Suchkriterien ändern</a>]
Suche speichern unter: <input type="text" name="search_save_as" /> <input type="submit" name="btn_submit_search_save" value="Speichern" /> 
</p>
<table width="100%" border="0" cellspacing="0" cellpadding="4">
  <tr>
';
		
		$lang["list_head_txt"] = $listDef["name"];
		foreach($listDef["item"] as $field) {
			$key = $field["field"];
			$html .= "<tmpl_if name='".$key."_visible'>";
			$html .= "    <td class=\"tblHead\"><tmpl_var name=\"".$key."_txt\"></td>\r\n";
			$html .= "</tmpl_if>";
			$lang[$key."_txt"] = $key;
		}
		
		$html .= '    <td class="tblHead">&nbsp;</td>
  </tr>
  <tmpl_loop name="records">
  <tr bgcolor="{tmpl_var name="bgcolor"}">
';
		
		foreach($listDef["item"] as $field) {
			$key = $field["field"];
			$html .= "<tmpl_if name='".$key."_visible'>";
			$html .= "    <td class=\"frmText11\"><a href=\"".$listDef["edit_file"]."?id={tmpl_var name='id'}\" class=\"frmText11\">{tmpl_var name=\"".$key."\"}</a></td>\r\n";
			$html .= "</tmpl_if>";
		}
		
		$html .= "    <td class=\"frmText11\" align=\"right\">[<a href=\"javascript: del_record('".$listDef["delete_file"]."?id={tmpl_var name='id'}&phpsessid={tmpl_var name='phpsessid'}');\" class=\"frmText11\">{tmpl_var name='delete_txt'}</a>]</td>
  </tr>
  </tmpl_loop>
";
  $html .= '
</table><table width="100%" border="0" cellspacing="0" cellpadding="4">
  <tr>
  	<td height="40" align="center" class="tblFooter"><tmpl_var name="paging"></td>
  </tr>
</table>
</tmpl_if>
</form>';
		
		if($module == '') {
			$filename = 'templates/'.$listDef["name"].'_search.htm';
		} else {
			$filename = '../'.$module.'/templates/'.$listDef["name"].'_search.htm';
		}
		
		
		// speichere Template
		if (!$handle = fopen($filename, 'w')) { 
        	print "Cannot open file ($filename)"; 
        	exit; 
   		} 
 
   		if (!fwrite($handle, $html)) { 
			print "Cannot write to file ($filename)"; 
			exit; 
		}
		fclose($handle);
		
		$lang["page_txt"] = 'Page';
		$lang["page_of_txt"] = 'of';
		$lang["page_next_txt"] = 'Next';
		$lang["page_back_txt"] = 'Back';
		$lang["delete_txt"] = 'Delete';
		$lang["filter_txt"] = 'Filter';
		
		// speichere language Datei
		$this->lng_add($lang,$listDef,$module);
    }
	
	function lng_add($lang,$listDef,$module = '') {
		global $go_api, $go_info,$conf;
		
		if($module == '') {
			$lng_file = "lib/lang/".$conf["language"]."_".$listDef['name']."_search.lng";
		} else {
			$lng_file = '../'.$module."/lib/lang/en_".$listDef['name']."_search.lng";
		}
		
		if(is_file($lng_file)) {
			include_once($lng_file);
		} else {
			$wb = array();
		}
		
		$wb_out = array_merge($wb,$lang);
		
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