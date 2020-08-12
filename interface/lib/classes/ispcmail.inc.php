<?php

/*
Copyright (c) 2012, Marius Cramer, pixcept KG
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
 * email class
 *
 * @package pxFramework
 *
 */


class ispcmail {

	/**#@+
     * @access private
     */
	private $html_part;
	private $text_part;

	private $headers;

	private $_logged_in = false;
	private $_smtp_conn = null;

	private $_crlf = "\n";

	private $attach_type = 'application/octet-stream';
	private $attachments;
	private $mime_boundary;
	private $body = '';
	private $_mail_sender = '';
	private $_sent_mails = 0;
	private $user_agent = 'ISPConfig/3 (Mailer Class)';
	/**#@-*/



	/**
	 * set the mail charset
	 */
	private $mail_charset  = 'UTF-8';//'ISO-8859-1';

	/**#@+
     * Provide smtp credentials for smtp mail sending
     *
     * @access public
     */

	/**
	 * if set to true smtp is used instead of mail() to send emails
	 * @see mail
	 */
	private $use_smtp = false;

	/**
	 * the smtp helo string - use the mail server name here!
	 */
	private $smtp_helo = '';

	/**
	 * the smtp server to send mails
	 */
	private $smtp_host = '';

	/**
	 * the smtp port
	 */
	private $smtp_port = 25;

	/**
	 * if the smtp server needs authentication you can set the smtp user here
	 */
	private $smtp_user = '';

	/**
	 * if the smtp server needs authentication you can set the smtp password here
	 */
	private $smtp_pass = '';

	/**
	 * If you want to use tls/ssl specify it here
	 */
	private $smtp_crypt = ''; // tls or ssl
	/**
	 * How many mails should be sent via one single smtp connection
	 */
	private $smtp_max_mails = 20;

	/**
	 * Should the mail be signed
	 */
	private $sign_email = false;

	/**
	 * The cert and key to use for email signing
	 */
	private $sign_key = '';
	private $sign_key_pass = '';
	private $sign_cert = '';
	private $sign_bundle = '';
	private $_is_signed = false;

	/**
	 * get disposition notification
	 */
	private $notification = false;
	/**#@-*/

	public function __construct($options = array()) {
		$rand = md5(microtime());
		$this->mime_boundary = '==Multipart_Boundary_x' . $rand . 'x';

		$this->headers = array();
		$this->attachments = array();

		$this->headers['MIME-Version'] = '1.0';
		$this->headers['User-Agent'] = $this->user_agent;
		if(is_array($options) && count($options) > 0) $this->setOptions($options);
	}

	public function __destruct() {
		$this->finish();
	}



	/**
	 * Set option
	 *
	 * @param string $key the option to set
	 * @param string $value the option value to set
	 */
	public function setOption($key, $value) {
		switch($key) {
		case 'smtp_helo':
			$this->smtp_helo = $value;
			break;
		case 'smtp_host':
			$this->smtp_host = $value;
			break;
		case 'smtp_server':
			$this->smtp_host = $value;
			break;
		case 'smtp_port':
			if(intval($value) > 0) $this->smtp_port = $value;
			break;
		case 'smtp_user':
			$this->smtp_user = $value;
			break;
		case 'smtp_pass':
			$this->smtp_pass = $value;
			break;
		case 'smtp_max_mails':
			$this->smtp_max_mails = intval($value);
			if($this->smtp_max_mails < 1) $this->smtp_max_mails = 1;
			break;
		case 'use_smtp':
			$this->use_smtp = ($value == true ? true : false);
			if($value == true) $this->_crlf = "\r\n";
			break;
		case 'smtp_crypt':
			if($value != 'ssl' && $value != 'tls') $value = '';
			$this->smtp_crypt = $value;
			break;
		case 'sign_email':
			$this->sign_email = ($value == true ? true : false);
			break;
		case 'sign_key':
			$this->sign_key = $value;
			break;
		case 'sign_key_pass':
			$this->sign_key_pass = $value;
			break;
		case 'sign_cert':
			$this->sign_cert = $value;
			break;
		case 'sign_bundle':
			$this->sign_bundle = $value;
			break;
		case 'mail_charset':
			$this->mail_charset = $value;
			break;
		case 'notify':
			$this->notification = ($value == true ? true : false);
			break;
		}
	}



	/** Detect the helo string if none given
	 *
	 */
	private function detectHelo() {
		if(isset($_SERVER['HTTP_HOST'])) $this->smtp_helo = (strpos($_SERVER['HTTP_HOST'], ':') !== false ? substr($_SERVER['HTTP_HOST'], 0, strpos($_SERVER['HTTP_HOST'], ':')) : $_SERVER['HTTP_HOST']);
		elseif(isset($_SERVER['SERVER_NAME'])) $this->smtp_helo = $_SERVER['SERVER_NAME'];
		else $this->smtp_helo = php_uname('n');
		if($this->smtp_helo == '') $this->smtp_helo = 'localhost';
		return $this->smtp_helo;
	}



	/**
	 * Set options
	 *
	 * @param array $options the options to set as an associative array key => value
	 */
	public function setOptions($options) {
		foreach($options as $key => $value) $this->setOption($key, $value);
	}



	/**
	 * Read a file's contents
	 *
	 * Simply gets the file's content
	 *
	 * @access public
	 * @param string $filename name and path of file to read
	 * @return string file content (can be binary)
	 */
	public function read_File($filename) {
		$content = '';

		$fp = fopen($filename, 'r');
		if(!$fp) return false;

		while(!feof($fp)) {
			$content .= fread($fp, 1024);
		}
		fclose($fp);

		return $content;
	}



	/**
	 * set smtp connection encryption
	 *
	 * @access public
	 * @param string $mode encryption mode (tls, ssl or empty string)
	 */
	public function setSMTPEncryption($mode = '') {
		if($mode != 'ssl' && $mode != 'tls') $mode = '';
		$this->smtp_crypt = $mode;
	}

	/**
	 * set a mail header
	 *
	 * Sets a single mail header to a given value
	 *
	 * @access public
	 * @param string $header header name to set
	 * @param string $value value to set in header field
	 */
	public function setHeader($header, $value) {
		if(strtolower($header) == 'bcc') $header = 'Bcc';
		elseif(strtolower($header) == 'cc') $header = 'Cc';
		elseif(strtolower($header) == 'from') $header = 'From';
		$this->headers["$header"] = $value;
	}



	/**
	 * get a mail header value
	 *
	 * Returns a value of a single mail header
	 *
	 * @access public
	 * @param string $header header name to get
	 * @return string header value
	 */
	public function getHeader($header) {
		if(strtolower($header) == 'bcc') $header = 'Bcc';
		elseif(strtolower($header) == 'cc') $header = 'Cc';
		elseif(strtolower($header) == 'from') $header = 'From';
		return isset($this->headers["$header"]) ? $this->headers["$header"] : '';
	}



	/**
	 * Set email sender
	 *
	 * Sets the email sender and optionally the sender's name
	 *
	 * @access public
	 * @param string $email sender email address
	 * @param string $name sender name
	 */
	public function setSender($email, $name = '') {
		if($name) $header = '"' . $name . '" <' . $email . '>';
		else $header = '<' . $email . '>';

		$this->_mail_sender = $email;

		$this->setHeader('From', $header);
	}



	/**
	 * Set mail subject
	 *
	 * @access public
	 * @param string $subject the mail subject
	 * @return string where-string for db query
	 */
	public function setSubject($subject) {
		$this->setHeader('Subject', $subject);
	}



	/**
	 * Get current mail subject
	 *
	 * @access public
	 * @return string mail subject
	 */
	public function getSubject() {
		return $this->headers['Subject'];
	}



	/**
	 * Set mail content
	 *
	 * Sets the mail html and plain text content
	 *
	 * @access public
	 * @param string $text plain text mail content (can be empty)
	 * @param string $html html mail content
	 */
	public function setMailText($text, $html = '') {
		$this->text_part = $text;
		$this->html_part = $html;
	}



	/**
	 * Read and attach a file
	 *
	 * Reads a file and attaches it to the current email
	 *
	 * @access public
	 * @param string $filename the file to read and attach
	 * @param string $display_name the name that will be displayed in the mail
	 * @see read_File
	 */
	public function readAttachFile($filename, $display_name = '') {
		if($display_name == '') {
			$path_parts = pathinfo($filename);
			$display_name = $path_parts["basename"];
			unset($path_parts);
		}
		$this->attachFile($this->read_File($filename), $display_name);
	}



	/**
	 * Attach a file
	 *
	 * Attaches a string (can be binary) as a file to the mail
	 *
	 * @access public
	 * @param string $content attachment data string
	 * @param string $filename name for file attachment
	 */
	public function attachFile($content, $filename) {
		$attachment = array('content' => $content,
			'filename' => $filename,
			'type' => 'application/octet-stream',
			'encoding' => 'base64'
		);
		$this->attachments[] = $attachment;
	}



	/**
	 * @access private
	 */
	private function create() {
		$attach = false;
		$html = false;
		$text = false;

		if($this->html_part) $html = true;
		if($this->text_part) $text = true;
		if(count($this->attachments) > 0) $attach = true;

		$textonly = false;
		$htmlonly = false;
		if($text == true && $html == false && $attach == false) {
			// only text
			$content_type = 'text/plain; charset="' . strtolower($this->mail_charset) . '"';
			$textonly = true;
		} elseif($text == true && $html == false && $attach == true) {
			// text and attachment
			$content_type = 'multipart/mixed; boundary="' . $this->mime_boundary . '"';
		} elseif($html == true && $text == true && $attach == false) {
			// html only (or text too)
			$content_type = 'multipart/alternative; boundary="' . $this->mime_boundary . '"';
		} elseif($html == true && $text == false && $attach == false) {
			// html only (or text too)
			$content_type = 'text/html; charset="' . strtolower($this->mail_charset) . '"';
			$htmlonly = true;
		} elseif($html == true && $attach == true) {
			// html and attachments
			$content_type = 'multipart/mixed; boundary="' . $this->mime_boundary . '"';
		}

		$this->headers['Content-Type'] = $content_type;

		if($textonly == false && $htmlonly == false) {
			$this->body = "This is a multi-part message in MIME format.\n\n";

			if($text) {
				/*$this->body .= "--{$this->mime_boundary}\n" .
                              "Content-Type:text/plain; charset=\"" . strtolower($this->mail_charset) . "\"\n" .
                              "Content-Transfer-Encoding: 7bit\n\n" . $this->text_part . "\n\n";*/
				$this->body .= "--{$this->mime_boundary}\n" .
					"Content-Type:text/plain; charset=\"UTF-8\"\n" .
					"Content-Transfer-Encoding: 8bit\n\n" . $this->text_part . "\n\n";
			}

			if($html) {
				/*$this->body .= "--{$this->mime_boundary}\n" .
                               "Content-Type:text/html; charset=\"" . strtolower($this->mail_charset) . "\"\n" .
                               "Content-Transfer-Encoding: 7bit\n\n" . $this->html_part . "\n\n";*/
				$this->body .= "--{$this->mime_boundary}\n" .
					"Content-Type:text/html; charset=\"UTF-8\"\n" .
					"Content-Transfer-Encoding: 8bit\n\n" . $this->html_part . "\n\n";
			}

			if($attach) {
				foreach($this->attachments as $att) {
					$this->body .= "--{$this->mime_boundary}\n" .
						"Content-Type: " . $att['type'] . "; name=\"" . $att['filename'] . "\"\n" .
						"Content-Transfer-Encoding: base64\n" .
						"Content-Disposition: attachment;\n\n" .
						chunk_split(base64_encode($att['content'])) . "\n\n";
				}
			}
			$this->body .= "--{$this->mime_boundary}--\n";
		} elseif($htmlonly == true) {
			$this->body = $this->html_part;
		} else {
			$this->body = $this->text_part;
		}

		if (isset($this->body)) {
			// Add message ID header
			$message_id = sprintf('<%s.%s@%s>', base_convert(time(), 10, 36), base_convert(rand(), 10, 36), $this->smtp_helo != '' ? $this->smtp_helo : $this->detectHelo());
			$this->headers['Message-ID'] = $message_id;
			return true;
		} else {
			return false;
		}
	}



	/**
	 * Function to sign an email body
	 */
	private function sign() {
		if($this->sign_email == false || $this->sign_key == '' || $this->sign_cert == '') return false;
		if(function_exists('openssl_pkcs7_sign') == false) return false;

		$tmpin = tempnam(sys_get_temp_dir(), 'sign');
		$tmpout = tempnam(sys_get_temp_dir(), 'sign');
		if(!file_exists($tmpin) || !is_writable($tmpin)) return false;

		file_put_contents($tmpin, 'Content-Type: ' . $this->getHeader('Content-Type') . "\n\n" . $this->body);
		$tmpf_key = tempnam(sys_get_temp_dir(), 'sign');
		file_put_contents($tmpf_key, $this->sign_key);
		$tmpf_cert = tempnam(sys_get_temp_dir(), 'sign');
		file_put_contents($tmpf_cert, $this->sign_cert);
		if($this->sign_bundle != '')  {
			$tmpf_bundle = tempnam(sys_get_temp_dir(), 'sign');
			file_put_contents($tmpf_bundle, $this->sign_bundle);
			openssl_pkcs7_sign($tmpin, $tmpout, 'file://' . realpath($tmpf_cert), array('file://' . realpath($tmpf_key), $this->sign_key_pass), array(), PKCS7_DETACHED, realpath($tmpf_bundle));
		} else {
			openssl_pkcs7_sign($tmpin, $tmpout, 'file://' . realpath($tmpf_cert), array('file://' . realpath($tmpf_key), $this->sign_key_pass), array());
		}
		unlink($tmpin);
		unlink($tmpf_cert);
		unlink($tmpf_key);
		if(file_exists($tmpf_bundle)) unlink($tmpf_bundle);

		if(!file_exists($tmpout) || !is_readable($tmpout)) return false;
		$this->body = file_get_contents($tmpout);
		unlink($tmpout);

		unset($this->headers['Content-Type']);
		unset($this->headers['MIME-Version']);

		$this->_is_signed = true;
	}

	private function _char_to_hex($matches) {
		return '=' . strtoupper(dechex(ord($matches[1])));
	}



	/**
	 * Function to encode a header if necessary
	 * according to RFC2047
	 * @access private
	 */
	private function _encodeHeader($input, $charset = 'ISO-8859-1') {
		preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches);
		foreach ($matches[1] as $value) {
			$replacement = preg_replace_callback('/([\x20\x80-\xFF])/', array($this, '_char_to_hex'), $value);
			$input = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
		}

		return $input;
	}



	/**
	 * Function to encode the subject if necessary
	 * according to RFC2047
	 * @access private
	 */
	private function _encodeSubject($input, $charset = 'ISO-8859-1') {
		/*
		if($charset == 'UTF-8' && function_exists('imap_8bit')) {
			$input = "=?utf-8?Q?" . imap_8bit($input) . "?=";
		} else {
			preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches);
			foreach ($matches[1] as $value) {
				$replacement = preg_replace('/([\x20\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
				$input = str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input);
			}
		}*/
		$input='=?UTF-8?B?'.base64_encode($input).'?=';

		return $input;
	}



	/**
	 * @access private
	 */
	private function _smtp_login() {
		$this->_smtp_conn = fsockopen(($this->smtp_crypt == 'ssl' ? 'tls://' : '') . $this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
		if(empty($this->_smtp_conn)) return false;
		$response = fgets($this->_smtp_conn, 515);

		//Say Hello to SMTP
		if($this->smtp_helo == '') $this->detectHelo();
		fputs($this->_smtp_conn, 'HELO ' . $this->smtp_helo . $this->_crlf);
		$response = fgets($this->_smtp_conn, 515);

		// ENCRYPTED?
		if($this->smtp_crypt == 'tls') {
			fputs($this->_smtp_conn, 'STARTTLS' . $this->_crlf);
			fgets($this->_smtp_conn, 515);
			
			$crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

			if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
				$crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
				$crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
			}
			stream_context_set_option($this->_smtp_conn, 'ssl', 'verify_host', false);
			stream_context_set_option($this->_smtp_conn, 'ssl', 'verify_peer', false);
			stream_context_set_option($this->_smtp_conn, 'ssl', 'verify_peer_name', false);
			stream_context_set_option($this->_smtp_conn, 'ssl', 'allow_self_signed', true);
			if (stream_socket_enable_crypto($this->_smtp_conn, true, $crypto_method) != true) {
				return false;
			}
		}

		//AUTH LOGIN
		fputs($this->_smtp_conn, 'AUTH LOGIN' . $this->_crlf);
		$response = fgets($this->_smtp_conn, 515);

		//Send username
		fputs($this->_smtp_conn, base64_encode($this->smtp_user) . $this->_crlf);
		$response = fgets($this->_smtp_conn, 515);

		//Send password
		fputs($this->_smtp_conn, base64_encode($this->smtp_pass) . $this->_crlf);
		$response = fgets($this->_smtp_conn, 515);

		$this->_logged_in = true;
		return true;
	}



	/**
	 * @access private
	 */
	private function _smtp_close() {
		$this->_logged_in = false;

		if(empty($this->_smtp_conn)) {
			return false;
		}

		fputs($this->_smtp_conn, 'QUIT' . $this->_crlf);
		$response = @fgets($this->_smtp_conn, 515);
		return true;
	}

	private function _extract_names($data) {
		$senders = array();

		$data = stripslashes(preg_replace("'(\t|\r|\n)'", '', $data));

		if(trim($data) == '') return $senders;

		$armail = array();
		$counter = 0;  $inthechar = 0;
		$chartosplit = ',;'; $protectchar = '"'; $temp = '';
		$closed = 1;

		for($i = 0; $i < strlen($data); $i++) {
			$thischar = $data[$i];
			if($thischar == '<' && $closed) $closed = 0;
			if($thischar == '>' && !$closed) $closed = 1;
			if($thischar == $protectchar) $inthechar = ($inthechar) ? 0 : 1;
			if((strpos($chartosplit, $thischar) !== false) && !$inthechar && $closed) {
				$armail[] = $temp;
				$temp = '';
			} else {
				$temp .= $thischar;
			}
		}

		if(trim($temp) != '') {
			$armail[] = trim($temp);
			unset($temp);
		}

		foreach($armail as $thisPart) {
			$thisPart = trim(preg_replace('/^"(.*)"$/i', '$1', trim($thisPart)));
			if($thisPart != '') {
				$email = '';
				$name = '';
				if(preg_match('/(.*)<(.*)>/i', $thisPart, $matches)) {
					$email = trim($matches[2]);
					$name = trim($matches[1]);
				} else {
					if(preg_match('/([-a-z0-9_$+.]+@[-a-z0-9_.]+[-a-z0-9_]+)((.*))/i', $thisPart, $matches)) {
						$email = $matches[1];
						$name = $matches[2];
					} else {
						$email = $thisPart;
					}
				}

				$email = preg_replace('/<(.*)\\>/', '$1', $email);
				$name = preg_replace('/"(.*)"/', '$1', trim($name));
				$name = preg_replace('/\((.*)\)/', '$1', $name);

				if($name == '') $name = $email;
				if($email == '') $email = $name;
				$senders[] = array(
					'name' => $name,
					'mail' => $email
				);
				unset($name);
				unset($email);
			}
		}
		unset($armail);
		unset($thisPart);

		return $senders;
	}

	/**
	 * Send the mail to one or more recipients
	 *
	 * The recipients can be either a string (1 recipient email without name) or an associative array of recipients with names as keys and email addresses as values.
	 *
	 * @access public
	 * @param mixed $recipients one email address or array of recipients with names as keys and email addresses as values
	 */
	public function send($recipients) {
		if(!is_array($recipients)) $recipients = array($recipients);

		if($this->use_smtp == true) $this->_crlf = "\r\n";
		else $this->_crlf = "\n";

		$this->create();
		if($this->sign_email == true) $this->sign();

		$subject = '';
		if (!empty($this->headers['Subject'])) {
			//$subject = $this->_encodeHeader($this->headers['Subject'], $this->mail_charset);
			$subject = $this->headers['Subject'];

			//$enc_subject = $this->_encodeHeader($subject, $this->mail_charset);
			$enc_subject = $this->_encodeSubject($subject, $this->mail_charset);
			unset($this->headers['Subject']);
		}

		if($this->notification == true) $this->setHeader('Disposition-Notification-To', $this->getHeader('From'));

		unset($this->headers['To']); // always reset the To header to prevent from sending to multiple users at once
		$this->headers['Date'] = date('r'); //date('D, d M Y H:i:s O');

		// Get flat representation of headers
		foreach ($this->headers as $name => $value) {
			if(strtolower($name) == 'to' || strtolower($name) == 'cc' || strtolower($name) == 'bcc') continue; // never add the To header
			$headers[] = $name . ': ' . $this->_encodeHeader($value, $this->mail_charset);
		}

		if($this->use_smtp == true) {
			if(!$this->_logged_in || !$this->_smtp_conn) {
				$result = $this->_smtp_login();
				if(!$result) return false;
			}
			$bcc_cc_sent = false;
			foreach($recipients as $recipname => $recip) {
				if($this->_sent_mails >= $this->smtp_max_mails) {
					// close connection to smtp and reconnect
					$this->_sent_mails = 0;
					$this->_smtp_close();
					$result = $this->_smtp_login();
					if(!$result) return false;
				}
				$this->_sent_mails += 1;

				$recipname = trim(str_replace('"', '', $recipname));
				$recip = $this->_encodeHeader($recip, $this->mail_charset);
				$recipname = $this->_encodeHeader($recipname, $this->mail_charset);

				//Email From
				fputs($this->_smtp_conn, 'MAIL FROM: <' . $this->_mail_sender . '>' . $this->_crlf);
				$response = fgets($this->_smtp_conn, 515);

				//Email To
				fputs($this->_smtp_conn, 'RCPT TO: <' . $recip . '>' . $this->_crlf);
				$response = fgets($this->_smtp_conn, 515);

				if($bcc_cc_sent == false) {
					$add_recips = array();
					if($this->getHeader('Cc') != '') $add_recips = array_merge($add_recips, $this->_extract_names($this->getHeader('Cc')));
					if($this->getHeader('Bcc') != '') $add_recips = array_merge($add_recips, $this->_extract_names($this->getHeader('Bcc')));
					foreach($add_recips as $add_recip) {
						if(!$add_recip['mail']) continue;
						fputs($this->_smtp_conn, 'RCPT TO: <' . $this->_encodeHeader($add_recip['mail'], $this->mail_charset) . '>' . $this->_crlf);
						$response = fgets($this->_smtp_conn, 515);
					}
					unset($add_recips);
					$bcc_cc_sent = true;
				}

				//The Email
				fputs($this->_smtp_conn, 'DATA' . $this->_crlf);
				$response = fgets($this->_smtp_conn, 515);

				//Construct Headers
				if($recipname && !is_numeric($recipname)) $this->setHeader('To', $recipname . ' <' . $recip . '>');
				else $this->setHeader('To', $recip);

				$mail_content = 'Subject: ' . $enc_subject . $this->_crlf;
				$mail_content .= 'To: ' . $this->getHeader('To') . $this->_crlf;
				if($this->getHeader('Cc') != '') $mail_content .= 'Cc: ' . $this->_encodeHeader($this->getHeader('Cc'), $this->mail_charset) . $this->_crlf;
				$mail_content .= implode($this->_crlf, $headers) . $this->_crlf . ($this->_is_signed == false ? $this->_crlf : '') . $this->body;

				fputs($this->_smtp_conn, $mail_content . $this->_crlf . '.' . $this->_crlf);
				$response = fgets($this->_smtp_conn, 515);

				// hopefully message was correctly sent now
				$result = true;
			}
		} else {
			if($this->getHeader('Bcc') != '') $headers[] = 'Bcc: ' . $this->_encodeHeader($this->getHeader('Bcc'), $this->mail_charset);
			if($this->getHeader('Cc') != '') $headers[] = 'Cc: ' . $this->_encodeHeader($this->getHeader('Cc'), $this->mail_charset);
			$rec_string = '';
			foreach($recipients as $recipname => $recip) {
				$recipname = trim(str_replace('"', '', $recipname));

				if($rec_string != '') $rec_string .= ', ';
				if($recipname && !is_numeric($recipname)) $rec_string .= $recipname . '<' . $recip . '>';
				else $rec_string .= $recip;
			}
			$to = $this->_encodeHeader($rec_string, $this->mail_charset);
			//$result = mail($to, $subject, $this->body, implode($this->_crlf, $headers));
			$result = mail($to, $enc_subject, $this->body, implode($this->_crlf, $headers));
		}

		// Reset the subject in case mail is resent
		if ($subject !== '') {
			$this->headers['Subject'] = $subject;
		}

		// Return
		return $result;
	}



	/**
	 * Close mail connections
	 *
	 * This closes an open smtp connection so you should always call this function in your script if you have finished sending all emails
	 *
	 * @access public
	 */
	public function finish() {
		if($this->use_smtp == true) $this->_smtp_close();

		$rand = md5(microtime());
		$this->mime_boundary = '==Multipart_Boundary_x' . $rand . 'x';

		$this->headers = array();
		$this->attachments = array();
		$this->text_part = '';
		$this->html_part = '';

		$this->headers['MIME-Version'] = '1.0';
		$this->headers['User-Agent'] = $this->user_agent;

		$this->smtp_helo = '';
		$this->smtp_host = '';
		$this->smtp_port = '';
		$this->smtp_user = '';
		$this->smtp_pass = '';
		$this->use_smtp = false;
		$this->smtp_crypt = false;
		$this->mail_charset = 'UTF-8';
		$this->_sent_mails = 0;

		return;
	}

}

?>
