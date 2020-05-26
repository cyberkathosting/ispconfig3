# Which code branch to use

The master branch is used for code (mostly new features) that shall go into the next major release (e.g. 3.2, 3.3 and so on). The stable branch (e.g. stable-3.1, stable-3.2) is the branch for the current intermediate and bugfix releases. Bugfixes shall be committed to the current stable branch and not the master branch. The stable branch is merged to the master automatically from time to time, please do not submit bugfixes a second time against the master.

# Some guidelines for web development with php.
-----------------------------------------------------
* Unix Line Breaks Only, NO windows breaks please.
* Tabs to indent lines, NO spaces
* no accidental _<?php space before, within or after a file
* every PHP file starts and end with <?php ?> no spaces before or after
* error_reporting(E_ALL|E_STRICT), yep PHP 5
* Magic quotes is gone, get used to it now. config = magic_quotes_gpc() Everything must be quoted
* Don't use ereg, split and other old function -> gone in PHP 5.4
* Don't use features that are not supported in PHP 5.3, for compatibility with LTS OS releases, ISPConfig must support PHP 5.3+
* Don't use shorttags. A Shorttag is <? and that is confusing with <?xml -> always use <?php
* Don't use namespaces
* Column names in database tables and database table names are in lowercase
* Classes for the interface are located in interface/lib/classes/ and loaded with $app->uses() or $app->load() functions.
* Classes for the server are located in server/lib/classes/ and loaded with $app->uses() or $app->load() functions.
* please mark any section that need review or work on with /* TODO: short description */
* Make function / var names on the following way, first word lower, next word(s) first letter upper like. getFirstResult();
* always a space but NO newline before opening braces, e. g.
```
class abc {
	public function cde() {
		if($a == $b) {
			return false;
		}
	}
}
```
* no spaces after function/method or control names, e. g.
```
function abc($x, $y) {
	if($condition == true) {
		$x = 2;
	}
}
```
and NOT
```
function abc ($x, $y) {
	if ( $condition == true ) {
	
	}
}
```

# Commenting style

The comments break down into the following types
```
// is uses for removing lines and debug dev etc
/* 
	is used to comment out blocks
*/

/** is used to create documentaion
 * thats over 
 * lines
 */
```
If you need to block out a section then use
```
/*
function redundant_code(){
	something here
}
*/
```
To block out single lines use // and all // are assumed to be redundant test code and NOT comments

// print_r($foo);

Do not use the phpdoc on every function, eg 
```
/**
* Login a user
* @param string user  username
* @param string password of user
*/
function login($user, $pass){
	
}
```
as this function is self-explaining, the following clean code will suffice
```
function login($user, $pass){
	
}
```

# Where to store custom settings

## Interface settings

The recommended place to store global interface settings is the ini style global config system 
(see system.ini.master file in install/tpl/ to set defaults). The settings file 
gets stored inside the ispconfig database. Settings can be accessed with the function:

```
$app->uses('ini_parser,getconf');
$interface_settings = $app->getconf->get_global_config('modulename');
```

where modulename corresponds to the config section in the system.ini.master file.
To make the settings editable under System > interface config, add the new configuration
fields to the file interface/web/admin/form/system_config.tform.php and the corresponding
tempalte file in the templates subfolder of the admin module.

## Server settings

Server settings are stored in the ini style server config system (see server.ini.master template file)
The settings file gets stored inside the ispconfig database in the server table. Settings can be 
accessed with the function $app->getconf->get_server_config(....)

Example to access the web configuration:

```
$app->uses('ini_parser,getconf');
$web_config = $app->getconf->get_server_config($server_id,'web');
```

# Learn about the form validators
There are form validators in interface/lib/classes/tform.inc.php to make validating forms easier.
Read about: REGEX,UNIQUE,NOTEMPTY,ISEMAIL,ISINT,ISPOSITIVE,ISIPV4,ISIPV6,ISIP,CUSTOM
