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

//* The purpose of this library is to provide some general functions.
//* This class is loaded automatically by the ispconfig framework.

abstract class ISPConfigRequest {
	/**
	 * Get header data and contents from an url
	 *
	 * Calls an url and returns an array containing the http header and the page content
	 *
	 * @access public
	 * @param string $url the url to call
	 * @param string $store_in the file to store the data in instead of returning them
	 * @return array The array with header data at index 0 and page content at index 1, returns boolean false on error. If $store_in is set only the headers are returned
	 */


	public static function get_with_headers($url, $store_in = null, $follow_redirects = false, $user_agent = false) {
		if($follow_redirects === true) $follow_redirects = 5;
		elseif($follow_redirects !== false) $follow_redirects--;

		if(!$user_agent) $user_agent = 'pxFW GET proxy';

		$url_info = parse_url($url);
		if(isset($url_info['scheme']) && $url_info['scheme'] == 'https') {
			$port = isset($url_info['port']) ? $url_info['port'] : 443;
			@$fp = fsockopen('tls://' . $url_info['host'], $port, $errno, $errstr, 10);
		} else {
			$port = isset($url_info['port']) ? $url_info['port'] : 80;
			@$fp = fsockopen($url_info['host'], $port, $errno, $errstr, 10);
		}

		if($store_in) {
			$outfp = fopen($store_in, 'w');
			if(!$outfp) return false;
		}
		if($fp) {
			stream_set_timeout($fp, 10);
			$head = 'GET ' . (isset($url_info['path']) ? $url_info['path'] : '/') . (isset($url_info['query']) ? '?' . $url_info['query'] : '');
			$head .= " HTTP/1.0\r\nHost: " . (isset($url_info['host']) ? $url_info['host'] : '') . "\r\n";
			$head .= "User-Agent: " . $user_agent . "\r\n";
			if(isset($url_info['user'])) {
				if(!array_key_exists('pass', $url_info)) $url_info['pass'] = '';
				$head .= "Authorization: basic " . base64_encode($url_info['user'] . ':' . $url_info['pass']) . "\r\n";
			}
			$head .= "Connection: Close\r\n";
			$head .= "Accept: */*\r\n\r\n";

			$data = '';
			$eoheader = false;
			fputs($fp, $head);
			while(!feof($fp)) {
				if($header = fgets($fp, 1024)) {
					if($eoheader == true) {
						if($store_in) fputs($outfp, $header);
						else $data .= $header;
						continue;
					}

					if ($header == "\r\n") {
						$eoheader = true;
						continue;
					} else {
						$header = trim($header);
					}
					$sc_pos = strpos($header, ':');
					if($sc_pos === false) {
						$headers['status'] = $header;
						$headers['http_code'] = intval(preg_replace('/^HTTP\/\d+\.\d+\s+(\d+)\s+.*$/', '$1', $header));
					} else {
						$label = substr($header, 0, $sc_pos);
						$value = substr($header, $sc_pos + 1);
						$headers[strtolower($label)] = trim($value);
					}
				}
			}
			fclose($fp);
			if(isset($headers['http_code']) && isset($headers['location']) && ($headers['http_code'] == 301 || $headers['http_code'] == 302) && $follow_redirects > 0) {
				if($store_in) fclose($outfp);
				return $self::get_with_headers($headers['location'], $store_in, $follow_redirects);
			}
			if($store_in) {
				fclose($outfp);

				$code = intval(preg_replace('/^HTTP\/\d+\.\d+\s+(\d+)\s+.*$/', '$1', $headers['status']));
				if($code != 200) {
					return false;
				}
				return $headers;
			} else {
				return array($headers, $data);
			}
		} else {
			if($store_in) {
				fclose($outfp);
				@unlink($store_in);
			}
			return false;
		}
	}

	/**
	 * Gets the content of an url
	 *
	 * Checks for the php function file_get_contents and uses an alternative if not found
	 *
	 * @access public
	 * @param string $url url to get
	 * @return string url data including headers
	 * @see file_get_contents
	 */
	public static function get($url) {
		if(function_exists('file_get_contents')) return file_get_contents($url);

		$fp = fopen($url, 'r');
		$data = '';
		while(!feof($fp)) {
			$data .= fgets($fp, 8192);
		}
		fclose($fp);

		return $data;
	}


	/**
	 * Make a post request and get data
	 *
	 * Calls an url with a post request and returns the data - and optionally the header content
	 *
	 * @access public
	 * @param string $url the url to call
	 * @param string $data the post data to send
	 * @param bool $get_headers if true, the function will return an array like PXUrl::get_with_headers(), otherwise the content is returned as a string
	 * @return mixed Content data as string or - if get_headers is true - the array with header data at index 0 and page content at index 1
	 * @see get_url_and_headers
	 */
	public static function post($url, $data, $get_headers = false, $user_agent = false) {
		$url_info = parse_url($url);
		if((isset($url_info['scheme']) && $url_info['scheme'] == 'https') || $url_info['port'] == 443) {
			$port = (!isset($url_info['port']) || !$url_info['port'] || $url_info['port'] == 443 || $url_info['port'] == 80) ? 443 : $url_info['port'];
			@$fp = fsockopen('tls://' . $url_info['host'], $port, $errno, $errstr, 10);
		} else {
			$port = isset($url_info['port']) ? $url_info['port'] : 80;
			@$fp = fsockopen($url_info['host'], $port, $errno, $errstr, 10);
		}

		if(!$fp) return '';

		if(!$user_agent) $user_agent = 'pxFW GET proxy';

		$header = 'POST ' . (isset($url_info['path']) ? $url_info['path'] : '/') . (isset($url_info['query']) ? '?' . @$url_info['query'] : '') . " HTTP/1.1\r\n";
		$header .= "Host: " . @$url_info['host'] . "\r\n";
		$header .= "User-Agent: " . $user_agent . "\r\n";
		if(isset($url_info['user'])) {
			if(!array_key_exists('pass', $url_info)) $url_info['pass'] = '';
			$header .= "Authorization: basic " . base64_encode($url_info['user'] . ':' . $url_info['pass']) . "\r\n";
		}
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($data) . "\r\n";
		$header .= "Connection: close\r\n\r\n";
		$header .= $data . "\r\n\r\n";

		fwrite($fp, $header);

		$response = '';
		$eoheader = false;
		$header = '';
		$tmpdata = '';
		$chunked = false;
		$chunklen = 0;

		while(!feof($fp)) {
			if($header = @fgets($fp, 1024)) {
				if($eoheader == true) {
					$response .= $header;
					continue;
				}

				if ($header == "\r\n") {
					$eoheader = true;
					continue;
				} else {
					$tmpdata .= $header;
					if(preg_match('/Transfer-Encoding:\s+chunked/i', $tmpdata)) $chunked = true;
				}
			}
		}
		//var_dump($response, $chunked, $header);
		if($chunked == true) {
			$lines = explode("\n", $response);
			$response = '';
			$chunklen = 0;
			foreach($lines as $line) {
				$line .= "\n";
				if($chunklen <= 0) {
					if(preg_match('/^([0-9a-f]+)\s*$/is', $line, $matches)) {
						$chunklen = hexdec($matches[1]);
					}
					continue;
				}

				if(strlen($line) > $chunklen) {
					//echo "Warnung: " . strlen($line) . " > " . $chunklen . "\n";
					$line = substr($line, 0, $chunklen);
				}
				$response .= $line;
				$chunklen -= strlen($line);
			}

			$start = strpos($response, '<?xml');
			$end = strrpos($response, '>');
			if($start !== false && $end !== false) $response = substr($response, $start, $end - $start + 1);
		}

		fclose($fp);

		if($get_headers == true) {
			$tmpheaders = explode("\n", $tmpdata);
			$headers = array();
			foreach($tmpheaders as $cur) {
				if(preg_match('/^(\w+)\:\s*(.*)$/is', $cur, $matches)) {
					$headers["$matches[1]"] = trim($matches[2]);
				} elseif(strpos($cur, ':') === false && substr($cur, 0, 5) === 'HTTP/') {
					$headers['status'] = $header;
					$headers['http_code'] = intval(preg_replace('/^HTTP\/\d+\.\d+\s+(\d+)\s+.*$/', '$1', $header));
				}
			}
			return array($headers, $response);
		} else return $response;
	}

}

?>
