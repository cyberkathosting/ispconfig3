<?php

// Name of the list
$liste['name'] = 'faq_manage_questions';

// Database table
$liste['table'] = 'help_faq';

// Index index field of the database table
$liste['table_idx'] = 'hf_id';

// Search Field Prefix
//$liste['search_prefix'] = 'search_';

// Records per page
$liste['records_per_page']= 30;

// Script File of the list
$liste['file'] = 'faq_manage_questions_list.php';

// Script file of the edit form
$liste['edit_file'] = 'faq_edit.php';

// Script File of the delete script
$liste['delete_file'] = 'faq_delete.php';

// Paging Template
$liste['paging_tpl'] = 'templates/paging.tpl.htm';

// Enable auth
$liste['auth'] = 'yes';


$liste["item"][] = array(   'field'     => "hf_section",
	'datatype'  => "VARCHAR",
	'formtype'  => "SELECT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'datasource'    => array (  'type'  => 'SQL',
		'querystring' => 'SELECT a.hf_section, b.hfs_name FROM help_faq a, help_faq_sections b WHERE (a.hf_section = b.hfs_id)',
		'keyfield'=> 'hf_section',
		'valuefield'=> 'hfs_name'
	),
	'width'     => "",
	'value'     => "");
?>
