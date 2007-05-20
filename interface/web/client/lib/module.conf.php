<?php
$module = array (
  'name' => 'client',
  'title' => 'Client',
  'template' => 'module.tpl.htm',
  'navframe_page' => '',
  'startpage' => 'client/client_list.php',
  'tab_width' => '',
  'nav' => 
  array (
    0 => 
    array (
      'title' => 'Clients',
      'open' => 1,
      'items' => 
      array (
        0 => 
        array (
          'title' => 'Add Client',
          'target' => 'content',
          'link' => 'client/client_edit.php',
        ),
        1 => 
        array (
          'title' => 'Edit Client',
          'target' => 'content',
          'link' => 'client/client_list.php',
        ),
      ),
    ),
    1 => 
    array (
      'title' => 'Statistics',
      'open' => 1,
      'items' => 
      array (
      ),
    ),
    2 => 
    array (
      'title' => 'Invoices',
      'open' => 1,
      'items' => 
      array (
      ),
    ),
    3 => 
    array (
      'title' => 'Mailings',
      'open' => 1,
      'items' => 
      array (
      ),
    ),
  ),
)
?>