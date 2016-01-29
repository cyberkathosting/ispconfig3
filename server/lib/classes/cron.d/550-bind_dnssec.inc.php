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

	public function onRunJob() {
		global $app, $conf;

		//* Load libraries
		$app->uses("getconf,tpl");

		//* load the server configuration options
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//TODO : change this when distribution information has been integrated into server record
		$filespre = (file_exists('/etc/gentoo-release')) ? 'pri/' : 'pri.';
		
		$soas = $app->db->queryAllRecords('SELECT * FROM dns_soa WHERE dnssec_wanted=\'Y\' AND dnssec_initialized=\'Y\' AND dnssec_last_signed < '.(time()-(3600*24*5)+900)); //Resign zones every 5 days (expiry is 16 days so we have enough safety, 15 minutes tolerance)
		
		foreach ($soas as $data) {
			$domain = substr($data['origin'], 0, strlen($data['origin'])-1);
			if (!file_exists($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain)) return false;
			
			$app->log('DNSSEC Auto-Resign: Resigning zone '.$domain, LOGLEVEL_INFO);
			
			$zonefile = file_get_contents($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain);
			$keycount=0;
			foreach (glob($dns_config['bind_zonefiles_dir'].'/K'.$domain.'*.key') as $keyfile) {
				$includeline = '$INCLUDE '.basename($keyfile);
				if (!preg_match('@'.preg_quote($includeline).'@', $zonefile)) $zonefile .= "\n".$includeline."\n";
				$keycount++;
			}
			if ($keycount != 2) $app->log('DNSSEC Warning: There are more or less than 2 keyfiles for zone '.$domain, LOGLEVEL_WARN);
			file_put_contents($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain, $zonefile);
			
			//Sign the zone and set it valid for max. 16 days
			exec('cd '.escapeshellcmd($dns_config['bind_zonefiles_dir']).';'.
				 '/usr/sbin/dnssec-signzone -A -e +1382400 -3 $(head -c 1000 /dev/random | sha1sum | cut -b 1-16) -N increment -o '.escapeshellcmd($domain).' -t '.$filespre.escapeshellcmd($domain));
				 
			//Write Data back into DB
			$dnssecdata = "DS-Records:\n".file_get_contents($dns_config['bind_zonefiles_dir'].'/dsset-'.$domain.'.');
			$dnssecdata .= "\n------------------------------------\n\nDNSKEY-Records:\n";
			foreach (glob($dns_config['bind_zonefiles_dir'].'/K'.$domain.'*.key') as $keyfile) {
				$dnssecdata .= file_get_contents($keyfile)."\n\n";
			}
			
			$app->db->query('UPDATE dns_soa SET dnssec_info=\''.$dnssecdata.'\', dnssec_initialized=\'Y\', dnssec_last_signed=\''.time().'\' WHERE id='.$data['id']);
			$data = next($soas);
		}
		
		parent::onRunJob();
	}

}

?>
