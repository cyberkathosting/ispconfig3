<?php

/*
Copyright (c) 2010 Till Brehm, projektfarm Gmbh
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

$app->uses('simplepie');
$app->uses('auth');

$app->tpl->newTemplate('dashboard/templates/custom_menu.htm');

$app->uses('getconf');
$misc_config = $app->getconf->get_global_config('misc');


switch($_SESSION['s']['user']['typ']) {
case 'admin':
	$atom_url = $misc_config['dashboard_atom_url_admin'];
	break;
case 'user':
	if ($app->auth->has_clients($_SESSION['s']['user']['userid']) === true)
		$atom_url = $misc_config['dashboard_atom_url_reseller'];
	else
		$atom_url = $misc_config['dashboard_atom_url_client'];
	break;
default:
	$atom_url = "";
}

$rows = array();

if( $atom_url != '' ) {
	if(!isset($_SESSION['s']['rss_news'])) {

		$app->simplepie->set_feed_url($atom_url);
		$app->simplepie->enable_cache(false);
		$app->simplepie->init();
		$items = $app->simplepie->get_items();

		$rows = array();
		$n = 1;

		foreach ($items as $item)
		{
			//* We want to show only the first 10 news records
			if($n <= 10) {
				$rows[] = array('title' => $item->get_title(),
					'link' => $item->get_link(),
					'content' => $item->get_content(),
					'date' => $item->get_date($app->lng('conf_format_dateshort'))
				);
			}
			$n++;
		}

		$_SESSION['s']['rss_news'] = $rows;

	} else {
		$rows = $_SESSION['s']['rss_news'];
	}

	$app->tpl->setVar('latest_news_txt', $app->lng('latest_news_txt'));

}

$app->tpl->setLoop('news', $rows);

?>
