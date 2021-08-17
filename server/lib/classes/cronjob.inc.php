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

class cronjob {

	// default is every 5 minutes
	protected $_schedule = '*/5 * * * *';

	// may a run be skipped?
	protected $_no_skip = false;

	// if true, this job is run when it is first recognized. If false, the next run is calculated from schedule on first run.
	protected $_run_at_new = false;

	protected $_last_run = null;
	protected $_next_run = null;
	private $_running = false;

	// services for delayed restart/reload
	private $_delayed_restart_services = array();

	/** return schedule */


	public function getSchedule() {
		global $app, $conf;
		
		$class = get_class($this);
		
		switch ($class) {
			case 'cronjob_backup':
				$app->uses('ini_parser,getconf');
				$server_id = $conf['server_id'];
				$server_conf = $app->getconf->get_server_config($server_id, 'server');
				if(isset($server_conf['backup_time']) && $server_conf['backup_time'] != ''){
					list($hour, $minute) = explode(':', $server_conf['backup_time']);
					$schedule = $minute.' '.$hour.' * * *';
				} else {
					$schedule = '0 0 * * *';
				}
				break;
			/*case 'cronjob_backup_mail':
				$schedule = '1 0 * * *';
				break;*/
			default:
				$schedule = $this->_schedule;
		}
		
		return $schedule;
	}



	/** run through cronjob sequence **/
	public function run($debug_mode = false) {
		global $conf;
		
		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Called run() for class " . get_class($this) . "\n";
		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Job has schedule: " . $this->getSchedule() . "\n";
		$this->onPrepare();
		$run_it = $this->onBeforeRun();
		if($run_it == true || $debug_mode === true) {
			$this->onRunJob();
			$this->onAfterRun();
			$this->onCompleted();
		}

		return;
	}

	/* this function prepares some data for the job and sets next run time if first executed */
	protected function onPrepare() {
		global $app, $conf;

		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Called onPrepare() for class " . get_class($this) . "\n";
		// check the run time and values for this job

		// remove stale cronjobs
		$data = $app->db->queryAllRecords("SELECT `last_run` FROM `sys_cron` WHERE `name` = ? AND (`last_run` IS NOT NULL AND `last_run` < DATE_SUB(NOW(), INTERVAL 24 HOUR)) AND `running` = 1", get_class($this));
		foreach ($data as $rec) {
			if($conf['log_priority'] <= LOGLEVEL_WARN) print "Removing stale sys_cron entry for ".get_class($this)." (last run ".$rec['last_run'].")\n";
			$app->db->query("DELETE FROM `sys_cron` WHERE `name` = ? AND `last_run` = ? AND `running` = 1", $rec['name'], $rec['last_run']);
		}

		// get previous run data
		$data = $app->db->queryOneRecord("SELECT `last_run`, `next_run`, IF(`last_run` IS NOT NULL AND `last_run` < DATE_SUB(NOW(), INTERVAL 24 HOUR), 0, `running`) as `running` FROM `sys_cron` WHERE `name` = ?", get_class($this));
		if($data) {
			if($data['last_run']) $this->_last_run = $data['last_run'];
			if($data['next_run']) $this->_next_run = $data['next_run'];
			if($data['running'] == 1) $this->_running = true;
		}
		if(!$this->_next_run) {
			if($this->_run_at_new == true) {
				$this->_next_run = ISPConfigDateTime::dbtime(); // run now.
			} else {
				$app->cron->parseCronLine($this->getSchedule());
				$next_run = $app->cron->getNextRun(ISPConfigDateTime::dbtime());
				$this->_next_run = $next_run;

				$app->db->query("REPLACE INTO `sys_cron` (`name`, `last_run`, `next_run`, `running`) VALUES (?, ?, ?, ?)", get_class($this), ($this->_last_run ? $this->_last_run : "#NULL#"), ($next_run === false ? "#NULL#" : $next_run), ($this->_running == true ? "1" : "0"));
			}
		}
	}

	/* this function checks if a cron job's next runtime is reached and returns true or false */
	protected function onBeforeRun() {
		global $app, $conf;

		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Called onBeforeRun() for class " . get_class($this) . "\n";

		if($this->_running == true) return false; // job is still marked as running!

		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Jobs next run is " . $this->_next_run . "\n";
		$reached = ISPConfigDateTime::compare($this->_next_run, ISPConfigDateTime::dbtime());
		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Date compare of " . ISPConfigDateTime::to_timestamp($this->_next_run) . " and " . ISPConfigDateTime::dbtime() . " is " . $reached . "\n";
		if($reached === false) return false; // error!

		if($reached === -1) {
			// next_run time not reached
			return false;
		}

		// next_run time reached (reached === 0 or -1)

		// calculare next run time based on last_run or current time
		$app->cron->parseCronLine($this->getSchedule());
		if($this->_no_skip == true) {
			// we need to calculare the next run based on the previous next_run, as we may not skip one.
			$next_run = $app->cron->getNextRun($this->_next_run);
			if($next_run === false) {
				// we could not calculate next run, try it with current time
				$next_run = $app->cron->getNextRun(ISPConfigDateTime::dbtime());
			}
		} else {
			// calculate next run based on current time
			$next_run = $app->cron->getNextRun(ISPConfigDateTime::dbtime());
		}

		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Jobs next run is now " . $next_run . "\n";

		$app->db->query("REPLACE INTO `sys_cron` (`name`, `last_run`, `next_run`, `running`) VALUES (?, NOW(), ?, 1)", get_class($this), ($next_run === false ? "#NULL#" : $next_run));
		return true;
	}

	// child classes should override this!
	protected function onRunJob() {
		global $app, $conf;

		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Called onRun() for class " . get_class($this) . "\n";
	}

	// child classes may override this!
	protected function onAfterRun() {
		global $app, $conf;

		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Called onAfterRun() for class " . get_class($this) . "\n";

		if(is_array($this->_delayed_restart_services)) {
			foreach ($this->_delayed_restart_services as $service => $mode) {
				$this->restartService($service, $mode);
			}
		}
	}

	// child classes may NOT override this!
	protected function onCompleted() {
		global $app, $conf;

		if($conf['log_priority'] <= LOGLEVEL_DEBUG) print "Called onCompleted() for class " . get_class($this) . "\n";
		$app->db->query("UPDATE `sys_cron` SET `running` = 0 WHERE `name` = ?", get_class($this));
	}

	// child classes may NOT override this!
	protected function restartService($service, $action='restart') {
		global $app;

		$app->uses('system');

		$retval = array('output' => '', 'retval' => 0);
		if($action == 'reload') {
			exec($app->system->getinitcommand($service, 'reload').' 2>&1', $retval['output'], $retval['retval']);
		} else {
			exec($app->system->getinitcommand($service, 'restart').' 2>&1', $retval['output'], $retval['retval']);
		}
		return $retval;
	}

	// child classes may NOT override this!
	protected function restartServiceDelayed($service, $action='restart') {
		$action = ($action == 'reload' ? 'reload' : 'restart');

		if (is_array($this->_delayed_restart_services)) {
			$this->_delayed_restart_services[$service] = $action;
		}
	}

}

