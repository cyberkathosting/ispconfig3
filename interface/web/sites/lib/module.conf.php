<?php
$module = array (
  'name' => 'sites',
  'title' => 'Sites & Email',
  'template' => 'module.tpl.htm',
  'navframe_page' => '',
  'startpage' => 'sites/index.php',
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
          'link' => 'sites/mail_domain_list.php',
        ),
        1 => 
        array (
          'title' => 'Domain Alias',
          'target' => 'content',
          'link' => 'sites/mail_domain_alias_list.php',
        ),
        2 => 
        array (
          'title' => 'Domain Relay',
          'target' => 'content',
          'link' => 'sites/mail_domain_relay_list.php',
        ),
        3 => 
        array (
          'title' => 'Email Mailbox',
          'target' => 'content',
          'link' => 'sites/mail_box_list.php',
        ),
        4 => 
        array (
          'title' => 'Email Alias',
          'target' => 'content',
          'link' => 'sites/mail_alias_list.php',
        ),
        5 => 
        array (
          'title' => 'Email Forward',
          'target' => 'content',
          'link' => 'sites/mail_forward_list.php',
        ),
        6 => 
        array (
          'title' => 'Email Catchall',
          'target' => 'content',
          'link' => 'sites/mail_catchall_list.php',
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
          'title' => 'Email Whitelist',
          'target' => 'content',
          'link' => 'sites/mail_whitelist_list.php',
        ),
        1 => 
        array (
          'title' => 'Email Blacklist',
          'target' => 'content',
          'link' => 'sites/mail_blacklist_list.php',
        ),
      ),
    ),
  ),
)
?>