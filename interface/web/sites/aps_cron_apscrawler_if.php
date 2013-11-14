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

//require_once('classes/class.crawler.php');
$app->load('aps_crawler');

if(!@ini_get('allow_url_fopen')) $app->error('allow_url_fopen is not enabled.');
if(!function_exists('curl_version')) $app->error('The PHP CURL extension is not available.');

$log_prefix = 'APS crawler cron: ';

$aps = new ApsCrawler($app, true); // true = Interface mode, false = Server mode

$app->log($log_prefix.'Used mem at begin: '.$aps->convertSize(memory_get_usage(true)));

$time_start = microtime(true);
$aps->startCrawler();
$aps->parseFolderToDB();
$aps->fixURLs();
$time = microtime(true) - $time_start;

$app->log($log_prefix.'Used mem at end: '.$aps->convertSize(memory_get_usage(true)));
$app->log($log_prefix.'Mem peak during execution: '.$aps->convertSize(memory_get_peak_usage(true)));
$app->log($log_prefix.'Execution time: '.round($time, 3).' seconds');

// Load the language file
$lngfile = 'lib/lang/'.$_SESSION['s']['language'].'_aps.lng';
$app->load_language_file('web/sites/'.$lngfile);

echo '<div id="OKMsg"><p>'.$app->lng('packagelist_update_finished_txt').'</p></div>';



?>
