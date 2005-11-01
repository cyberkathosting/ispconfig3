<?php
$module = array (
  'name' => 'designer',
  'title' => 'BE Designer',
  'template' => 'module.tpl.htm',
  'navframe_page' => '',
  'startpage' => 'designer/module_list.php',
  'tab_width' => '',
  'nav' => 
  array (
    0 => 
    array (
      'title' => 'Modules',
      'open' => '1',
      'items' => 
      array (
        0 => 
        array (
          'title' => 'Add',
          'target' => 'content',
          'link' => 'designer/module_edit.php',
        ),
        1 => 
        array (
          'title' => 'Edit',
          'target' => 'content',
          'link' => 'designer/module_list.php',
        ),
      ),
    ),
    1 => 
    array (
      'title' => 'Formulare',
      'open' => '1',
      'items' => 
      array (
        2 => 
        array (
          'title' => 'Add',
          'target' => 'content',
          'link' => 'designer/form_edit.php',
        ),
        3 => 
        array (
          'title' => 'Edit',
          'target' => 'content',
          'link' => 'designer/form_list.php',
        ),
      ),
    ),
    2 => 
    array (
      'title' => 'Lists',
      'open' => '1',
      'items' => 
      array (
        4 => 
        array (
          'title' => 'Add',
          'target' => 'content',
          'link' => 'designer/list_edit.php',
        ),
        5 => 
        array (
          'title' => 'Edit',
          'target' => 'content',
          'link' => 'designer/list_list.php',
        ),
      ),
    ),
    3 => 
    array (
      'title' => 'Languages',
      'open' => '1',
      'items' => 
      array (
        6 => 
        array (
          'title' => 'Add',
          'target' => 'content',
          'link' => 'designer/lang_edit.php',
        ),
        7 => 
        array (
          'title' => 'Edit',
          'target' => 'content',
          'link' => 'designer/lang_list.php',
        ),
      ),
    ),
  ),
)
?>