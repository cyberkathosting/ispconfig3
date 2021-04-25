# Contributing to ISPConfig
ISPConfig is a open source project and community contributions are very welcome. To contribute, please stick to the guidelines.

This document is under development and will be continuously improved.

# Issues
* Before opening a new issue, use the search function to check if there isn't a bug report / feature request already.
* If you are reporting a bug, please share your OS and PHP (CLI) version.
* If you want to report several bugs or request several features, open a separate issue for each one of them.

# Branches
* If you are a new user, please send an email to: dev [at] ispconfig [dot] org to receive rights to fork the project.
* Please create an issue for each contribution you want to make.
* Do not put multiple contributions into a single branch and merge request. Each contribution should have it's own branch.
* Do not use the develop branch in your forked project for your contribution. Create a separate branch for each issue.
* Give your branch a name, e. g. `6049-update-the-contributing-doc ` where 6049 is the issue number.

# Merge requests
Please give your merge request a description that shortly states what it is about. Merge requests without a good title or with missing description will get delayed because it is more effort for us to check the meaning of the changes made.
Once again: Do not put multiple things into a single merge request. If you for example fix two issues where one affects apache and one mail users, use separate issues and separate merge requests.
You can group multiple issues in a single merge request if they have the same specific topic, e. g. if you have one issue stating that a language entry in mail users is missing and a second issue that a language entry for server config is missing, you can put both issues into a single branch and merge request. Be sure to include all issue ids (if multiple) into the merge request's description in this case.
* Open a issue for the bug you want to fix / the feature you want to implement
* After opening the issue, commit your changes to your branch
* Note the issue # in every commit
* Update the documentation (New devs will not have access to this. Please send a email to docs@ispconfig.org)
* Add translations for every language
* Use a short title
* Write a clear description - for example, when updating the contributing guidelines with issue #6049: \
"Update of our contributing guidelines \
Closes #6049"
* Please be aware that we are not able to accept merge request that do not stick to the coding guidelines. We need to insist on that to keep the code clean and maintainable.

# Some guidelines for web development with php.
-----------------------------------------------------
* Don't use features that are not supported in PHP 5.4, for compatibility with LTS OS releases, ISPConfig must support PHP 5.4+
* Don't use shorttags. A Shorttag is `<?` and that is confusing with `<?xml` -> always use `<?php`
* Don't use namespaces
* Column names in database tables and database table names are in lowercase
* Classes for the interface are located in interface/lib/classes/ and loaded with $app->uses() or $app->load() functions.
* Classes for the server are located in server/lib/classes/ and loaded with $app->uses() or $app->load() functions.

### Indentations

Indentations are always done with tabs. Do **not** use spaces.
It is recommended to set your IDE to display tabs with a width of 4 spaces.

### Variable and method / function names

Methods and functions should always be written in camel-case. Variables and properties should always be lowercase instead.

**Correct:**
```php
class MyClass {
    private $issue_list = [];

    private function getMyValue() {

    }
}
```

**Wrong:**
```php
class my_class {
    private $IssueList = [];

    private function get_my_value() {

    }
}
```

### Blocks

#### Curly braces

Opening curly braces always have to be in the same line as the preceding condition. They are separated by a single space from the closing paranthesis.
Closing curly braces are always on a separate line after the last statement in the block. The only exception is a do-while block where the logic is inverted.

Curly braces are **always** to be used. Do not leave them out, even if there is only a single statement in the corresponding block.

**Correct:**
```php
if($variable === true) {

}

while($condition) {

}

do {

} while($condition);
```

**Wrong:**
```php
if($variable === true){

}

if($variable === true)
{

}

if($variable === true)
   $x = 'no braces';

while($condition) { }
```

#### Short style

The short style of conditional assignments is allowed to be used, but it must not affect readability, e. g. they shall not be nested.

**Allowed:**
```php
$a = 0;
if($condition === true) {
    $a = 1;
}

$a = ($condition === true ? 1 : 0);
```

**Disallowed:**
```php
$x = ($condition === true ? ($further == 'foo' ? true : false) : true);
```


#### Spaces and paranthesis

The rules for using spaces are:
- no space after `if`/`while` etc. and the following opening paranthesis
- single space after closing paranthesis and before opening curly brace
- no spaces at the end of a line
- no spaces after opening paranthesis and before closing paranthesis
- single space before and after comparators

**Correct:**
```php
if($variable === $condition) {

}

while(($condition !== false || $condition2 === true) && $n <= 15) {
    $n++;
}
```

**Wrong:**
```php
if ($variable===$condition) {

}

while(($condition!==false||$condition2===true))&&$n<=15){

}
```

#### Newlines inside of conditions

Breaking up conditions into separate lines can be done if it positively affects readability.

```php
if($condition === true && ($state === 'completed' || $state === 'pending') && ($processed_by !== null || $process_time < time())) {

}
```
can also be written as
```php
if($condition === true
    && ($state === 'completed' || $state === 'pending')
    && ($processed_by !== null || $process_time < time())
    ) {

}
```
This must not be abused, e. g. the following is not allowed:

```php
if($a == 1
    || $b == 2) {

    }
```

### Arrays

#### Short syntax

Please **do** use short array syntax. We have deprecated the old-style array syntax.

**Correct**:
```php
$var = [];

$var2 = [
    'conf' => [
        'setting1' => 'value1'
    ]
];
```

**Wrong:**
```php
$var = array();

$var2 = array(
    'conf' => array(
        'setting1' => 'value1'
    )
);
```

#### Spaces and newlines

When defining an empty array, both brackets shall be on the same line. When defining an array with values, the style depends on the values you are going to assign.

##### List of values

When defining an array with a list of values, e. g. numbers or names, they should be on the same line as the brackets without using new lines, as long as the line does not exceed a total number of characters of about 90. After each comma there has to be a single space.

##### Nested array

When defining a nested array onle the opening bracket is to be on the same line. The closing bracket has to be on a separate line indented by `tabs * level of array`.

##### Examples

```php
// empty array
$a = [];

// array with list of values
$array = [4, 3, 76, 12];

// array with long list of values
$array = [
    'This is one entry', 'This is a second one', 'Another one', 'Further entries', 'foo', 'bar', 34, 42, $variable, // newline here for better readability
    'Next entry', 'the last entry'
];

// nested array
$array = [
    'conf' => [
        'level' => 1,
        'settings' => [
            'window' => 'open',
            'door' => 'closed
        ]
    ]
];
```

**Not-to-dos:**
```php
$array=[
];

$array = [
    1,
    4,
    35,
    23,
    345,
    11,
    221,
    'further',
    '...'
];

$array=['conf'=>['settings'=>['window' => 'open', 'door' => 'closed]]];
```

### Strings 

Whenever possible use single quotes `'` instead of double qoutes `"`. Try not to embedd variables in string. Concatenate them instead.

**Correct:**
```php
// simple text
$var = 'This is a text';

// array index
$array['index'] = 'value';

// text with variables
$var = 'This is a text with ' . $value . ' values inside and at the end: ' . $sum_value;

// dynamic array index
$idx = 'index' . $key;
$value = $array[$idx];
```

**Wrong:**
```php
// simple text
$var = "This is a text";

// array index
$array["index"] = 'value';

// text with variables
$var = "This is a text with $value values inside and at the end: {$sum_value}";

// dynamic array index
$value = $array['index' . $key];
$value = $array["index{$key}"];
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
