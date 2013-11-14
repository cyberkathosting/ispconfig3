<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Active Fish Group                                 |
// +----------------------------------------------------------------------+
// | Authors: Kelvin Jones <kelvin@kelvinjones.co.uk>                     |
// +----------------------------------------------------------------------+
//
// $Id: error.php,v 1.1 2003/07/08 12:32:06 platinum Exp $

define('FATAL',   E_USER_ERROR);
define('WARNING', E_USER_WARNING);
define('NOTICE',  E_USER_NOTICE);
define('KILL',    -1); // used for killing inside parsing.

/**
 * Class is used by vlibTemplate.
 * It handles all of the error reporting for vlibTemplate.
 *
 * @author Kelvin Jones <kelvin@kelvinjones.co.uk>
 * @since 06/03/2002
 * @package vLIB
 * @access private
 */


class vlibTemplateError {

	/*-----------------------------------------------------------------------------\
|     DO NOT TOUCH ANYTHING IN THIS CLASS IT MAY NOT WORK OTHERWISE            |
\-----------------------------------------------------------------------------*/

	public static function raiseError ($code, $level = null, $extra=null) {
		if (!($level & error_reporting())&& $level != KILL) return; // binary AND checks for reporting level

		$error_codes = array(
			'VT_ERROR_NOFILE'               => 'vlibTemplate Error: Template ('.$extra.') file not found.',
			'VT_ERROR_PARSE'                => 'vlibTemplate Error: Parse error!<br />To debug this file, use vlibTemplateDebug instead of vlibTemplate in the class instantiation(i.e. new vlibTemplateDebug).',
			'VT_NOTICE_INVALID_TAG'         => 'vlibTemplate Notice: Invalid tag ('.$extra.').',
			'VT_ERROR_INVALID_TAG'          => 'vlibTemplate Error: Invalid tag ('.$extra.'). To disable this you must turn of the STRICT option.',
			'VT_NOTICE_INVALID_ATT'         => 'vlibTemplate Notice: Invalid attribute ('.$extra.').',
			'VT_WARNING_INVALID_ARR'        => 'vlibTemplate Warning: Invalid loop structure passed to vlibTemplate::setLoop() (loop name: '.$extra.').',
			'VT_ERROR_INVALID_ERROR_CODE'   => 'vlibTemplate Error: Invalid error raised.',
			'VT_ERROR_WRONG_NO_PARAMS'      => 'vlibTemplate Warning: Wrond parameter count passed to '.$extra.'.',
			'VT_ERROR_UNKNOWN_VAR'          => 'vlibTemplate Error: template var not found.',
			'VT_ERROR_NO_CACHE_WRITE'       => 'vlibTemplate Error: unable to write to cache file ('.$extra.').',
			'VT_ERROR_WRONG_CACHE_TYPE'     => 'vlibTemplate Error: non-directory file found in cache root with same name as directory ('.$extra.').',
			'VT_ERROR_CACHE_MKDIR_FAILURE'  => 'vlibTemplate Error: failed to create directory in cache root ('.$extra.').',
			'VT_WARNING_NOT_CACHE_OBJ'      => 'vlibTemplate Warning: called a vlibTemplateCache function ('.$extra.') without instantiating the vlibTemplateCache class.',
			'VT_WARNING_LOOP_NOT_SET'       => 'vlibTemplate Warning: called vlibTemplate::addRow() or vlibTemplate::addLoop() with an invalid loop name.',
			'VT_WARNING_INVALID_RESOURCE'   => 'vlibTemplate Warning: Invalid resource type passed to vlibTemplate::setDbLoop() for Db "'.$extra.'".',
			'VT_WARNING_INVALID_LOOP_DB'    => 'vlibTemplate Warning: Invalid Db type passed to vlibTemplate::setDbLoop(), "'.$extra.'" not currently available.',
			'VT_WARNING_INVALID_IF_OP'      => 'vlibTemplate Warning: The Operator "'.$extra.'" is not supported by vlibTemplate.'
		);

		$error_levels = array(
			'VT_ERROR_NOFILE'               => FATAL,
			'VT_ERROR_PARSE'                => FATAL,
			'VT_NOTICE_INVALID_TAG'         => NOTICE,
			'VT_ERROR_INVALID_TAG'          => FATAL,
			'VT_NOTICE_INVALID_ATT'         => NOTICE,
			'VT_WARNING_INVALID_ARR'        => WARNING,
			'VT_ERROR_INVALID_ERROR_CODE'   => FATAL,
			'VT_ERROR_WRONG_NO_PARAMS'      => WARNING,
			'VT_ERROR_UNKNOWN_VAR'          => WARNING,
			'VT_ERROR_NO_CACHE_WRITE'       => KILL,
			'VT_ERROR_WRONG_CACHE_TYPE'     => KILL,
			'VT_ERROR_CACHE_MKDIR_FAILURE'  => KILL,
			'VT_WARNING_NOT_CACHE_OBJ'      => WARNING,
			'VT_WARNING_LOOP_NOT_SET'       => WARNING,
			'VT_WARNING_INVALID_RESOURCE'   => WARNING,
			'VT_WARNING_INVALID_LOOP_DB'    => WARNING,
			'VT_WARNING_INVALID_IF_OP'      => WARNING
		);

		($level === null) and $level = $error_levels[$code];
		if ($level == KILL) {
			die ($error_codes[$code]);
		}

		if ($msg = $error_codes[$code]) {
			trigger_error($msg, $level);
		} else {
			$level = $error_levels['VT_ERROR_INVALID_ERROR_CODE'];
			$msg = $error_codes['VT_ERROR_INVALID_ERROR_CODE'];
			trigger_error($msg, $level);
		}
		return;
	}

}

?>
