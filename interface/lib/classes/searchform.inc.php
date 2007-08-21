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

/**
* Listenbehandlung
*
* @package searchform
* @author Till Brehm
* @version 1.1
*/

class searchform {

    public $debug = 0;
    public $errorMessage;
    public $listDef;
    public $searchValues;
    public $pagingHTML;
    public $pagingValues;
    public $searchChanged = 0;
    public $module;

    public function loadListDef($file, $module = '')
    {
        global $app, $conf;
        if(!is_file($file)){
            die("List-Definition: $file not found.");
        }
        include_once($file);
        $this->listDef = $liste;
        $this->module = $module;
		
		//* Fill datasources
		foreach($this->listDef['item'] as $key => $field) {
			if(is_array($field['datasource'])) {
                $this->listDef['item'][$key]['value'] = $this->getDatasourceData($field);
            }
		}	
        return true;
    }
		
	/**
    * Get the key => value array of a form filed from a datasource definitiom
    *
    * @param field = array with field definition
    * @param record = Dataset as array
    * @return key => value array for the value field of a form
    */

    public function getDatasourceData($field)
    {
        global $app;
        $values = array();

        if($field['datasource']['type'] == 'SQL') {
            //* Preparing SQL string. We will replace some common placeholders
            $querystring = $field['datasource']['querystring'];
            $querystring = str_replace('{USERID}', $_SESSION['s']['user']['userid'], $querystring);
            $querystring = str_replace('{GROUPID}', $_SESSION['s']['user']['default_group'], $querystring);
            $querystring = str_replace('{GROUPS}', $_SESSION['s']['user']['groups'], $querystring);
            $table_idx = $this->formDef['db_table_idx'];
            //$querystring = str_replace('{RECORDID}',$record[$table_idx],$querystring);
            $app->uses('tform');
            $querystring = str_replace('{AUTHSQL}', $app->tform->getAuthSQL('r'), $querystring);

            //* Getting the records
            $tmp_records = $app->db->queryAllRecords($querystring);
            if($app->db->errorMessage != ''){
                die($app->db->errorMessage);
            }
            if(is_array($tmp_records)) {
                $key_field = $field['datasource']['keyfield'];
                $value_field = $field['datasource']['valuefield'];
                foreach($tmp_records as $tmp_rec) {
                    $values[$tmp_rec[$key_field]] = $tmp_rec[$value_field];
                }
            }
        }
        if($field['datasource']['type'] == 'CUSTOM') {
            //* Calls a custom class to validate this record
            if($field['datasource']['class'] != '' and $field['datasource']['function'] != '') {
                $datasource_class = $field['datasource']['class'];
                $datasource_function = $field['datasource']['function'];
                $app->uses($datasource_class);
                $record = array();
                $values = $app->$datasource_class->$datasource_function($field, $record);
            }else{
                $this->errorMessage .= "Custom datasource class or function is empty<br>\r\n";
            }
        }
        return $values;
    }

    public function getSearchSQL($sql_where = '')
    {
        global $db;

        //* Config vars
        $list_name = $this->listDef['name'];
        $search_prefix = $this->listDef['search_prefix'];

        //* store retrieval query
        foreach($this->listDef['item'] as $i) {
            $field = $i['field'];

            //* TODO ?  hat sich die suche ge�ndert - has itself search  ?
            $ki = $search_prefix.$field;
            if(isset($_REQUEST) and $_REQUEST[$ki] != $_SESSION['search'][$list_name][$ki]){
                $this->searchChanged = 1;
            }

            //* suchfield in session store.
            if(isset($_REQUEST[$ki])){
                $_SESSION['search'][$list_name][$ki] = $_REQUEST[$ki];
            }

            if($i['formtype'] == 'SELECT'){
                if(is_array($i['value'])) {
                    $out = '<option value=""></option>';
                    foreach($i['value'] as $k => $v) {
                        $selected = ($k == $_SESSION['search'][$list_name][$ki] && $_SESSION['search'][$list_name][$ki] != '') ? ' SELECTED' : '';
                        $out .= "<option value='$k'$selected>$v</option>\r\n";
                    }
                }
                $this->searchValues[$ki] = $out;
            }else{
                $this->searchValues[$ki] = $_SESSION['search'][$list_name][$ki];
            }
        }

        //* store variables in object. $this->searchValues = $_SESSION["search"][$list_name];
        foreach($this->listDef['item'] as $i) {
            $field = $i['field'];
            //if($_REQUEST[$search_prefix.$field] != '') $sql_where .= " $field ".$i["op"]." '".$i["prefix"].$_REQUEST[$search_prefix.$field].$i["suffix"]."' and";
			if($_SESSION['search'][$list_name][$ki] != ''){
                $sql_where .= " $field ".$i['op']." '".$i['prefix'].$_SESSION['search'][$list_name][$ki].$i['suffix']."' and";
            }
        }
        return ($sql_where != '') ? substr($sql_where, 0, -3) : '1';
    }

    public function getPagingSQL($sql_where = '1') {
        global $app, $conf;

        $list_name          = $this->listDef['name'];
        $search_prefix      = $this->listDef['search_prefix'];
        $records_per_page   = $this->listDef['records_per_page'];
        $table              = $this->listDef['table'];

        //* set page to seror id session not set
        if($_SESSION['search'][$list_name]['page'] == '')   $_SESSION['search'][$list_name]['page'] = 0;

        //* Set page size to request if set
        if(isset($_REQUEST['page']))    $_SESSION['search'][$list_name]['page'] = $_REQUEST['page']; 

        //* TODO PAGE to 0 set, if look for themselves ge?ndert. =  page auf 0 setzen, wenn suche sich ge�ndert hat.
        if($this->searchChanged == 1)   $_SESSION['search'][$list_name]['page'] = 0;

        $sql_von = $_SESSION['search'][$list_name]['page'] * $records_per_page;
        $record_count = $app->db->queryOneRecord("SELECT count(*) AS anzahl FROM $table WHERE $sql_where");
        $pages = intval(($record_count['anzahl'] - 1) / $records_per_page);

        $vars['list_file']      = $this->listDef['file'];
        $vars['page']           = $_SESSION['search'][$list_name]['page'];
        $vars['last_page']      = $_SESSION['search'][$list_name]['page'] - 1;
        $vars['next_page']      = $_SESSION['search'][$list_name]['page'] + 1;
        $vars['pages']          = $pages;
        $vars['max_pages']      = $pages + 1;
        $vars['records_gesamt'] = $record_count['anzahl'];
        $vars['page_params']    = $this->listDef['page_params'];

        if($_SESSION['search'][$list_name]['page'] > 0) $vars['show_page_back'] = 1;
        if($_SESSION['search'][$list_name]['page'] <= $vars['pages'] - 1) $vars['show_page_next'] = 1;

        $this->pagingValues = $vars;
        $this->pagingHTML = $this->getPagingHTML($vars);

        return "LIMIT $sql_von, $records_per_page";
    }

    public function getPagingHTML($vars) {
        global $app;
        $page_params = $vars['page_params'];
        $list_file = $vars['list_file'];
        $content = '<a href="'.$list_file.'?page=0'.$page_params.'"><img src="../themes/iprg/images/btn_left.png" border="0"></a> &nbsp; ';
        if($vars['show_page_back'] == 1){
            $content .= '<a href="'.$list_file.'?page='.$vars['last_page'].$page_params.'"><img src="../themes/iprg/images/btn_back.png" border="0"></a> ';
        }
        $content .= ' '.$app->lng('Page').' '.$vars['next_page'].' '.$app->lng('of').' '.$vars['max_pages'].' ';
        if($vars['show_page_next'] == 1){
            $content .= '<a href="'.$list_file.'?page='.$vars['next_page'].$page_params.'"><img src="../themes/iprg/images/btn_next.png" border="0"></a> &nbsp; ';
        }
        $content .= '<a href="'.$list_file.'?page='.$vars['pages'].$page_params.'"> <img src="../themes/iprg/images/btn_right.png" border="0"></a>';
        return $content;
    }
		
    public function getPagingHTMLasTXT($vars)
    {
        global $app;
        $page_params = $vars['page_params'];
        $list_file = $vars['list_file'];
        $content = '[<a href="'.$list_file.'?page=0'.$page_params.'">|&lt;&lt; </a>]';
        if($vars['show_page_back'] == 1){
            $content .= '[<< <a href="'.$list_file.'?page='.$vars['last_page'].$page_params.'">'.$app->lng('Back').'</a>] ';
        }
        $content .= ' '.$app->lng('Page').' '.$vars['next_page'].' '.$app->lng('of').' '.$vars['max_pages'].' ';
        if($vars['show_page_next'] == 1){
            $content .= '[<a href="'.$vars['list_file'].'?page='.$vars['next_page'].$page_params.'">'.$app->lng('Next').' >></a>] ';
        }
        $content .= '[<a href="'.$list_file.'?page='.$vars['pages'].$page_params.'"> &gt;&gt;|</a>]';
        return $content;
    }

    public function getSortSQL()
    {
        $sort_field = $this->listDef['sort_field'];
        $sort_direction = $this->listDef['sort_direction'];
        return ($sort_field != '' && $sort_direction != '') ? "ORDER BY $sort_field $sort_direction" : '';
    }
		
	public function saveSearchSettings($searchresult_name)
    {
		global $app, $conf;
		
		$list_name = $this->listDef['name'];
		$settings = $_SESSION['search'][$list_name];
		unset($settings['page']);
		$data = addslashes(serialize($settings));
		
		$userid = $_SESSION['s']['user']['userid'];
		$groupid = $_SESSION['s']['user']['default_group'];
		$sys_perm_user = 'riud';
		$sys_perm_group = 'r';
		$sys_perm_other = '';
		$module = $_SESSION['s']['module']['name'];
		$searchform = $this->listDef['name'];
		$title = $searchresult_name;
		
		$sql = 'INSERT INTO `searchform` ( '
               .'`sys_userid` , `sys_groupid` , `sys_perm_user` , `sys_perm_group` , `sys_perm_other` , `module` , `searchform` , `title` , `data` '
               .')VALUES ('
               ."'$userid', '$groupid', '$sys_perm_user', '$sys_perm_group', '$sys_perm_other', '$module', '$searchform', '$title', '$data')";
		$app->db->query($sql);
	}

    public function decode($record)
    {
        if(is_array($record)) {
            foreach($this->listDef['item'] as $field) {
                $key = $field['field'];
                switch ($field['datatype'])
                {
                    case 'DATE':
                        if($val > 0) {
                            $record[$key] = date($this->dateformat, $record[$key]);
                        }
                        break;
    
                    case 'INTEGER':
                        $record[$key] = intval($record[$key]);
                        break;
    
                    case 'DOUBLE':
                        $record[$key] = $record[$key];
                        break;
    
                    case 'CURRENCY':
                        $record[$key] = number_format($record[$key], 2, ',', '');
                        break;
    
                    
                    case 'VARCHAR':
                    case 'TEXT':
                    default:
                        $record[$key] = stripslashes($record[$key]);
                        break;
                    }
            }
        }
        return $record;
    }

    public function encode($record)
    {
        if(is_array($record)) {
            foreach($this->listDef['item'] as $field) {
                $key = $field['field'];
                switch ($field['datatype'])
                {
                    case 'VARCHAR':
                    case 'TEXT':
                        if(!is_array($record[$key])) {
                            $record[$key] = addslashes($record[$key]);
                        } else {
                            $record[$key] = implode($this->tableDef[$key]['separator'],$record[$key]);
                        }
                        break;

                    case 'DATE':
                        if($record[$key] > 0) {
                            list($tag, $monat, $jahr) = explode('.', $record[$key]);
                            $record[$key] = mktime(0, 0, 0, $monat, $tag, $jahr);
                        }
                        break;

                    case 'INTEGER':
                        $record[$key] = intval($record[$key]);
                        break;

                    case 'DOUBLE':
                        $record[$key] = addslashes($record[$key]);
                        break;

                    case 'CURRENCY':
                        $record[$key] = str_replace(',', '.', $record[$key]);
                        break;
                }
            }
        }
        return $record;
    }
}

?>