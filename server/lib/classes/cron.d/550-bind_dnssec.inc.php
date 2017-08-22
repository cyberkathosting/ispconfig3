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


DNSSEC-Implementation by Alexander Täffner aka dark alex
*/

class cronjob_bind_dnssec extends cronjob {

	// job schedule
	protected $_schedule = '30 3 * * *'; //daily at 3:30 a.m.
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
	
	private function increase_serial($serial){
		global $app, $conf;

		// increase serial
		$serial_date = $app->functions->intval(substr($serial, 0, 8));
		$count = $app->functions->intval(substr($serial, 8, 2));
		$current_date = date("Ymd");
		if($serial_date >= $current_date){
			$count += 1;
			if ($count > 99) {
				$serial_date += 1;
				$count = 0;
			}
			$count = str_pad($count, 2, "0", STR_PAD_LEFT);
			$new_serial = $serial_date.$count;
		} else {
			$new_serial = $current_date.'01';
		}
		return $new_serial;
	}

	public function onRunJob() {
		global $app, $conf;
		
		//* job should only run on ispc master
		if($app->db->dbHost != $app->dbmaster->dbHost) return;

		//* Load libraries
		$app->uses("getconf,tpl");

		//* load the server configuration options
//		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//TODO : change this when distribution information has been integrated into server record
//		$filespre = (file_exists('/etc/gentoo-release')) ? 'pri/' : 'pri.';
//		$soas = $app->db->queryAllRecords("SELECT id,serial,origin FROM dns_soa WHERE server_id = ? AND active= 'Y' AND dnssec_wanted = 'Y' AND dnssec_initialized = 'Y' AND (dnssec_last_signed < ? OR dnssec_last_signed > ?)", $conf['server_id'], time()-(3600*24*5)+900, time()+900); //Resign zones every 5 days (expiry is 16 days so we have enough safety, 15 minutes tolerance)
		$soas = $app->db->queryAllRecords("SELECT id,serial,origin FROM dns_soa WHERE active= 'Y' AND dnssec_wanted = 'Y' AND dnssec_initialized = 'Y' AND (dnssec_last_signed < ? OR dnssec_last_signed > ?)", time()-(3600*24*5)+900, time()+900); //Resign zones every 5 days (expiry is 16 days so we have enough safety, 15 minutes tolerance)

		foreach ($soas as $data) {
			$domain = substr($data['origin'], 0, strlen($data['origin'])-1);
//			if (!file_exists($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain)) continue;
			
			$app->log('DNSSEC Auto-Resign: Touching zone '.$domain, LOGLEVEL_DEBUG);
			$app->db->datalogUpdate('dns_soa', array("serial" => $this->increase_serial($data['serial'])), 'id', $data['id']);
		}
		
		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
