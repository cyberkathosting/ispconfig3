<?php

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

// Path to the list definition file
$list_def_file = 'list/faq_list.php';

// Check the module permissions
$app->auth->check_module_permissions('help');

// Loading the class
$app->uses('listform_actions');

// Optional limit
$hf_section = 0;
if(isset($_GET['hfs_id']))
	$hf_section = $app->functions->intval(preg_replace("/[^0-9]/", "", $_GET['hfs_id']));

// if section id is not specified in the url, choose the first existing section
if(!$hf_section)
{
	$res = $app->db->queryOneRecord("SELECT MIN(hfs_id) AS min_id FROM help_faq_sections");
	$hf_section = $res['min_id'];
}
$app->listform_actions->SQLExtWhere = "help_faq.hf_section = $hf_section";


if($hf_section) $res = $app->db->queryOneRecord("SELECT hfs_name FROM help_faq_sections WHERE hfs_id=?", $hf_section);
// Start the form rendering and action ahndling
echo "<h2>FAQ: ".$app->functions->htmlentities($res['hfs_name'])."</h2>";
if($hf_section) $app->listform_actions->onLoad();

?>
