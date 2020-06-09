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

class cronjob_monitor_system_update extends cronjob {

	// job schedule
	protected $_schedule = '0 * * * *';
	protected $_run_at_new = true;

	private $_tools = null;

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
	public function onBeforeRun() {
		global $app;

		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;

		$app->uses('getconf');
		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
		if($server_config['monitor_system_updates'] == 'n') return;
		
		/* used for all monitor cronjobs */
		$app->load('monitor_tools');
		$this->_tools = new monitor_tools();
		/* end global section for monitor cronjobs */

		/* the id of the server as int */
		$server_id = intval($conf['server_id']);

		/** The type of the data */


		$type = 'system_update';

		/* This monitoring is only available on Debian or Ubuntu */
		if (file_exists('/etc/debian_version')) {

			/*
			 * first update the "apt database"
			 */
			shell_exec('while fuser /var/lib/apt/lists/lock >/dev/null 2>&1 ; do sleep 2; done; apt-get update');

			/*
			 * Then test the upgrade.
			 * if there is any output, then there is a needed update
			 */
			$aptData = shell_exec('while fuser /var/lib/dpkg/lock >/dev/null 2>&1 || fuser /var/lib/apt/lists/lock >/dev/null 2>&1 ; do sleep 2; done; apt-get -s -qq dist-upgrade');
			if ($aptData == '') {
				/* There is nothing to update! */
				$state = 'ok';
			} else {
				/*
				 * There is something to update! this is in most cases not critical, so we can
				 * do a system-update once a month or so...
				 */
				$state = 'info';
			}

			/*
			 * Fetch the output
			 */
			$data['output'] = $aptData;
		} elseif (file_exists('/etc/gentoo-release')) {

			/*
			 * first update the portage tree
			 */

			// In keeping with gentoo's rsync policy, don't update to frequently (every four hours - taken from http://www.gentoo.org/doc/en/source_mirrors.xml)
			$do_update = true;
			if (file_exists('/usr/portage/metadata/timestamp.chk')) {
				$datetime = file_get_contents('/usr/portage/metadata/timestamp.chk');
				$datetime = trim($datetime);

				$dstamp = strtotime($datetime);
				if ($dstamp) {
					$checkat = $dstamp + 14400; // + 4hours
					if (mktime() < $checkat) {
						$do_update = false;
					}
				}
			}

			if ($do_update) {
				shell_exec('emerge --sync --quiet');
			}

			/*
			 * Then test the upgrade.
			 * if there is any output, then there is a needed update
			 */
			$emergeData = shell_exec('glsa-check -t affected');
			if ($emergeData == '') {
				/* There is nothing to update! */
				$state = 'ok';
				$data['output'] = 'No unapplied GLSA\'s found on the system.';
			} else {
				/* There is something to update! */
				$state = 'info';
				$data['output'] = shell_exec('glsa-check -pv --nocolor affected 2>/dev/null');
			}
		} elseif (file_exists('/etc/SuSE-release')) {

			/*
			 * update and find the upgrade.
			 * if there is any output, then there is a needed update
			 */
			$aptData = shell_exec('zypper -q lu');
			if ($aptData == '') {
				/* There is nothing to update! */
				$state = 'ok';
			} else {
				/*
				 * There is something to update! this is in most cases not critical, so we can
				 * do a system-update once a month or so...
				 */
				$state = 'info';
			}

			/*
			 * Fetch the output
			 */
			$data['output'] = shell_exec('zypper lu');

		} elseif(file_exists('/etc/redhat-release')) {
                        /*
                         * update and find the upgrade.
                         * if there is any output, then there is a needed update
                         */

			/* try to figure out the default package manager first */
                        if(file_exists('/usr/bin/dnf') && (is_link('/usr/bin/yum'))) {
                                $rhPkgMgr = 'dnf';
                        } elseif(file_exists('/usr/bin/dnf') && (!file_exists('/usr/bin/yum'))) {
                                $rhPkgMgr = 'dnf';
                        } else {
                                $rhPkgMgr = 'yum';
                        }

			$aptData = shell_exec($rhPkgMgr. ' -q list updates');

                        if ($aptData == '') {
                                /* There is nothing to update! */
                                $state = 'ok';
                        } else {
                                /*
                                 * There is something to update! this is in most cases not critical, so we can
                                 * do a system-update once a month or so...
                                 */
                                $state = 'info';
                        }

                        /*
                         * Fetch the output
			 */

                        $data['output'] = shell_exec($rhPkgMgr. ' -q list updates');
            
	        } else {
			/*
			 * It is not Debian/Ubuntu, so there is no data and no state
			 *
			 * no_state, NOT unknown, because "unknown" is shown as state
			 * inside the GUI. no_state is hidden.
			 *
			 * We have to write NO DATA inside the DB, because the GUI
			 * could not know, if there is any dat, or not...
			 */
			$state = 'no_state';
			$data['output'] = '';
		}

		$res = array();
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;

		//* Ensure that output is encoded so that it does not break the serialize
		//$res['data']['output'] = htmlentities($res['data']['output']);
		$res['data']['output'] = htmlentities($res['data']['output'], ENT_QUOTES, 'UTF-8');

		/*
		 * Insert the data into the database
		 */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
			'VALUES (?, ?, UNIX_TIMESTAMP(), ?, ?)';
		$app->dbmaster->query($sql, $res['server_id'], $res['type'], serialize($res['data']), $res['state']);

		/* The new data is written, now we can delete the old one */
		$this->_tools->delOldRecords($res['type'], $res['server_id']);

		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
