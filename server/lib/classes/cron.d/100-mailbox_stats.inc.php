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

class cronjob_mailbox_stats extends cronjob {

	// job schedule
	protected $_schedule = '0 0 * * *';
	protected $mailbox_traffic = array();
	protected $mail_boxes = array();
	protected $mail_rewrites = array();

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

		// cronjob code here

		//######################################################################################################
		// store the mailbox statistics in the database
		//######################################################################################################

		$parse_mail_log = false;
		$sql = "SELECT mailuser_id,maildir FROM mail_user WHERE server_id = ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id']);
		if(count($records) > 0) $parse_mail_log = true;

		foreach($records as $rec) {
			if(@is_file($rec['maildir'].'/ispconfig_mailsize')) {
				$parse_mail_log = false;

				// rename file
				rename($rec['maildir'].'/ispconfig_mailsize', $rec['maildir'].'/ispconfig_mailsize_save');

				// Read the file
				$lines = file($rec['maildir'].'/ispconfig_mailsize_save');
				$mail_traffic = 0;
				foreach($lines as $line) {
					$mail_traffic += intval($line);
				}
				unset($lines);

				// Delete backup file
				if(@is_file($rec['maildir'].'/ispconfig_mailsize_save')) unlink($rec['maildir'].'/ispconfig_mailsize_save');

				// Save the traffic stats in the sql database
				$tstamp = date('Y-m');

				$sql = "SELECT * FROM mail_traffic WHERE month = '$tstamp' AND mailuser_id = ?";
				$tr = $app->dbmaster->queryOneRecord($sql, $rec['mailuser_id']);

				$mail_traffic += $tr['traffic'];
				if($tr['traffic_id'] > 0) {
					$sql = "UPDATE mail_traffic SET traffic = ? WHERE traffic_id = ?";
					$app->dbmaster->query($sql, $mail_traffic, $tr['traffic_id']);
				} else {
					$sql = "INSERT INTO mail_traffic (month,mailuser_id,traffic) VALUES (?,?,?)";
					$app->dbmaster->query($sql, $tstamp, $rec['mailuser_id'], $mail_traffic);
				}
				//echo $sql;

			}

		}

		if($parse_mail_log == true) {
			$mailbox_traffic = array();
			$mail_boxes = array();
			$mail_rewrites = array(); // we need to read all mail aliases and forwards because the address in amavis is not always the mailbox address

			function add_mailbox_traffic(&$traffic_array, $address, $traffic,$mail_boxes, $mail_rewrites) {
				//global $mail_boxes, $mail_rewrites;
				//echo '##'.print_r($mail_boxes).'##';
				$address = strtolower($address);
				if(in_array($address, $mail_boxes) == true) {
					if(!isset($traffic_array[$address])) $traffic_array[$address] = 0;
					$traffic_array[$address] += $traffic;
				} elseif(array_key_exists($address, $mail_rewrites)) {
					foreach($mail_rewrites[$address] as $address) {
						if(!isset($traffic_array[$address])) $traffic_array[$address] = 0;
						$traffic_array[$address] += $traffic;
					}
				} else {
					// this is not a local address - skip it
				}
			}

			$sql = "SELECT email FROM mail_user WHERE server_id = ?";
			$records = $app->db->queryAllRecords($sql, $conf['server_id']);
			foreach($records as $record) {
				$mail_boxes[] = $record['email'];
			}
			$sql = "SELECT source, destination FROM mail_forwarding WHERE server_id = ?";
			$records = $app->db->queryAllRecords($sql, $conf['server_id']);
			foreach($records as $record) {
				$targets = preg_split('/[\n,]+/', $record['destination']);
				foreach($targets as $target) {
					if(in_array($target, $mail_boxes)) {
						if(isset($mail_rewrites[$record['source']])) $mail_rewrites[$record['source']][] = $target;
						else $mail_rewrites[$record['source']] = array($target);
					}
				}
			}

			$state_file = dirname(__FILE__) . '/mail_log_parser.state';
			$prev_line = false;
			$last_line = false;
			$cur_line = false;

			if(file_exists($state_file)) {
				$prev_line = $this->parse_mail_log_line(trim(file_get_contents($state_file)));
				//if($prev_line) echo "continuing from previous run, log position: " . $prev_line['message-id'] . " at " . strftime('%d.%m.%Y %H:%M:%S', $prev_line['timestamp']) . "\n";
			}

			if(file_exists('/var/log/mail.log')) {
				$fp = fopen('/var/log/mail.log', 'r');
				//echo "Parsing mail.log...\n";
				$l = 0;
				while($line = fgets($fp, 8192)) {
					$l++;
					//if($l % 1000 == 0) echo "\rline $l";
					$cur_line = $this->parse_mail_log_line($line);
					//print_r($cur_line);
					if(!$cur_line) continue;

					if($prev_line) {
						// check if this line has to be processed
						if($cur_line['timestamp'] < $prev_line['timestamp']) {
							$parse_mail_log = false; // we do not need to parse the second file!
							continue; // already processed
						} elseif($cur_line['timestamp'] == $prev_line['timestamp'] && $cur_line['message-id'] == $prev_line['message-id']) {
							$parse_mail_log = false; // we do not need to parse the second file!
							$prev_line = false; // this line has already been processed but the next one has to be!
							continue;
						}
					}
					
					$this->mail_boxes = $mail_boxes;
				    $this->mail_rewrites = $mail_rewrites;
					
					$this->add_mailbox_traffic($cur_line['from'], $cur_line['size'],$mail_boxes, $mail_rewrites);
					//echo "1\n";
					//print_r($this->mailbox_traffic);
					foreach($cur_line['to'] as $to) {
						$this->add_mailbox_traffic($to, $cur_line['size'],$mail_boxes, $mail_rewrites);
						//echo "2\n";
						//print_r($this->mailbox_traffic);
					}
					$last_line = $line; // store for the state file
				}
				fclose($fp);
				//echo "\n";
			}

			if($parse_mail_log == true && file_exists('/var/log/mail.log.1')) {
				$fp = fopen('/var/log/mail.log.1', 'r');
				//echo "Parsing mail.log.1...\n";
				$l = 0;
				while($line = fgets($fp, 8192)) {
					$l++;
					//if($l % 1000 == 0) echo "\rline $l";
					$cur_line = $this->parse_mail_log_line($line);
					if(!$cur_line) continue;

					if($prev_line) {
						// check if this line has to be processed
						if($cur_line['timestamp'] < $prev_line['timestamp']) continue; // already processed
						if($cur_line['timestamp'] == $prev_line['timestamp'] && $cur_line['message-id'] == $prev_line['message-id']) {
							$prev_line = false; // this line has already been processed but the next one has to be!
							continue;
						}
					}

					add_mailbox_traffic($mailbox_traffic, $cur_line['from'], $cur_line['size'],$mail_boxes, $mail_rewrites);
					foreach($cur_line['to'] as $to) {
						add_mailbox_traffic($mailbox_traffic, $to, $cur_line['size'],$mail_boxes, $mail_rewrites);
					}
				}
				fclose($fp);
				//echo "\n";
			}
			unset($mail_rewrites);
			unset($mail_boxes);

			// Save the traffic stats in the sql database
			$tstamp = date('Y-m');
			$sql = "SELECT mailuser_id,email FROM mail_user WHERE server_id = ?";
			$records = $app->db->queryAllRecords($sql, $conf['server_id']);
			$mailbox_traffic = $this->mailbox_traffic;
			foreach($records as $rec) {
				if(array_key_exists($rec['email'], $mailbox_traffic)) {
					$sql = "SELECT * FROM mail_traffic WHERE month = ? AND mailuser_id = ?";
					$tr = $app->dbmaster->queryOneRecord($sql, $tstamp, $rec['mailuser_id']);

					$mail_traffic = $tr['traffic'] + $mailbox_traffic[$rec['email']];
					if($tr['traffic_id'] > 0) {
						$sql = "UPDATE mail_traffic SET traffic = ? WHERE traffic_id = ?";
						$app->dbmaster->query($sql, $mail_traffic, $tr['traffic_id']);
					} else {
						$sql = "INSERT INTO mail_traffic (month,mailuser_id,traffic) VALUES (?,?,?)";
						$app->dbmaster->query($sql, $tstamp, $rec['mailuser_id'], $mail_traffic);
					}
					//echo $sql;
				}
			}

			unset($mailbox_traffic);
			if($last_line) file_put_contents($state_file, $last_line);
		}


		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}
	
	private function parse_mail_log_line($line) {
		//Oct 31 17:35:48 mx01 amavis[32014]: (32014-05) Passed CLEAN, [IPv6:xxxxx] [IPv6:xxxxx] <xxx@yyyy> -> <aaaa@bbbb>, Message-ID: <xxxx@yyyyy>, mail_id: xxxxxx, Hits: -1.89, size: 1591, queued_as: xxxxxxx, 946 ms

		// Oct 31 17:35:48
		$timestampSyslog = '\w+\s+\d+\s+\d+:\d+:\d+';
		$timePatternRsyslog = 'Y-m-d\TH:i:sT';
		// 2019-02-12T18:17:19+01:00
		$timestampRsyslog = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[\+-]\d{2}:\d{2}';
		// 2019-02-12T18:17:19.203313+01:00
		$timestampHighPrecision = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+[\+-]\d{2}:\d{2}';
		$timePatternHighPrecision = 'Y-m-d\TH:i:s.uT';

		$timestampAll = $timestampSyslog . '|' . $timestampRsyslog . '|' . $timestampHighPrecision;

		if(preg_match('/^('. $timestampAll .')\s+[^ ]+\s+amavis.* <([^>]+)>\s+->\s+((<[^>]+>,)+) .*Message-ID:\s+<([^>]+)>.* size:\s+(\d+),.*$/', $line, $matches) == false) return false;

		$timestamp = strtotime($matches[1]);
		if(!$timestamp) {
			$timestamp = DateTime::createFromFormat($timePatternRsyslog, $matches[1]);
		}
		if(!$timestamp) {
			$timestamp = DateTime::createFromFormat($timePatternHighPrecision, $matches[1]);
		}
		if(!$timestamp) return false;

		$to = array();
		$recipients = explode(',', $matches[3]);
		foreach($recipients as $recipient) {
			$recipient = substr($recipient, 1, -1);
			if(!$recipient || $recipient == $matches[2]) continue;
			$to[] = $recipient;
		}
		return array('line' => $line, 'timestamp' => $timestamp, 'size' => $matches[6], 'from' => $matches[2], 'to' => $to, 'message-id' => $matches[5]);
	}
	
	private function add_mailbox_traffic($address, $traffic) {

		$address = strtolower($address);

		if(in_array($address, $this->mail_boxes) == true) {
			if(!isset($this->mailbox_traffic[$address])) $this->mailbox_traffic[$address] = 0;
			$this->mailbox_traffic[$address] += $traffic;
		} elseif(array_key_exists($address, $this->mail_rewrites)) {
			foreach($this->mail_rewrites[$address] as $address) {
				if(!isset($this->mailbox_traffic[$address])) $this->mailbox_traffic[$address] = 0;
				$this->mailbox_traffic[$address] += $traffic;
			}
		} else {
			// this is not a local address - skip it
		}
	}

}

?>
