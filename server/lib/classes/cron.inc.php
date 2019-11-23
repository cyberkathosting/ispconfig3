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

class cron {

	/**#@+
     * @access private
     */
	private $_sMinute = '';
	private $_sHour = '';
	private $_sDay = '';
	private $_sMonth = '';
	private $_sWDay = '';
	private $_bParsed = false;
	private $_iNextRun = null;
	private $_aValidValues;

	public function __construct() {
		// empty
		$this->_sMinute = '';
		$this->_sHour = '';
		$this->_sDay = '';
		$this->_sMonth = '';
		$this->_sWDay = '';
		$this->_bParsed = false;
		$this->_aValidValues = array('minute' => array(),
			'hour' => array(),
			'day' => array(),
			'month' => array(),
			'weekday' => array());
	}

	private function _calcValidValues() {
		// minute field
		$this->_aValidValues['minute'] = $this->_calcFieldValues('minute', $this->_sMinute);
		$this->_aValidValues['hour'] = $this->_calcFieldValues('hour', $this->_sHour);
		$this->_aValidValues['day'] = $this->_calcFieldValues('day', $this->_sDay);
		$this->_aValidValues['month'] = $this->_calcFieldValues('month', $this->_sMonth);
		$this->_aValidValues['weekday'] = $this->_calcFieldValues('weekday', $this->_sWDay);
		$this->_bParsed = true;
	}

	private function _calcFieldValues($sField, $sValue) {
		global $app;

		$aValidValues = array();

		// global checks
		$iFrom = 0;
		$iTo = 0;
		switch($sField) {
		case 'minute':
			$iTo = 59;
			break;
		case 'hour':
			$iTo = 23;
			break;
		case 'day':
			$iFrom = 1;
			$iTo = 31;
			break;
		case 'month':
			$sValue = strtr($sValue, array('JAN' => 1,
					'FEB' => 2,
					'MAR' => 3,
					'APR' => 4,
					'MAY' => 5,
					'JUN' => 6,
					'JUL' => 7,
					'AUG' => 8,
					'SEP' => 9,
					'OCT' => 10,
					'NOV' => 11,
					'DEC' => 12)
			);
			$iFrom = 1;
			$iTo = 12;
			break;
		case 'weekday':
			$sValue = strtr($sValue, array('SUN' => 0,
					'MON' => 1,
					'TUE' => 2,
					'WED' => 3,
					'THU' => 4,
					'FRI' => 5,
					'SAT' => 6,
					'7' => 0)
			);
			$iTo = 6;
			break;
		}
		$aParts = explode(',', $sValue);
		for($a = 0; $a < count($aParts); $a++) {
			$sValue = $aParts[$a];
			$iValue = $app->functions->intval($sValue);

			if($sValue === '*') {
				// everything is valid
				for($i = $iFrom; $i <= $iTo; $i++) {
					$aValidValues[] = $i;
				}
				break; // no need to go any further
			} elseif((string)$iValue == $sValue) {
				if($iValue >= $iFrom && $iValue <= $iTo) $aValidValues[] = $iValue;
			} elseif(preg_match('/^([0-9]+)-([0-9]+)(\/([1-9][0-9]*))?$/', $sValue, $aMatch)) {
				if($aMatch[1] < $iFrom) $aMatch[1] = $iFrom;
				if($aMatch[2] > $iTo) $aMatch[2] = $iTo;
				if(isset($aMatch[3])) {
					for($i = $aMatch[1]; $i <= $aMatch[2]; $i++) {
						if(($i - $aMatch[1]) % $aMatch[4] == 0) $aValidValues[] = $i;
					}
				} else {
					for($i = $aMatch[1]; $i <= $aMatch[2]; $i++) $aValidValues[] = $i;
				}
			} elseif(preg_match('/^\*\/([1-9][0-9]*)$/', $sValue, $aMatch)) {
				for($i = $iFrom; $i <= $iTo; $i++) {
					if($i % $aMatch[1] == 0) $aValidValues[] = $i;
				}
			}
		}

		$aValidValues = array_unique($aValidValues);
		sort($aValidValues);

		return $aValidValues;
	}

	/**#@-*/

	/**
	 * Set the cron field values
	 *
	 * @param string $sMinute the minute field value
	 * @param string $sHour the hour field value
	 * @param string $sDay the day field value
	 * @param string $sWDay the weekday field value
	 * @param string $sMonth the month field value
	 */


	public function setCronFields($sMinute = '*', $sHour = '*', $sDay = '*', $sMonth = '*', $sWDay = '*') {
		$this->_sMinute = $sMinute;
		$this->_sHour = $sHour;
		$this->_sDay = $sDay;
		$this->_sMonth = $sMonth;
		$this->_sWDay = $sWDay;
		$this->_bParsed = false;
	}



	/**
	 * Parse a line of a cron and set the internal field values
	 *
	 * @param string $sLine cron line
	 */
	public function parseCronLine($sLine) {
		$aFields = preg_split('/[ \t]+/', trim($sLine));
		for($i = 0; $i < 5; $i++) {
			if(!isset($aFields[$i])) $aFields[$i] = '*';
		}
		if($aFields[0] == '@yearly' || $aFields[0] == '@annually') $aFields = array(0, 0, 1, 1, '*');
		elseif($aFields[0] == '@monthly') $aFields = array(0, 0, 1, '*', '*');
		elseif($aFields[0] == '@weekly') $aFields = array(0, 0, '*', '*', 0);
		elseif($aFields[0] == '@daily' || $aFields[0] == '@midnight') $aFields = array(0, 0, '*', '*', '*');
		elseif($aFields[0] == '@hourly') $aFields = array(0, '*', '*', '*', '*');

		$this->setCronFields($aFields[0], $aFields[1], $aFields[2], $aFields[3], $aFields[4]);
	}

	public function getNextRun($vDate) {
		global $app;

		$iTimestamp = ISPConfigDatetime::to_timestamp($vDate);
		if($iTimestamp === false) return $iTimestamp;

		if($this->_bParsed == false) $this->_calcValidValues();

		// get the field values for the given Date.
		list($iMinute, $iHour, $iDay, $iWDay, $iMonth, $iYear) = explode(':', ISPConfigDateTime::to_string($vDate, 'custom:%M:%H:%d:%w:%m:%Y'));

		$bValid = false;
		$iStartYear = $iYear;
		while($bValid == false) {
			$iCurMinute = $this->_getNextValue('minute', $iMinute, true);
			$iCurHour = $this->_getNextValue('hour', $iHour, true);
			$iCurDay = $this->_getNextValue('day', $iDay, true);
			$iCurMonth = $this->_getNextValue('month', $iMonth, true);
			$iCurWDay = $this->_getNextValue('weekday', $iWDay, true);

			$iNextMinute = $this->_getNextValue('minute', $iMinute);
			$iNextHour = $this->_getNextValue('hour', $iHour);
			$iNextDay = $this->_getNextValue('day', $iDay);
			$iNextMonth = $this->_getNextValue('month', $iMonth);
			$iNextWDay = $this->_getNextValue('weekday', $iWDay);

			if($iNextMinute > $iMinute && $iHour == $iCurHour && $iDay == $iCurDay && $iWDay == $iCurWDay && $iMonth == $iCurMonth) {
				$iMinute = $iNextMinute;
			} elseif($iNextHour > $iHour && $iDay == $iCurDay && $iWDay == $iCurWDay && $iMonth == $iCurMonth) {
				$iMinute = reset($this->_aValidValues['minute']);
				$iHour = $iNextHour;
			} elseif($iNextDay > $iDay && ISPConfigDateTime::last_day($iMonth) >= $iNextDay && $iMonth == $iCurMonth) {
				$iMinute = reset($this->_aValidValues['minute']);
				$iHour = reset($this->_aValidValues['hour']);
				$iDay = $iNextDay;
			} elseif($iNextMonth > $iMonth) {
				$iMinute = reset($this->_aValidValues['minute']);
				$iHour = reset($this->_aValidValues['hour']);
				$iDay = reset($this->_aValidValues['day']);
				$iMonth = $iNextMonth;
			} else {
				$iMinute = reset($this->_aValidValues['minute']);
				$iHour = reset($this->_aValidValues['hour']);
				$iDay = reset($this->_aValidValues['day']);
				$iMonth = reset($this->_aValidValues['month']);
				$iYear++;
			}

			$ts = mktime($iHour, $iMinute, 0, $iMonth, $iDay, $iYear);
			//print strftime('%d.%m.%Y (%A) %H:%M', $ts) . "\n";
			//var_dump($iCurMinute, $iCurHour, $iCurDay, $iCurMonth, $iCurWDay, '--', $iNextMinute, $iNextHour, $iNextDay, $iNextMonth, $iNextWDay);
			if(ISPConfigDateTime::last_day($iMonth, $iYear) >= $iDay && in_array($app->functions->intval(strftime('%w', $ts)), $this->_aValidValues['weekday'], true) === true) {
				$bValid = true;
			} else {
				if($iYear - $iStartYear > 5) {
					if(LOG_PRIORITY <= PRIO_ERROR) $portal->log('No valid run dates for schedule ' . $this->_sMinute . ' ' . $this->_sHour . ' ' . $this->_sDay . ' ' . $this->_sMonth . ' ' . $this->_sWDay . ' in the next 5 years!', PRIO_ERROR, __FILE__, __LINE__);
					return false;
				}
			}
		}

		//var_dump($vDate, implode('-', array($iYear, $iMonth, $iDay, $iHour, $iNextMinute, 0)), $this->_sMinute, $this->_sHour, $this->_sDay, $this->_sWDay, $this->_sMonth, $this->_aValidValues);
		return $iYear . '-' . $iMonth . '-' . $iDay . ' ' . $iHour . ':' . $iNextMinute . ':0';
	}

	private function _getNextValue($sField, $iValue, $bIncludeCurrent = false) {
		if(!array_key_exists($sField, $this->_aValidValues)) return false;

		reset($this->_aValidValues[$sField]);
		foreach($this->_aValidValues[$sField] as $cur) {
			if($bIncludeCurrent == true && $cur >= $iValue) return $cur;
			elseif($cur > $iValue) return $cur;
		}
		return reset($this->_aValidValues[$sField]);
	}

}

?>
