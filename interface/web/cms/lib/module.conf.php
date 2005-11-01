<?php

$module["name"]				= "cms";
$module["title"]        	= "Media Manager";
$module["template"]     	= "module_tree.tpl.htm";
$module["startpage"]    	= "cms/media_list.php";
$module["navframe_page"]    = "cms/treenavi.php";
$module["tab_width"]    = ''; // Standard ist 100


$items[] = array( 'title'  => 'Neu',
                  'target' => 'content',
                  'link'   => 'cms/media_add.php?filenum=5&phpsessid='.$_SESSION["s"]["id"]);

$items[] = array( 'title'  => 'Bearbeiten',
                  'target' => 'content',
                  'link'   => 'cms/media_list.php?phpsessid='.$_SESSION["s"]["id"]);


                  $module["nav"][] = array(        'title'        => 'Media Inhalt',
                                                   'open'         => 1,
                                                   'items'        => $items);

// aufräumen
unset($items);

$items[] = array( 'title'  => 'Neu',
                  'target' => 'content',
                  'link'   => 'cms/media_profile_edit.php?phpsessid='.$_SESSION["s"]["id"]);

$items[] = array( 'title'  => 'Bearbeiten',
                  'target' => 'content',
                  'link'   => 'cms/media_profile_list.php?phpsessid='.$_SESSION["s"]["id"]);


                  $module["nav"][] = array(        'title'        => 'Media Profile',
                                                   'open'         => 1,
                                                   'items'        => $items);

// aufräumen
unset($items);

$items[] = array( 'title'  => 'Neu',
                  'target' => 'content',
                  'link'   => 'cms/media_cat_edit.php?phpsessid='.$_SESSION["s"]["id"]);

$items[] = array( 'title'  => 'Bearbeiten',
                  'target' => 'content',
                  'link'   => 'cms/media_cat_list.php?phpsessid='.$_SESSION["s"]["id"]);


                  $module["nav"][] = array(        'title'        => 'Media Kategorie',
                                                   'open'         => 1,
                                                   'items'        => $items);

// aufräumen
unset($items);


?>