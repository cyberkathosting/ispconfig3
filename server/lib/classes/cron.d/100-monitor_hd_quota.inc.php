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

class cronjob_monitor_hd_quota extends cronjob {

	// job schedule
	protected $_schedule = '*/5 * * * *';
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

		/* used for all monitor cronjobs */
		$app->load('monitor_tools');
		$this->_tools = new monitor_tools();
		/* end global section for monitor cronjobs */

		//* Initialize data array
		$data = array();

		//* the id of the server as int
		$server_id = intval($conf['server_id']);

		//* The type of the data
		$type = 'harddisk_quota';

		//* The state of the harddisk_quota.
		$state = 'ok';

		//* Fetch the data for all users
		$dfData = shell_exec('repquota -au 2>/dev/null');

		//* Split into array
		$df = explode("\n", $dfData);

		//* ignore the first 5 lines, process the rest
		for ($i = 5; $i <= sizeof($df); $i++) {
			if ($df[$i] != '') {
				//* Make a array of the data
				$s = preg_split('/[\s]+/', $df[$i]);
				$username = $s[0];
				if (substr($username, 0, 3) == 'web') {
					if (isset($data['user'][$username])) {
						$data['user'][$username]['used'] += $s[2];
						$data['user'][$username]['soft'] += $s[3];
						$data['user'][$username]['hard'] += $s[4];
						$data['user'][$username]['files'] += $s[5];
					} else {
						$data['user'][$username]['used'] = $s[2];
						$data['user'][$username]['soft'] = $s[3];
						$data['user'][$username]['hard'] = $s[4];
						$data['user'][$username]['files'] = $s[5];
					}
				}
			}
		}

		//** Fetch the data for all users
		$dfData = shell_exec('repquota -ag 2>/dev/null');

		//* split into array
		$df = explode("\n", $dfData);

		//* ignore the first 5 lines, process the rest
		for ($i = 5; $i <= sizeof($df); $i++) {
			if ($df[$i] != '') {
				//* Make a array of the data
				$s = preg_split('/[\s]+/', $df[$i]);
				$groupname = $s[0];
				if (substr($groupname, 0, 6) == 'client') {
					if (isset($data['group'][$groupname])) {
						$data['group'][$groupname]['used'] += $s[1];
						$data['group'][$groupname]['soft'] += $s[2];
						$data['group'][$groupname]['hard'] += $s[3];
					} else {
						$data['group'][$groupname]['used'] = $s[1];
						$data['group'][$groupname]['soft'] = $s[2];
						$data['group'][$groupname]['hard'] = $s[3];
					}
				}
			}
		}

		$res = array();
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;

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
