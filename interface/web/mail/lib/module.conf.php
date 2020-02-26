<?php

$userid=$app->auth->get_user_id();

$module['name']   = 'mail';
$module['title']   = 'top_menu_email';
$module['template']  = 'module.tpl.htm';
$module['startpage']  = 'mail/mail_domain_list.php';
$module['tab_width']    = '';
$module['order']    = '40';


//**** Email accounts menu
$items = array();

if($app->auth->get_client_limit($userid, 'maildomain') != 0)
{
	$items[] = array( 'title'  => 'Domain',
		'target'  => 'content',
		'link' => 'mail/mail_domain_list.php',
		'html_id' => 'mail_domain_list');
}

if($app->auth->get_client_limit($userid, 'mailaliasdomain') != 0)
{
	$items[] = array( 'title'  => 'Domain Alias',
		'target'  => 'content',
		'link' => 'mail/mail_aliasdomain_list.php',
		'html_id' => 'mail_aliasdomain_list');
}

if($app->auth->get_client_limit($userid, 'mailbox') != 0)
{
	$items[] = array( 'title'  => 'Email Mailbox',
		'target'  => 'content',
		'link' => 'mail/mail_user_list.php',
		'html_id' => 'mail_user_list');
}

if($app->auth->get_client_limit($userid, 'mailalias') != 0)
{
	$items[] = array( 'title'  => 'Email Alias',
		'target'  => 'content',
		'link' => 'mail/mail_alias_list.php',
		'html_id' => 'mail_alias_list');
}

if($app->auth->get_client_limit($userid, 'mailforward') != 0)
{
	$items[] = array( 'title'  => 'Email Forward',
		'target'  => 'content',
		'link' => 'mail/mail_forward_list.php',
		'html_id' => 'mail_forward_list');
}

if($app->auth->get_client_limit($userid, 'mailcatchall') != 0)
{
	$items[] = array( 'title'  => 'Email Catchall',
		'target'  => 'content',
		'link' => 'mail/mail_domain_catchall_list.php',
		'html_id' => 'mail_domain_catchall_list');
}

if($app->auth->get_client_limit($userid, 'mailrouting') != 0)
{
	$items[] = array( 'title'  => 'Email Routing',
		'target'  => 'content',
		'link' => 'mail/mail_transport_list.php',
		'html_id' => 'mail_transport_list');
}

if(count($items) && $app->system->has_service($userid, 'mail'))
{
	$module['nav'][] = array( 'title' => 'Email Accounts',
		'open'  => 1,
		'items' => $items);
}

//**** Mailinglist menu
$items = array();

if($app->auth->get_client_limit($userid, 'mailmailinglist') != 0)
{
	$items[] = array( 'title'  => 'Mailing List',
		'target'  => 'content',
		'link' => 'mail/mail_mailinglist_list.php',
		'html_id' => 'mail_mailinglist_list');
}

if(count($items) && $app->system->has_service($userid, 'mail'))
{
	$module['nav'][] = array( 'title' => 'Mailing List',
		'open'  => 1,
		'items' => $items);
}

//**** Spamfilter menu
$items = array();

if($app->auth->get_client_limit($userid, 'spamfilter_wblist') != 0)
{
	$items[] = array( 'title'  => 'Whitelist',
		'target'  => 'content',
		'link' => 'mail/spamfilter_whitelist_list.php',
		'html_id' => 'spamfilter_whitelist_list');

	$items[] = array( 'title'  => 'Blacklist',
		'target'  => 'content',
		'link' => 'mail/spamfilter_blacklist_list.php',
		'html_id' => 'spamfilter_blacklist_list');
}

if($app->auth->is_admin()) {

	$items[] = array(  'title'  => 'User / Domain',
		'target'  => 'content',
		'link'     => 'mail/spamfilter_users_list.php',
		'html_id' => 'spamfilter_users_list');

	$items[] = array(  'title'  => 'Policy',
		'target'  => 'content',
		'link'     => 'mail/spamfilter_policy_list.php',
		'html_id' => 'spamfilter_policy_list');

	// $items[] = array(  'title'  => 'Server Settings',
	//        'target'  => 'content',
	//        'link'     => 'mail/spamfilter_config_list.php');
}

if(count($items))
{
	$module['nav'][] = array( 'title' => 'Spamfilter',
		'open'  => 1,
		'items' => $items);
}

//**** Fetchmail menu
$items = array();

if($app->auth->get_client_limit($userid, 'fetchmail') != 0)
{
	$items[] = array( 'title'  => 'Fetchmail',
		'target'  => 'content',
		'link' => 'mail/mail_get_list.php',
		'html_id' => 'mail_get_list');

	$module['nav'][] = array( 'title' => 'Fetchmail',
		'open'  => 1,
		'items' => $items);
}

//**** XMPP Menu
$items = array();

if($app->auth->get_client_limit($userid, 'xmpp_domain') != 0)
{
    $items[] = array( 'title'  => 'XMPP Domain',
        'target'  => 'content',
        'link' => 'mail/xmpp_domain_list.php',
        'html_id' => 'xmpp_domain_list');
}

if($app->auth->get_client_limit($userid, 'xmpp_user') != 0)
{
    $items[] = array( 'title'  => 'XMPP Account',
        'target'  => 'content',
        'link' => 'mail/xmpp_user_list.php',
        'html_id' => 'xmpp_user_list');
}

if(count($items) && $app->system->has_service($userid, 'xmpp'))
    $module['nav'][] = array( 'title' => 'Jabber / XMPP',
        'open'  => 1,
        'items' => $items);



//**** Statistics menu
$items = array();


//** Email Quotas
$items[] = array( 'title'   => 'Mailbox quota',
	'target'  => 'content',
	'link'    => 'mail/user_quota_stats.php',
	'html_id' => 'user_quota_stats');

$items[] = array( 'title'  => 'Mailbox traffic',
	'target'  => 'content',
	'link' => 'mail/mail_user_stats.php',
	'html_id' => 'mail_user_stats');
if($app->auth->get_client_limit($userid, 'backup') == 'y') {
        $items[] = array (
            'title'   => 'Backup Stats',
            'target'  => 'content',
            'link'    => 'mail/backup_stats.php',
            'html_id' => 'backup_stats');
}

$module['nav'][] = array( 'title' => 'Statistics',
	'open'  => 1,
	'items' => $items);


//**** Global filters menu
$items = array();
if($_SESSION['s']['user']['typ'] == 'admin') {

	$items[] = array(   'title'  => 'Postfix Whitelist',
		'target'  => 'content',
		'link'     => 'mail/mail_whitelist_list.php',
		'html_id' => 'mail_whitelist_list');


	$items[] = array(   'title'  => 'Postfix Blacklist',
		'target'  => 'content',
		'link'     => 'mail/mail_blacklist_list.php',
		'html_id' => 'mail_blacklist_list');


	$items[] = array(   'title'  => 'Content Filter',
		'target'  => 'content',
		'link'     => 'mail/mail_content_filter_list.php',
		'html_id' => 'mail_content_filter_list');


	$items[] = array(   'title'  => 'Relay Recipients',
		'target'  => 'content',
		'link'     => 'mail/mail_relay_recipient_list.php',
		'html_id' => 'mail_relay_recipient_list');


	$module['nav'][] = array( 'title' => 'Global Filters',
		'open'  => 1,
		'items' => $items);
}
?>
