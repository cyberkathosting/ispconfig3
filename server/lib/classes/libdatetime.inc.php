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

/**
 * Date and Time class
 *
 * provides functions related to date and time operations
 */


abstract class ISPConfigDateTime {

	/**
	 * Get days, hours, minutes and seconds
	 *
	 * Returns an array with days, hours, minutes and seconds from a given amount of seconds
	 *
	 * @access public
	 * @param int $seconds amount of seconds
	 * @param bool $get_days if true get the days, too
	 * @return array data (0 => days, 1 => hours, 2 => minutes, 3 => seconds)
	 */
	public static function get_parts($seconds, $get_days = false) {
		$days = 0;
		if($get_days == true) {
			$days = floor($seconds / (3600 * 24));
			$seconds = $seconds % (3600 * 24);
		}
		$hours = floor($seconds / 3600);
		$seconds = $seconds % 3600;
		$minutes = floor($seconds / 60);
		$seconds = $seconds % 60;

		return array($days, $hours, $minutes, $seconds);
	}

	public static function dbtime() {
		global $app;

		$time = $app->db->queryOneRecord('SELECT UNIX_TIMESTAMP() as `time`');
		return $time['time'];
	}



	/**
	 * Get a unix timestamp for a date
	 *
	 * @access public
	 * @param mixed $date the date to convert. Can be
	 * - int (unix timestamp)
	 * - date string yyyy-mm-dd[ hh:mm:ss]
	 * - date string dd.mm.yyyy[ hh:mm:ss]
	 * @return int the unix timestamp
	 */
	public static function to_timestamp($date) {
		if(!is_string($date) && !is_numeric($date)) return false;
		$date = trim($date);

		if(is_numeric($date)) return $date;

		if(strpos($date, '-') !== false) {
			$regex = "(\d{2,4})-(\d{1,2})-(\d{1,2})(\s+(\d{1,2}):(\d{1,2})(:(\d{1,2}))?)?";
			$ok = preg_match("'$regex'", $date, $matches);
			if($ok) {
				$year = $matches[1];
				$month = $matches[2];
				$day = $matches[3];
				$hour = isset($matches[5]) ? $matches[5] : 0;
				$minute = isset($matches[6]) ? $matches[6] : 0;
				$second = isset($matches[8]) ? $matches[8] : 0;
			}
		} else {
			$regex = "(\d{1,2})[/.](\d{1,2})[/.](\d{2,4})(\s+(\d{1,2}):(\d{1,2})(:(\d{1,2}))?)?";
			$ok = preg_match("'$regex'", $date, $matches);
			if($ok) {
				$year = $matches[3];
				$month = $matches[2];
				$day = $matches[1];
				$hour = isset($matches[5]) ? $matches[5] : 0;
				$minute = isset($matches[6]) ? $matches[6] : 0;
				$second = isset($matches[8]) ? $matches[8] : 0;
			}
		}

		if(!$ok) return false;

		if(!$day || !$month || !$year) return false;
		if(!$hour) $hour = 0;
		if(!$minute) $minute = 0;
		if(!$second) $second = 0;
		if($year < 1900) $year += 1900;

		if(!checkdate($month, $day, $year)) return false;

		$date = mktime($hour, $minute, $second, $month, $day, $year);

		return $date;
	}

	/**
	 * Get a date string
	 *
	 * Returns a formatted date string
	 *
	 * @access public
	 * @param mixed $date the date to convert. Can be
	 * - int (unix timestamp)
	 * - date string yyyy-mm-dd[ hh:mm:ss]
	 * - date string dd.mm.yyyy[ hh:mm:ss]
	 * @param string $format the format to get the date string in.
	 * - short: dd.mm.yy
	 * - veryshort: dd.mm.
	 * - medium: dd.mm.yyyy
	 * - long: dd. Month yyyy
	 * - extra: Day, dd. Month yyyy
	 * - day: dd
	 * - monthnum: mm
	 * - shortmonth: Short month name like Mar for March
	 * - month: Month name
	 * - shortyear: yy
	 * - year: yyyy
	 * - onlydate: dd.mm
	 * - onlydatelong: dd. Month
	 * - onlytime: HH:MM
	 * - rss: Rss time format for XML
	 * - nice: if you prepend a nice: (like nice:long) you will get results like "today" or "yesterday" if applicable
	 * - custom: you can give a strftime format like %d.%m.%Y %H:%M if you prepend custom: to it
	 * @param bool $time if true apped the time to the date string
	 * @param bool $seconds if true append the seconds to the time
	 * @return string date string
	 */
	public static function to_string($date, $format = 'short', $time = false, $seconds = false) {
		global $portal;

		if(!$date) return '';

		setlocale(LC_TIME, array('de_DE.UTF-8', 'de_DE', 'de_DE.ISO-8859-1', 'de_DE.ISO-8859-15'));

		if(!is_numeric($date)) {
			$date = self::to_timestamp($date);
			if($date === false) return $date;
		}

		if($format == 'timestamp') return $date;

		$fmt = '';
		$prepend = '';
		if(substr($format, 0, 5) == 'nice:') {
			if(strftime('%d.%m.%Y', $date) == strftime('%d.%m.%Y', $portal->getTime())) {
				if($time == true) $format = 'onlytime';
				else $format = '';
				$prepend = 'Heute';
			} elseif(strftime('%d.%m.%Y', $date) == strftime('%d.%m.%Y', $portal->getTime() - 86400)) {
				if($time == true) $format = 'onlytime';
				else $format = '';
				$prepend = 'Gestern';
			} elseif(strftime('%d.%m.%Y', $date) == strftime('%d.%m.%Y', $portal->getTime() + 86400)) {
				if($time == true) $format = 'onlytime';
				else $format = '';
				$prepend = 'Morgen';
			} else {
				$format = substr($format, 5);
			}
		} elseif(substr($format, 0, 7) == 'custom:') {
			$fmt = substr($format, 7);
			$format = '';
			$time = false;
		}

		if($format == 'short') $fmt = '%d.%m.%y';
		elseif($format == 'veryshort') $fmt = '%d.%m.';
		elseif($format == 'medium') $fmt = '%d.%m.%Y';
		elseif($format == 'long') $fmt = '%d. %B %Y';
		elseif($format == 'extra') $fmt = '%A, %d. %B %Y';
		elseif($format == 'day') $fmt = '%d';
		elseif($format == 'monthnum') $fmt = '%m';
		elseif($format == 'shortmonth') $fmt = '%b';
		elseif($format == 'month') $fmt = '%B';
		elseif($format == 'shortyear') $fmt = '%y';
		elseif($format == 'year') $fmt = '%Y';
		elseif($format == 'onlydate') $fmt = '%d.%m.';
		elseif($format == 'onlydatelong') $fmt = '%d. %B';
		elseif($format == 'onlytime') {
			$fmt = '%H:%M';
			$time = false;
		} elseif($format == 'rss') {
			$ret = date(DATE_RSS, $date);
			if($prepend != '') $ret = $prepend . ' ' . $ret;
			return $ret;
		} elseif($format == 'sitemap') {
			$ret = date(DATE_ATOM, $date);
			if($prepend != '') $ret = $prepend . ' ' . $ret;
			return $ret;
		}
		if($time == true) $fmt .= ' %H:%M' . ($seconds == true ? ':%S' : '');

		if($fmt != '') $ret = strftime($fmt, $date);
		else $ret = '';

		if($prepend != '') $ret = trim($prepend . ' ' . $ret);
		return $ret;
	}

	/**
	 * Get the month difference of two dates
	 *
	 * Gets the difference in months of two given dates.
	 * The days are ignored, so the difference between 2010-01-21 and 2010-05-01 is 4!
	 *
	 * @access public
	 * @param mixed $date_from the beginning date, either
	 * - int (unix timestamp)
	 * - date string yyyy-mm-dd[ hh:mm:ss]
	 * - date string dd.mm.yyyy[ hh:mm:ss]
	 * @param mixed $date_to the ending date, either
	 * - int (unix timestamp)
	 * - date string yyyy-mm-dd[ hh:mm:ss]
	 * - date string dd.mm.yyyy[ hh:mm:ss]
	 * @param bool $return_years if set to true, the function returns an array of years and months instead of months
	 * @param bool $include_both if set to true, the starting AND ending month is included, so the month count is +1
	 * @return mixed either int (months) or array of int (0 => years, 1 => months) or FALSE on invalid dates
	 */
	public static function months_between($date_from, $date_to, $return_years = false, $include_both = false) {
		$date_from = self::to_string($date_from, 'custom:%Y%m');
		if($date_from === false) return $date_from;

		$date_to = self::to_string($date_to, 'custom:%Y%m');
		if($date_to === false) return $date_to;

		$date_from = intval($date_from);
		$date_to = intval($date_to);

		if($date_to < $date_from) return false;

		$result = $date_to - $date_from;
		if($include_both == true) $result++;

		$years = floor($result / 100);
		$months = $result % 100;
		if($months > 12) $months -= 88;
		elseif($months == 12) {
			$months = 0;
			$years++;
		}
		if($return_years == true) return array($years, $months);

		$months += ($years * 12);
		return $months;
	}

	/**
	 * Get the day difference of two dates
	 *
	 * Gets the difference in days of two given dates.
	 *
	 * @access public
	 * @param mixed $date_from the beginning date, either
	 * - int (unix timestamp)
	 * - date string yyyy-mm-dd[ hh:mm:ss]
	 * - date string dd.mm.yyyy[ hh:mm:ss]
	 * @param mixed $date_to the ending date, either
	 * - int (unix timestamp)
	 * - date string yyyy-mm-dd[ hh:mm:ss]
	 * - date string dd.mm.yyyy[ hh:mm:ss]
	 * @param bool $include_both if set to true, the starting AND ending day is included, so the day count is +1
	 * @return mixed either int (days) or FALSE on invalid dates
	 */
	public static function days_between($date_from, $date_to, $include_both = false) {
		$date_from = self::to_string($date_from, 'custom:%Y-%m-%d');
		if($date_from === false) return $date_from;
		list($y, $m, $d) = explode('-', $date_from);
		$ts_from = mktime(0, 0, 0, $m, $d, $y);

		$date_to = self::to_string($date_to, 'custom:%Y-%m-%d');
		if($date_to === false) return $date_to;
		list($y, $m, $d) = explode('-', $date_to);
		$ts_to = mktime(0, 0, 0, $m, $d, $y);

		$result = $ts_to - $ts_from;
		if($include_both == true) $result++;

		$days = floor($result / (3600 * 24));

		return $days;
	}

	/**
	 * Check if one date is before another
	 *
	 * @access public
	 * @param mixed $date_1 the first date, either
	 * - int (unix timestamp)
	 * - date string yyyy-mm-dd[ hh:mm:ss]
	 * - date string dd.mm.yyyy[ hh:mm:ss]
	 * @param mixed $date_2 the second date, either
	 * - int (unix timestamp)
	 * - date string yyyy-mm-dd[ hh:mm:ss]
	 * - date string dd.mm.yyyy[ hh:mm:ss]
	 * @return mixed either int (1 if the first date is earlier, -1 if the second date is earlier, 0 if both are the same) or FALSE on invalid dates
	 */
	public static function compare($date_1, $date_2) {
		$ts_1 = self::to_timestamp($date_1);
		if($ts_1 === false) return false;
		$ts_2 = self::to_timestamp($date_2);
		if($ts_2 === false) return false;

		if($ts_1 < $ts_2) return 1;
		elseif($ts_1 > $ts_2) return -1;
		else return 0;
	}



	/**
	 * Convert date to sql format
	 *
	 * Converts a date from different formats to the sql format if possible
	 *
	 * @access public
	 * @param string $date date string in forms
	 * - dd.mm.yy
	 * - yyyy-mm-dd
	 * - yy-mm-dd
	 * - yyyy/mm/dd
	 * - dd.mm.yy
	 * - all formats can have time information HH:MM:SS appended
	 * @param bool $no_time if true, the resulting sql date is without time part even if time was part of the input
	 * @return mixed sql date string on success, error object otherwise
	 */
	public static function sql_date($date = false, $no_time = false) {
		global $portal;

		$result = '';
		$time = '';

		if($date === false) $date = $portal->getTime(true);

		if(is_numeric($date)) {
			return $no_time ? strftime('%Y-%m-%d', $date) : strftime('%Y-%m-%d %H:%M:%S', $date);
		}

		if(preg_match('/^(.*)(\d{1,2}:\d{1,2}(:\d{1,2})?)(\D|$)/', $date, $matches)) {
			$date = $matches[1];
			$time = ' ' . $matches[2];
		}
		if(preg_match('/(^|\D)(\d{4,4})-(\d{1,2})-(\d{1,2})(\D|$)/', $date, $result)) {
			$day = $result[4];
			$month = $result[3];
			$year = $result[2];
		} elseif(preg_match('/(^|\D)(\d{4,4})\/(\d{1,2})\/(\d{1,2})(\D|$)/', $date, $result)) {
			$day = $result[4];
			$month = $result[3];
			$year = $result[2];
		} elseif(preg_match('/(^|\D)(\d{2,2})-(\d{1,2})-(\d{1,2})(\D|$)/', $date, $result)) {
			$day = $result[4];
			$month = $result[3];
			$year = $result[2];
		} elseif(preg_match('/(^|\D)(\d{1,2})\.(\d{1,2})\.(\d{4,4})(\D|$)/', $date, $result)) {
			$day = $result[2];
			$month = $result[3];
			$year = $result[4];
		} elseif(preg_match('/(^|\D)(\d{1,2})\.(\d{1,2})\.(\d{2,2})(\D|$)/', $date, $result)) {
			$day = $result[2];
			$month = $result[3];
			$year = $result[4];
		} else {
			return false;
		}
		if($no_time == true) $time = '';

		$day = str_pad(intval($day), 2, '0', STR_PAD_LEFT);
		$month = str_pad(intval($month), 2, '0', STR_PAD_LEFT);
		$year = intval($year);

		$valid = checkdate($month, $day, $year);
		if(!$valid) return false;

		return $year . '-' . $month . '-' . $day . $time;
	}



	/**
	 * Get information if given date is leap year
	 *
	 * @access public
	 * @param mixed $date Date to check
	 * @return bool true if leap year, false otherwise
	 */
	public static function is_leap_year($date) {
		// check if only year was given
		if(is_numeric($date) && $date < 10000) $date .= '-01-01';

		$ts = self::to_timestamp($date);
		if($ts === false) return false;

		if(date('L', $ts) == 1) return true;
		else return false;
	}



	/**
	 * Get the last day of the month
	 *
	 * @access public
	 * @param int $month the month to get the last day for
	 * @param int $year the corresponding year (for february in leap years)
	 * @return bool true if leap year, false otherwise
	 */
	public static function last_day($month, $year = false) {
		switch($month) {
		case 1:
		case 3:
		case 5:
		case 7:
		case 8:
		case 10:
		case 12:
			return 31;
			break;
		case 2:
			return $year !== false && self::is_leap_year($year) ? 29 : 28;
			break;
		default:
			return 30;
			break;
		}
	}

	/**
	 * Get age for given date
	 *
	 * Returns the age for a given date if possible
	 *
	 * @access public
	 * @param string $date see ISPConfigDateTime::sql_date() for possible values
	 * @return mixed int of age if successful, error object otherwise
	 * @see ISPConfigDateTime::sql_date
	 */
	public static function calc_age($date) {
		global $portal;

		$date = self::sql_date($date);
		if($date === false) return $date;

		list($year, $month, $day) = explode('-', $date);
		list($curyear, $curmonth, $curday) = explode('-', strftime('%Y-%m-%d', $portal->getTime()));

		$year_diff = $curyear - $year;
		$month_diff = $curmonth - $month;
		$day_diff = $curday - $day;

		if($day_diff < 0) $month_diff--;
		if($month_diff < 0) $year_diff--;
		if($year_diff < 0) $year_diff = 0;

		return $year_diff;
	}

}
