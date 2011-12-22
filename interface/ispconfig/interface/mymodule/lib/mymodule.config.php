<?php

//**** Module Definition ****

// Name of the module. The module name must match the name of the module directory.
// The module name may not contain spaces.
$module['name']      = 'mymodule';

// Title of the module which is dispalayed in the top navigation.
$module['title']     = 'Mymodule';

// The template file of the module. This is always 'module.tpl.htm' unless
// there are any special requirements such as a three column layout.
$module['template']  = 'module.tpl.htm';

// The page that is displayed when the module is loaded.
// The path must is relative to the web/ directory
$module['startpage'] = 'mymodule/index.php';

// The width of the tab. Normally you should leave this empty and
// let the browser define the width automatically.
$module['tab_width'] = '';

//****  Menu Definition ****

// Make sure that the items array is empty
$items = array();

// Add a menu item with the label 'Send message'
$items[] = array( 'title'   => 'Send message',
                  'target'  => 'content',
                  'link'    => 'mymodule/support_message_edit.php'
                );

// Add a menu item with the label 'View messages'
$items[] = array( 'title'   => 'View messages',
                  'target'  => 'content',
                  'link'    => 'mymodule/support_message_list.php'
                );

// Append the menu $items defined above to a menu section labeled 'Support'
$module['nav'][] = array( 'title' => 'Support',
                          'open'  => 1,
                          'items'	=> $items
                        );

?>