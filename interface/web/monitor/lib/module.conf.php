<?php

/*
 Config of the Module
 */
$module["name"] 		= "monitor";
$module["title"] 		= "Monitor";
$module["template"] 	= "module.tpl.htm";
$module["tab_width"]    = '';
$module["startpage"] 	= "monitor/show_data.php?type=overview";

/*
 We need all the available servers on the left navigation.
 So fetch them from the database and add then to the navigation as dropdown-list
*/
$servers = $app->db->queryAllRecords("SELECT server_id, server_name FROM server order by server_name");

$dropDown = "<select id='server_id' onchange=\"loadContent('monitor/show_data.php?type=overview&server=' + document.getElementById('server_id').value);\">";
foreach ($servers as $server)
{
  $dropDown .= "<option value='" . $server['server_id'] . "|" . $server['server_name'] . "'>" . $server['server_name'] . "</option>";
}
$dropDown .= "</select>";

/*
 Now add them as dropdown to the navigation
 */
$items[] = array( 'title' 	=> $dropDown,
		'target' 	=> '', // no action!
		'link'	=> '');   // no action!

$module["nav"][] = array(	'title'	=> 'Server to Monitor',
		'open' 	=> 1,
		'items'	=> $items);
		
/*
  The first Server at the list is the server first selected
 */
$_SESSION['monitor']['server_id']   = $servers[0]['server_id'];
$_SESSION['monitor']['server_name'] = $servers[0]['server_name'];

/*
	Logmonitoring module
*/
// aufräumen
unset($items);
$items[] = array( 'title' 	=> "Server Load",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_data.php?type=server_load');

$items[] = array( 'title' 	=> "Disk usage",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_data.php?type=disk_usage');

$items[] = array( 'title' 	=> "Memory usage",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_data.php?type=mem_usage');

$items[] = array( 'title' 	=> "Services",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_data.php?type=services');


$module["nav"][] = array(	'title'	=> 'Monitoring',
							'open' 	=> 1,
							'items'	=> $items);

// aufräumen
unset($items);

$items[] = array( 'title' 	=> "CPU",
		'target' 	=> 'content',
		'link'	=> 'monitor/show_data.php?type=cpu_info');

$module["nav"][] = array(	'title'	=> 'System-Information',
		'open' 	=> 1,
		'items'	=> $items);

// aufräumen
unset($items);


/*
	Logmonitoring module
*/

$items[] = array( 'title' 	=> "Mail log",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_log.php?log=log_mail');

$items[] = array( 'title' 	=> "Mail warn",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_log.php?log=log_mail_warn');

$items[] = array( 'title' 	=> "Mail err",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_log.php?log=log_mail_err');

$items[] = array( 'title' 	=> "Messages",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_log.php?log=log_messages');

$items[] = array( 'title' 	=> "Freshclam",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_log.php?log=log_freshclam');

$items[] = array( 'title' 	=> "Clamav",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_log.php?log=log_clamav');

$items[] = array( 'title' 	=> "ISPConfig",
				  'target' 	=> 'content',
				  'link'	=> 'monitor/show_log.php?log=log_ispconfig');


$module["nav"][] = array(	'title'	=> 'Logfiles',
							'open' 	=> 1,
							'items'	=> $items);

// aufräumen
unset($items);
?>