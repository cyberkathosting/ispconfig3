<?php

$module["name"] 		= "mail";
$module["title"] 		= "Email";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "mail/mail_domain_list.php";
$module["tab_width"]    = '';

/*
	Email accounts menu
*/

$items[] = array( 'title' 	=> "Domain",
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_domain_list.php');

$items[] = array( 'title' 	=> "Email Mailbox",
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_user_list.php');

$items[] = array( 'title' 	=> "Email Alias",
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_alias_list.php');			  

$items[] = array( 'title' 	=> "Email Forward",
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_forward_list.php');

$items[] = array( 'title' 	=> "Email Catchall",
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_domain_catchall_list.php');

$items[] = array( 'title' 	=> "Email Routing",
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_transport_list.php');

$module["nav"][] = array(	'title'	=> 'Email Accounts',
							'open' 	=> 1,
							'items'	=> $items);

// aufräumen
unset($items);

/*
	Spamfilter menu
*/

$items[] = array( 'title' 	=> "Whitelist",
				  'target' 	=> 'content',
				  'link'	=> 'mail/spamfilter_whitelist_list.php');

$items[] = array( 'title' 	=> "Blacklist",
				  'target' 	=> 'content',
				  'link'	=> 'mail/spamfilter_blacklist_list.php');

if($_SESSION["s"]["user"]["typ"] == 'admin') {

	$items[] = array( 	'title' 	=> "User / Domain",
				  		'target' 	=> 'content',
				  		'link'	=> 'mail/spamfilter_users_list.php');

	$items[] = array( 	'title' 	=> "Policy",
				  		'target' 	=> 'content',
				  		'link'	=> 'mail/spamfilter_policy_list.php');
}

$module["nav"][] = array(	'title'	=> 'Spamfilter',
							'open' 	=> 1,
							'items'	=> $items);

// aufräumen
unset($items);

/*
	Fetchmail menu
*/


$items[] = array( 'title' 	=> "Fetchmail",
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_get_list.php');


$module["nav"][] = array(	'title'	=> 'Fetchmail',
							'open' 	=> 1,
							'items'	=> $items);

// aufräumen
unset($items);

/*
	Global filters menu
*/

if($_SESSION["s"]["user"]["typ"] == 'admin') {

	$items[] = array( 'title' 	=> "Whitelist",
				  	'target' 	=> 'content',
				  	'link'	=> 'mail/mail_whitelist_list.php');

	$items[] = array( 'title' 	=> "Blacklist",
				 	 'target' 	=> 'content',
				  	 'link'	=> 'mail/mail_blacklist_list.php');

	$module["nav"][] = array(	'title'	=> 'Global Filters',
								'open' 	=> 1,
								'items'	=> $items);

	// aufräumen
	unset($items);
}


?>