<?php
$module = array (
  'name' => 'resellers',
  'title' => 'Resellers',
  'template' => 'module.tpl.htm',
  'navframe_page' => '',
  'startpage' => 'resellers/reseller_list.php',
  'tab_width' => '',
  'nav' => 
  array (
    0 => 
    array (
      'title' => 'Resellers',
      'open' => 1,
      'items' => 
      array (
        0 => 
        array (
          'title' => 'Add Reseller',
          'target' => 'content',
          'link' => 'resellers/reseller_edit.php',
        ),
        1 => 
        array (
          'title' => 'Edit Reseller',
          'target' => 'content',
          'link' => 'resellers/reseller_list.php',
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