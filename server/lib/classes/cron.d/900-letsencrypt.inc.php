<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
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

class cronjob_letsencrypt extends cronjob {

	// job schedule
	protected $_schedule = '0 3 * * *';

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
	public function onBeforeRun() {
		global $app;

		$app->modules->loadModules('web_module');
		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;
		
		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
		if(!isset($server_config['migration_mode']) || $server_config['migration_mode'] != 'y') {			
			$acme = $app->letsencrypt->get_acme_script();
			if($acme) {
				// skip letsencrypt
				parent::onRunJob();
				return;
			}
			
			$letsencrypt = $app->letsencrypt->get_certbot_script();
			if($letsencrypt) {
				$ret = null;
				$val = 0;
				$matches = array();
				$version = exec($letsencrypt . ' --version  2>&1', $ret, $val);
				if(preg_match('/^(\S+|\w+)\s+(\d+(\.\d+)+)$/', $version, $matches)) {
					$type = strtolower($matches[1]);
					$version = $matches[2];
					if(($type != 'letsencrypt' && $type != 'certbot') || version_compare($version, '0.7.0', '<')) {
						exec($letsencrypt . ' -n renew');
						$app->services->restartServiceDelayed('httpd', 'force-reload');
					} else {
						$marker_file = '/usr/local/ispconfig/server/le.restart';
						$cmd = "echo '1' > " . $marker_file;
						$app->system->exec_safe($letsencrypt . ' -n renew --post-hook ?', $cmd);
						if(file_exists($marker_file) && trim(file_get_contents($marker_file)) == '1') {
							unlink($marker_file);
							$app->services->restartServiceDelayed('httpd', 'force-reload');
						}
					}
				} else {
					exec($letsencrypt . ' -n renew');
					$app->services->restartServiceDelayed('httpd', 'force-reload');
				}
			}
		} else {
			$app->log('Migration mode active, not running Let\'s Encrypt renewal.', LOGLEVEL_DEBUG);
		}
		
		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		parent::onAfterRun();
	}

}