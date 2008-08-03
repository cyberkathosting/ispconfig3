<?php

$module["name"] 		= "monitor";
$module["title"] 		= "Monitor";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "monitor/system.php?mod=index";
$module["tab_width"]    = '';

/*
	Logmonitoring module
*/

$items[] = array( 'title' 	=> "Load",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/system.php?mod=load');

$items[] = array( 'title' 	=> "Harddisk",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/system.php?mod=disk');

$items[] = array( 'title' 	=> "Memory usage",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/system.php?mod=memusage');

$items[] = array( 'title' 	=> "CPU",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/system.php?mod=cpu');

$items[] = array( 'title' 	=> "Services",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/system.php?mod=services');


$module["nav"][] = array(	'title'	=> 'System',
							'open' 	=> 1,
							'items'	=> $items);

// aufräumen
unset($items);

/*
	Logmonitoring module
*/

$items[] = array( 'title' 	=> "Mail log",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/logview.php?log=mail_log');

$items[] = array( 'title' 	=> "Mail warn",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/logview.php?log=mail_warn');

$items[] = array( 'title' 	=> "Mail err",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/logview.php?log=mail_err');

$items[] = array( 'title' 	=> "Messages",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/logview.php?log=messages');

$items[] = array( 'title' 	=> "Freshclam",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/logview.php?log=freshclam');

$items[] = array( 'title' 	=> "Clamav",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/logview.php?log=clamav');

$items[] = array( 'title' 	=> "ISPConfig",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/logview.php?log=ispconfig');


$module["nav"][] = array(	'title'	=> 'Logfiles',
							'open' 	=> 1,
							'items'	=> $items);

// aufräumen
unset($items);
?>