<?php
/*
Copyright (c) 2012, ISPConfig UG
Contributors: web wack creations,  http://www.web-wack.at
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

$liste['name'] = 'aps_packages'; // Name of the list
$liste['table'] = 'aps_packages'; // Database table
$liste['table_idx'] = 'id'; // Table index
$liste["search_prefix"] = 'search_'; // Search field prefix
$liste['records_per_page'] = 15; // Records per page
$liste['file'] = 'aps_availablepackages_list.php'; // Script file for this list
$liste['edit_file']    = ''; // Script file to edit
$liste['delete_file'] = ''; // Script file to delete
$liste['paging_tpl'] = 'templates/paging.tpl.htm'; // Paging template
$liste['auth'] = 'no'; // Handling it myself (check for admin)

// Search fields
$liste["item"][] = array('field'    => 'name',
	'datatype' => 'VARCHAR',
	'formtype' => 'TEXT',
	'op'       => 'like',
	'prefix'   => '%',
	'suffix'   => '%',
	'width'    => '',
	'value'    => '');

$liste["item"][] = array('field'    => 'version',
	'datatype' => 'VARCHAR',
	'formtype' => 'TEXT',
	'op'       => 'like',
	'prefix'   => '%',
	'suffix'   => '%',
	'width'    => '',
	'value'    => '');

$liste["item"][] = array('field'    => 'category',
	'datatype' => 'VARCHAR',
	'formtype' => 'SELECT',
	'op'       => '=',
	'prefix'   => '',
	'suffix'   => '',
	'datasource' => array('type' => 'SQL',
		'querystring' => 'SELECT category FROM aps_packages ORDER BY category',
		'keyfield' => 'category',
		'valuefield' => 'category'),
	'width'    => '',
	'value'    => '');

if($_SESSION['s']['user']['typ'] == 'admin')
{
	$liste['item'][] = array('field'    => 'package_status',
		'datatype' => 'VARCHAR',
		'formtype' => 'SELECT',
		'op'       => '=',
		'prefix'   => '',
		'suffix'   => '',
		'width'    => '',
		'value'    => array(PACKAGE_ENABLED => $app->lng('Yes'),
			PACKAGE_LOCKED => $app->lng('No')));
}
?>
