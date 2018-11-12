<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
Modified 2009, Marius Cramer, pixcept KG
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

class validate_cron {

	function get_error($errmsg) {
		global $app;

		if(isset($app->tform->wordbook[$errmsg])) {
			return $app->tform->wordbook[$errmsg]."<br>\r\n";
		} else {
			return $errmsg."<br>\r\n";
		}
	}

	/*
        Validator function to check if a given cron command is in correct form (url only).
    */
	function command_format($field_name, $field_value, $validator) {
		if(preg_match("'^(\w+):\/\/'", $field_value, $matches)) {

			$parsed = parse_url($field_value);
			if($parsed === false) return $this->get_error($validator['errmsg']);

			if($parsed["scheme"] != "http" && $parsed["scheme"] != "https") return $this->get_error($validator['errmsg']);

			if(preg_match("'^([a-z0-9][a-z0-9_\-]{0,62}\.)+([A-Za-z0-9\-]{2,30})$'i", $parsed["host"]) == false) return $this->get_error($validator['errmsg']);
		}
		if(strpos($field_value, "\n") !== false || strpos($field_value, "\r") !== false || strpos($field_value, chr(0)) !== false) {
			return $this->get_error($validator['errmsg']);
		}
	}

	function run_month_format($field_name, $field_value, $validator) {
		global $app;
		//* allow value @reboot in month field
		if($field_value != '@reboot') {
			return $this->run_time_format($field_name, $field_value, $validator);
		}
	}

	/*
		Validator function to check if a given cron time is in correct form.
	*/
	function run_time_format($field_name, $field_value, $validator) {
		global $app;

		//* check general form
		$is_ok = true;
		$field_value = str_replace(" ", "", $field_value); // spaces are not needed
		$used_times = array();

		if(preg_match("'^[0-9\-\,\/\*]+$'", $field_value) == false) return $this->get_error($validator['errmsg']); // allowed characters are 0-9, comma, *, -, /
		elseif(preg_match("'[\-\,\/][\-\,\/]'", $field_value) == true) return $this->get_error($validator['errmsg']); // comma, - and / never stand together
		//* now split list and check each entry. store used values in array for later limit-check
		$time_list = explode(",", $field_value);
		if(count($time_list) < 1) return $this->get_error($validator['errmsg']);

		$max_entry = 0;
		$min_entry = 0;
		$in_minutes = 1;
		//* get maximum value of entry for each field type (name)
		switch($field_name) {
		case "run_min":
			$max_entry = 59;
			break;
		case "run_hour":
			$max_entry = 23;
			$in_minutes = 60;
			break;
		case "run_mday":
			$max_entry = 31;
			$min_entry = 1;
			$in_minutes = 1440;
			break;
		case "run_month":
			$max_entry = 12;
			$min_entry = 1;
			$in_minutes = 1440 * 28; // not exactly but enough
			break;
		case "run_wday":
			$max_entry = 7;
			$in_minutes = 1440;
			break;
		}

		if($max_entry == 0) return $this->get_error('unknown_fieldtype_error');

		foreach($time_list as $entry) {
			//* possible value combinations:
			//* x               =>      ^(\d+)$
			//* x-y             =>      ^(\d+)\-(\d+)$
			//* x/y             =>      ^(\d+)\/([1-9]\d*)$
			//* x-y/z           =>      ^(\d+)\-(\d+)\/([1-9]\d*)$
			//* */x             =>      ^\*\/([1-9]\d*)$
			//* combined regex  =>      ^(\d+|\*)(\-(\d+))?(\/([1-9]\d*))?$

			if(preg_match("'^(((\d+)(\-(\d+))?)|\*)(\/([1-9]\d*))?$'", $entry, $matches) == false) {
				return $this->get_error($validator['errmsg']);
			}

			//* matches contains:
			//* 1       =>      * or value or x-y range
			//* 2       =>      unused
			//* 3       =>      value if [1] != *
			//* 4       =>      empty if no range was used
			//* 5       =>      2nd value of range if [1] != * and range was used
			//* 6       =>      empty if step was not used
			//* 7       =>      step

			$loop_step = 1;
			$loop_from = $min_entry;
			$loop_to = $max_entry;

			//* calculate used values
			if($matches[1] == "*") {
				//* not to check
			} else {
				if($matches[3] < $min_entry || $matches[3] > $max_entry) {
					//* check if value is in allowed range
					return $this->get_error($validator['errmsg']);
				} elseif($matches[4] && ($matches[5] < $min_entry || $matches[5] > $max_entry || $matches[5] <= $matches[3])) {
					//* check if value is in allowed range and not less or equal to first value
					return $this->get_error($validator['errmsg']);
				}

				$loop_from = $matches[3];
				$loop_to = $matches[3];
				if($matches[4]) $loop_to = $matches[5];
			}
			if($matches[6] && ($matches[7] < 2 || $matches[7] > $max_entry - 1)) {
				//* check if step value is valid
				return $this->get_error($validator['errmsg']);
			}
			if($matches[7]) $loop_step = $matches[7];

			//* loop through values to set used times
			for($t = $loop_from; $t <= $loop_to; $t = $t + $loop_step) {
				$used_times[] = $t;
			}
		} //* end foreach entry loop

		//* sort used times and erase doubles
		sort($used_times);
		$used_times = array_unique($used_times);

		//* get minimum frequency and store it in $app->tform->cron_min_freq for usage in onUpdateSave and onInsertSave!
		$min_freq = -1;
		$prev_time = -1;
		foreach($used_times as $curtime) {
			if($prev_time != -1) {
				$freq = $curtime - $prev_time;
				if($min_freq == -1 || $freq < $min_freq) $min_freq = $freq;
			}
			$prev_time = $curtime;
		}

		//* check last against first (needed because e.g. wday 1,4,7 has diff 1 not 3
		$prev_time = $used_times[0];
		$freq = ($prev_time - $min_entry) + ($max_entry - $curtime) + 1;
		if($min_freq == -1 || $freq < $min_freq) $min_freq = $freq;

		if($min_freq > 0 && $min_freq <= $max_entry) { //* only store if > 1 && < $max_entry!
			$min_freq = $min_freq * $in_minutes; // we have to overwrite $app->tform->cron_min_freq if this is higher value
			if(!$app->tform->cron_min_freq || $app->tform->cron_min_freq > $min_freq) $app->tform->cron_min_freq = $min_freq;
		}

		//return "DEBUG: " . $app->tform->cron_min_freq . " ($min_freq) --- " . var_export($used_times, true) . "<br />";
	}




}
