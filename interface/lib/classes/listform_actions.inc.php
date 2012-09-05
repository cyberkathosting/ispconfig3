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

class listform_actions {
	
	private $id;
	public $idx_key;
	public $DataRowColor;
	public  $SQLExtWhere = '';
	public  $SQLOrderBy = '';
	
	public function onLoad()
    {
		global $app, $conf, $list_def_file;
		
		$app->uses('tpl,listform,tform');
		
		//* Clear session variable that is used when lists are embedded with the listview plugin
		$_SESSION['s']['form']['return_to'] = '';
		
		// Load list definition
		$app->listform->loadListDef($list_def_file);
		
		if(!is_file('templates/'.$app->listform->listDef["name"].'_list.htm')) {
			$app->uses('listform_tpl_generator');
			$app->listform_tpl_generator->buildHTML($app->listform->listDef);
		}
		
		$app->tpl->newTemplate("listpage.tpl.htm");
		$app->tpl->setInclude('content_tpl','templates/'.$app->listform->listDef["name"].'_list.htm');
		
		//* Manipulate order by for sorting / Every list has a stored value
		//* Against notice error
		if(!isset($_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'])){
		  $_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'] = '';
		}

		if(!empty($_GET['orderby'])){
		  $order = str_replace('tbl_col_','',$_GET['orderby']);
		  //* Check the css class submited value
		  if (preg_match("/^[a-z\_]{1,}$/",$order)) {
		    if($_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'] == $order){
		      $_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'] = $order.' DESC';
		    } else {
		      $_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'] = $order;
		    }
		  }
		}

		// If a manuel oder by like customers isset the sorting will be infront
		if(!empty($_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'])){
		  if(empty($this->SQLOrderBy)){
		    $this->SQLOrderBy = "ORDER BY ".$_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'];
		  } else {
		    $this->SQLOrderBy = str_replace("ORDER BY ","ORDER BY ".$_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'].', ',$this->SQLOrderBy);
		  }
		}
		
		// Getting Datasets from DB
		$records = $app->db->queryAllRecords($this->getQueryString());

		$this->DataRowColor = "#FFFFFF";
		$records_new = '';
		if(is_array($records)) {
			$this->idx_key = $app->listform->listDef["table_idx"]; 
			foreach($records as $rec) {
				$records_new[] = $this->prepareDataRow($rec);
			}
		}

		$app->tpl->setLoop('records',$records_new);

		$this->onShow();
		
		
	}
	
	public function prepareDataRow($rec)
    {
		global $app;
		
		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;
		
		//* substitute value for select fields
		if(is_array($app->listform->listDef['item']) && count($app->listform->listDef['item']) > 0) {
			foreach($app->listform->listDef['item'] as $field) {
				$key = $field['field'];
				if(isset($field['formtype']) && $field['formtype'] == 'SELECT') {
					if(strtolower($rec[$key]) == 'y' or strtolower($rec[$key]) == 'n') {
						// Set a additional image variable for bolean fields
						$rec['_'.$key.'_'] = (strtolower($rec[$key]) == 'y')?'x16/tick_circle.png':'x16/cross_circle.png';
					}
					//* substitute value for select field
					$rec[$key] = @$field['value'][$rec[$key]];
				}
			}
		}
		
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}
	
	private function getQueryString() {
		global $app;
		$sql_where = '';

		//* Generate the search sql
		if($app->listform->listDef['auth'] != 'no') {
			if($_SESSION['s']['user']['typ'] == "admin") {
				$sql_where = '';
			} else {
				$sql_where = $app->tform->getAuthSQL('r').' and';
			}
		}		
		if($this->SQLExtWhere != '') {
			$sql_where .= ' '.$this->SQLExtWhere.' and';
		}

		$sql_where = $app->listform->getSearchSQL($sql_where);
		$app->tpl->setVar($app->listform->searchValues);
		
		$order_by_sql = $this->SQLOrderBy;

		//* Generate SQL for paging
		$limit_sql = $app->listform->getPagingSQL($sql_where);
		$app->tpl->setVar('paging',$app->listform->pagingHTML);

		$extselect = '';
		$join = '';
		if(!empty($_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order'])){
		  $order = str_replace(' DESC','',$_SESSION['search'][$app->listform->listDef["name"].$app->listform->listDef['table']]['order']);
		  if($order == 'server_id' && $app->listform->listDef['table'] != 'server'){
		    $join .= ' LEFT JOIN server as s ON '.$app->listform->listDef['table'].'.server_id = s.server_id ';
		    $order_by_sql = str_replace('server_id','s.server_name',$order_by_sql);
		  } elseif($order == 'client_id' && $app->listform->listDef['table'] != 'client'){
		    $join .= ' LEFT JOIN client as c ON '.$app->listform->listDef['table'].'.client_id = c.client_id ';
		    $order_by_sql = str_replace('client_id','c.contact_name',$order_by_sql);
		  } elseif($order == 'parent_domain_id'){
		    $join .= ' LEFT JOIN web_domain as wd ON '.$app->listform->listDef['table'].'.parent_domain_id = wd.domain_id ';
		    $order_by_sql = str_replace('parent_domain_id','wd.domain',$order_by_sql);
		    $sql_where = str_replace('type',$app->listform->listDef['table'].'.type',$sql_where);
		  } elseif($order == 'sys_groupid'){
		    $join .= ' LEFT JOIN sys_group as sg ON '.$app->listform->listDef['table'].'.sys_groupid = sg.groupid ';
		    $order_by_sql = str_replace('sys_groupid','sg.name',$order_by_sql);
		  } elseif($order == 'rid'){
		    $join .= ' LEFT JOIN spamfilter_users as su ON '.$app->listform->listDef['table'].'.rid = su.id ';
		    $order_by_sql = str_replace('rid','su.email',$order_by_sql);
		  } elseif($order == 'policy_id'){
		    $join .= ' LEFT JOIN spamfilter_policy as sp ON '.$app->listform->listDef['table'].'.policy_id = sp.id ';
		    $order_by_sql = str_replace('policy_id','sp.policy_name',$order_by_sql);
		  } elseif($order == 'web_folder_id'){
		    $join .= ' LEFT JOIN web_folder as wf ON '.$app->listform->listDef['table'].'.web_folder_id = wf.web_folder_id ';
		    $order_by_sql = str_replace('web_folder_id','wf.path',$order_by_sql);
		  } elseif($order == 'ostemplate_id' && $app->listform->listDef['table'] != 'openvz_ostemplate'){
		    $join .= ' LEFT JOIN openvz_ostemplate as oo ON '.$app->listform->listDef['table'].'.ostemplate_id = oo.ostemplate_id ';
		    $order_by_sql = str_replace('ostemplate_id','oo.template_name',$order_by_sql);
		  } elseif($order == 'template_id' && $app->listform->listDef['table'] != 'openvz_template'){
		    $join .= ' LEFT JOIN openvz_template as ot ON '.$app->listform->listDef['table'].'.template_id = ot.template_id ';
		    $order_by_sql = str_replace('template_id','ot.template_name',$order_by_sql);
		  } elseif($order == 'sender_id' && $app->listform->listDef['table'] != 'sys_user'){
		    $join .= ' LEFT JOIN sys_user as su ON '.$app->listform->listDef['table'].'.sender_id = su.userid ';
		    $order_by_sql = str_replace('sender_id','su.username',$order_by_sql);
		  } elseif($order == 'web_traffic_last_month'){
		    $tmp_year = date('Y',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		    $tmp_month = date('m',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		    $extselect .= ', SUM(wt.traffic_bytes) as calctraffic';
		    $join .= ' INNER JOIN web_traffic as wt ON '.$app->listform->listDef['table'].'.domain = wt.hostname ';
		    $sql_where .= " AND YEAR(wt.traffic_date) = '$tmp_year' AND MONTH(wt.traffic_date) = '$tmp_month'";
		    $order_by_sql = str_replace('web_traffic_last_month','calctraffic',$order_by_sql);
		    $order_by_sql = "GROUP BY domain ".$order_by_sql;
		  } elseif($order == 'web_traffic_this_month'){
		    $tmp_year = date('Y');
		    $tmp_month = date('m');
		    $extselect .= ', SUM(wt.traffic_bytes) as calctraffic';
		    $join .= ' INNER JOIN web_traffic as wt ON '.$app->listform->listDef['table'].'.domain = wt.hostname ';
		    $sql_where .= " AND YEAR(wt.traffic_date) = '$tmp_year' AND MONTH(wt.traffic_date) = '$tmp_month'";
		    $order_by_sql = str_replace('web_traffic_this_month','calctraffic',$order_by_sql);
		    $order_by_sql = "GROUP BY domain ".$order_by_sql;
		  } elseif($order == 'web_traffic_last_year'){
		    $tmp_year = date('Y',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		    $extselect .= ', SUM(wt.traffic_bytes) as calctraffic';
		    $join .= ' INNER JOIN web_traffic as wt ON '.$app->listform->listDef['table'].'.domain = wt.hostname ';
		    $sql_where .= " AND YEAR(wt.traffic_date) = '$tmp_year'";
		    $order_by_sql = str_replace('web_traffic_last_year','calctraffic',$order_by_sql);
		    $order_by_sql = "GROUP BY domain ".$order_by_sql;
		  } elseif($order == 'web_traffic_this_year'){
		    $tmp_year = date('Y');
		    $extselect .= ', SUM(wt.traffic_bytes) as calctraffic';
		    $join .= ' INNER JOIN web_traffic as wt ON '.$app->listform->listDef['table'].'.domain = wt.hostname ';
		    $sql_where .= " AND YEAR(wt.traffic_date) = '$tmp_year'";
		    $order_by_sql = str_replace('web_traffic_this_year','calctraffic',$order_by_sql);
		    $order_by_sql = "GROUP BY domain ".$order_by_sql;
		  } elseif($order == 'mail_traffic_last_month'){
		    $tmp_date = date('Y-m',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		    $join .= ' INNER JOIN mail_traffic as mt ON '.$app->listform->listDef['table'].'.mailuser_id = mt.mailuser_id ';
		    $sql_where .= " AND mt.month like '$tmp_date%'";
		    $order_by_sql = str_replace('mail_traffic_last_month','traffic',$order_by_sql);
		  } elseif($order == 'mail_traffic_this_month'){
		    $tmp_date = date('Y-m');
		    $join .= ' INNER JOIN mail_traffic as mt ON '.$app->listform->listDef['table'].'.mailuser_id = mt.mailuser_id ';
		    $sql_where .= " AND mt.month like '$tmp_date%'";
		    $order_by_sql = str_replace('mail_traffic_this_month','traffic',$order_by_sql);
		  } elseif($order == 'mail_traffic_last_year'){
		    $tmp_date = date('Y',mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		    $extselect .= ', SUM(mt.traffic) as calctraffic';
		    $join .= ' INNER JOIN mail_traffic as mt ON '.$app->listform->listDef['table'].'.mailuser_id = mt.mailuser_id ';
		    $sql_where .= " AND mt.month like '$tmp_date%'";;
		    $order_by_sql = str_replace('mail_traffic_last_year','calctraffic',$order_by_sql);
		    $order_by_sql = "GROUP BY mailuser_id ".$order_by_sql;
		  } elseif($order == 'mail_traffic_this_year'){
		    $tmp_date = date('Y');
		    $extselect .= ', SUM(mt.traffic) as calctraffic';
		    $join .= ' INNER JOIN mail_traffic as mt ON '.$app->listform->listDef['table'].'.mailuser_id = mt.mailuser_id ';
		    $sql_where .= " AND mt.month like '$tmp_date%'";
		    $order_by_sql = str_replace('mail_traffic_this_year','calctraffic',$order_by_sql);
		    $order_by_sql = "GROUP BY mailuser_id ".$order_by_sql;
		  }
		}
		return 'SELECT '.$app->listform->listDef['table'].'.*'.$extselect.' FROM '.$app->listform->listDef['table']."$join WHERE $sql_where $order_by_sql $limit_sql";
	}
	
	
	public function onShow()
    {
		global $app;
		
		//* Set global Language File
		$lng_file = ISPC_LIB_PATH.'/lang/'.$_SESSION['s']['language'].'.lng';
		if(!file_exists($lng_file))
		$lng_file = ISPC_LIB_PATH.'/lang/en.lng';
		include($lng_file);
		$app->tpl->setVar($wb);
		
		//* Limit each page
		$limits = array('5'=>'5','15'=>'15','25'=>'25','50'=>'50','100'=>'100','999999999' => 'all');

		//* create options and set selected, if default -> 15 is selected

		$options = '';
		foreach($limits as $key => $val){
		  $options .= '<option value="'.$key.'" '.(isset($_SESSION['search']['limit']) &&  $_SESSION['search']['limit'] == $key ? 'selected="selected"':'' ).(!isset($_SESSION['search']['limit']) && $key == '15' ? 'selected="selected"':'').'>'.$val.'</option>';
		}
		$app->tpl->setVar('search_limit','<select name="search_limit" class="search_limit">'.$options.'</select>');
		
		$app->tpl->setVar('toolsarea_head_txt',$app->lng('toolsarea_head_txt'));
		$app->tpl->setVar($app->listform->wordbook);
		$app->tpl->setVar('form_action', $app->listform->listDef['file']);
		
        if(isset($_SESSION['show_info_msg'])) {
            $app->tpl->setVar('show_info_msg', $_SESSION['show_info_msg']);
            unset($_SESSION['show_info_msg']);
        }
        if(isset($_SESSION['show_error_msg'])) {
            $app->tpl->setVar('show_error_msg', $_SESSION['show_error_msg']);
            unset($_SESSION['show_error_msg']);
        }
        
		//* Parse the templates and send output to the browser
		$this->onShowEnd();
	}
	
	public function onShowEnd()
    {
		global $app;
		$app->tpl_defaults();
		$app->tpl->pparse();
	}
}

?>