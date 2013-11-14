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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';
//require_once('classes/class.base.php'); // for constants
$app->load('aps_base');

// Path to the list definition file
$list_def_file = "list/aps_availablepackages.list.php";

// Check the module permissions
$app->auth->check_module_permissions('sites');

// Load needed classes
$app->uses('tpl,listform_actions');

$app->listform_actions->SQLOrderBy = 'ORDER BY aps_packages.name, aps_packages.version';
// Show only unlocked packages to clients and (un-)lockable packages to admins
if($_SESSION['s']['user']['typ'] != 'admin') $app->listform_actions->SQLExtWhere = 'aps_packages.package_status = '.PACKAGE_ENABLED;
else $app->listform_actions->SQLExtWhere = '(aps_packages.package_status = '.PACKAGE_ENABLED.' OR aps_packages.package_status = '.PACKAGE_LOCKED.')';

// Get package amount
$pkg_count = $app->db->queryOneRecord("SELECT COUNT(*) FROM aps_packages");
$app->tpl->setVar("package_count", $pkg_count['COUNT(*)']);

// Start the form rendering and action handling
$app->listform_actions->onLoad();
?>
