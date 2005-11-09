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
          'link' => '',
        ),
        2 => 
        array (
          'title' => 'Email Mailbox',
          'target' => 'content',
          'link' => '',
        ),
        3 => 
        array (
          'title' => 'Email Forward',
          'target' => 'content',
          'link' => '',
        ),
        4 => 
        array (
          'title' => 'Domain Catchall',
          'target' => 'content',
          'link' => '',
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
          'link' => '',
        ),
        1 => 
        array (
          'title' => 'Email Blacklist',
          'target' => 'content',
          'link' => '',
        ),
      ),
    ),
  ),
)
?>