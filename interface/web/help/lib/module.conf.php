<?php

//* Name of the module. The module name must match the name of the module directory. The module name may not contain spaces.
$module['name']      = 'help';

//* Title of the module. The title is dispalayed in the top navigation.
$module['title']     = 'top_menu_help';

//* The template file of the module. This is always module.tpl.htm if you do not have any special requirements like a 3 column layout.
$module['template']  = 'module.tpl.htm';

//* The page that is displayed when the module is loaded. the path must is relative to the web directory
if(isset($_GET['go2_faq_sections_list'])){
	$module['startpage'] = 'help/faq_sections_list.php';
} else {
	if($_SESSION['s']['user']['typ'] == 'admin') {
		$module['startpage'] = 'help/version.php';
	} else {
		$module['startpage'] = 'help/support_message_list.php';
	}
}

//* The width of the tab. Normally you should leave this empty and let the browser define the width automatically.
$module['tab_width'] = '';


//*** Menu Definition *****************************************

//* make sure that the items array is empty
$items = array();

//* Add a menu item with the label 'Send message'
$items[] = array( 'title'   => 'Send message',
	'target'  => 'content',
	'link'    => 'help/support_message_edit.php',
	'html_id' => 'help_message_send');

//* Add a menu item with the label 'View messages'
$items[] = array( 'title'   => 'View messages',
	'target'  => 'content',
	'link'    => 'help/support_message_list.php',
	'html_id' => 'help_message_list');


//* Add the menu items defined above to a menu section labeled 'Support'
$module['nav'][] = array( 'title' => 'Support',
	'open'  => 1,
	'items' => $items);

//* the FAQ menu section
$itemsfaq = array();
//* admin's tools
if($_SESSION['s']['user']['typ'] == 'admin') {
	$itemsfaq[] = array(  'title'  => 'Manage Sections',
		'target' => 'content',
		'link'  => 'help/faq_sections_list.php');
	$itemsfaq[] = array(  'title'  => 'Manage Questions',
		'target' => 'content',
		'link'  => 'help/faq_manage_questions_list.php');

	$module['nav'][] = array(  'title' => 'FAQ',
		'open' => 1,
		'items' => $itemsfaq);
}
else
	{ //* the user
	$sql = "SELECT * FROM help_faq_sections";
	$res = $app->db->queryAllRecords($sql);
	//* all the content sections
	if(is_array($res) && !empty($res)) {
		foreach($res as $v) {
			$itemsfaq[] = array(  'title'  => $v['hfs_name'],
				'target' => 'content',
				'link'  => 'help/faq_list.php?hfs_id='.$v['hfs_id']);
		}
		// Display 'FAQ' menu only if there are sections
		$module['nav'][] = array(  'title' => 'FAQ',
			'open' => 1,
			'items' => $itemsfaq);
	}
}
//* -- end of the FAQ menu section



if($_SESSION['s']['user']['typ'] == 'admin') {
	//* make sure that the items array is empty
	$items = array();

	//* Add a menu item with the label 'Version'
	$items[] = array( 'title'   => 'Version',
		'target'  => 'content',
		'link'    => 'help/version.php',
		'html_id' => 'help_version' );


	//* Add the menu items defined above to a menu section labeled 'Support'
	$module['nav'][] = array( 'title' => 'About ISPConfig',
		'open'  => 1,
		'items' => $items);

}



?>
