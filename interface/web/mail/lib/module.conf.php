<?php
$module = array (
  'name' => 'mail',
  'title' => 'Email',
  'template' => 'module.tpl.htm',
  'navframe_page' => '',
  'startpage' => 'mail/mail_domain_list.php',
  'tab_width' => '',
  'nav' => 
  array (
    0 => 
    array (
      'title' => 'Email Accounts',
      'open' => 1,
      'items' => 
      array (
        0 => 
        array (
          'title' => 'Domain',
          'target' => 'content',
          'link' => 'mail/mail_domain_list.php',
        ),
        3 => 
        array (
          'title' => 'Email Mailbox',
          'target' => 'content',
          'link' => 'mail/mail_user_list.php',
        ),
        4 => 
        array (
          'title' => 'Email Alias',
          'target' => 'content',
          'link' => 'mail/mail_alias_list.php',
        ),
        5 => 
        array (
          'title' => 'Email Forward',
          'target' => 'content',
          'link' => 'mail/mail_forward_list.php',
        ),
        6 => 
        array (
          'title' => 'Email Catchall',
          'target' => 'content',
          'link' => 'mail/mail_domain_catchall_list.php',
        ),
        7 => 
        array (
          'title' => 'Email Routing',
          'target' => 'content',
          'link' => 'mail/mail_transport_list.php',
        ),
      ),
    ),
    1 => 
    array (
      'title' => 'Email Filter',
      'open' => 1,
      'items' => 
      array (
        0 => 
        array (
          'title' => 'Whitelist',
          'target' => 'content',
          'link' => 'mail/mail_whitelist_list.php',
        ),
        1 => 
        array (
          'title' => 'Blacklist',
          'target' => 'content',
          'link' => 'mail/mail_blacklist_list.php',
        ),
      ),
    ),
    2 => 
    array (
      'title' => 'Spamfilter',
      'open' => 1,
      'items' => 
      array (
        0 => 
        array (
          'title' => 'Whitelist',
          'target' => 'content',
          'link' => 'mail/spamfilter_whitelist_list.php',
        ),
        1 => 
        array (
          'title' => 'Blacklist',
          'target' => 'content',
          'link' => 'mail/spamfilter_blacklist_list.php',
        ),
        2 => 
        array (
          'title' => 'User',
          'target' => 'content',
          'link' => 'mail/spamfilter_users_list.php',
        ),
        3 => 
        array (
          'title' => 'Policy',
          'target' => 'content',
          'link' => 'mail/spamfilter_policy_list.php',
        ),
      ),
    ),
    3 => 
    array (
      'title' => 'Fetchmail',
      'open' => 1,
      'items' => 
      array (
        0 => 
        array (
          'title' => 'Fetchmail Accounts',
          'target' => 'content',
          'link' => 'mail/fetchmail_list.php',
        ),
      ),
    ),
  ),
)
?>