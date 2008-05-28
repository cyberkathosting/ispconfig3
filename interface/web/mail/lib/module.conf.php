<?php

$module['name'] 		= 'mail';
$module['title'] 		= 'Email';
$module['template'] 	= 'module.tpl.htm';
$module['startpage'] 	= 'mail/mail_domain_list.php';
$module['tab_width']    = '';


//**** Email accounts menu
$items = array();

$items[] = array( 'title' 	=> 'Domain',
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_domain_list.php');

$items[] = array( 'title' 	=> 'Email Mailbox',
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_user_list.php');

$items[] = array( 'title' 	=> 'Email Alias',
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_alias_list.php');			  

$items[] = array( 'title' 	=> 'Email Forward',
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_forward_list.php');

$items[] = array( 'title' 	=> 'Email Catchall',
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_domain_catchall_list.php');

$items[] = array( 'title' 	=> 'Email Routing',
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_transport_list.php');

$module['nav'][] = array(	'title'	=> 'Email Accounts',
							'open' 	=> 1,
							'items'	=> $items);

//**** Spamfilter menu
$items = array();

$items[] = array( 'title' 	=> 'Whitelist',
				  'target' 	=> 'content',
				  'link'	=> 'mail/spamfilter_whitelist_list.php');

$items[] = array( 'title' 	=> 'Blacklist',
				  'target' 	=> 'content',
				  'link'	=> 'mail/spamfilter_blacklist_list.php');

if($_SESSION['s']['user']['typ'] == 'admin') {

	$items[] = array( 	'title' 	=> 'User / Domain',
				  		'target' 	=> 'content',
				  		'link'	    => 'mail/spamfilter_users_list.php');

	$items[] = array( 	'title' 	=> 'Policy',
				  		'target' 	=> 'content',
				  		'link'	    => 'mail/spamfilter_policy_list.php');
						
	$items[] = array( 	'title' 	=> 'Server Settings',
				  		'target' 	=> 'content',
				  		'link'	    => 'mail/spamfilter_config_list.php');
}

$module['nav'][] = array(	'title'	=> 'Spamfilter',
							'open' 	=> 1,
							'items'	=> $items);

//**** Fetchmail menu
$items = array();

$items[] = array( 'title' 	=> 'Fetchmail',
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_get_list.php');


$module['nav'][] = array(	'title'	=> 'Fetchmail',
							'open' 	=> 1,
							'items'	=> $items);

//**** Statistics menu
$items = array();

$items[] = array( 'title' 	=> 'Mailboxes',
				  'target' 	=> 'content',
				  'link'	=> 'mail/mail_user_stats.php');


$module['nav'][] = array(	'title'	=> 'Statistics',
							'open' 	=> 1,
							'items'	=> $items);
							
							
//**** Global filters menu
$items = array();
if($_SESSION['s']['user']['typ'] == 'admin') {

	$items[] = array(   'title' 	=> 'Postfix Whitelist',
  				  	    'target' 	=> 'content',
				  	    'link'	    => 'mail/mail_whitelist_list.php');

	$items[] = array(   'title' 	=> 'Postfix Blacklist',
				 	    'target' 	=> 'content',
				  	    'link'	    => 'mail/mail_blacklist_list.php');
	
	$items[] = array(   'title' 	=> 'Content Filter',
				 	    'target' 	=> 'content',
				  	    'link'	    => 'mail/mail_content_filter_list.php');

	$module['nav'][] = array(	'title'	=> 'Global Filters',
								'open' 	=> 1,
								'items'	=> $items);
}

?>