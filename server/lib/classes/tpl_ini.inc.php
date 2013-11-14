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
// $Id: vlibIni.php,v 1.1 2003/07/08 12:31:10 platinum Exp $

/*
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; This file contains configuration parametres for use  ;
; with the vLIB library. [ NOW A CLASS!! ]             ;
;                                                      ;
; vLIB uses this file so that for future releases, you ;
; will not have to delve through all the php script    ;
; again to set your specific variable/properties ..etc ;
;                                                      ;
; ---------------------------------------------------- ;
; ATTENTION: Do NOT remove any variable given in the   ;
; configurations below as they will probably still be  ;
; needed by vLIB. If you do not need a variable simply ;
; let it be.                                           ;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
*/

if (!defined('vlibIniClassLoaded')) {
	define('vlibIniClassLoaded', 1);

	/**
	 * vlibIni is a class used to store configuration parameters
	 * for the vLIB library.
	 *
	 * @since 21/07/2002
	 * @author Kelvin Jones <kelvin@kelvinjones.co.uk>
	 * @package vLIB
	 * @access private
	 */


	class vlibIni {

		/** config vars for vlibTemplate */
		public static function vlibTemplate () {

			global $conf;

			$tpl_dir = $conf["rootpath"]."/conf";
			$include_paths = array($conf["rootpath"].'/conf-custom');

			return array(

				'TEMPLATE_DIR' => $tpl_dir,   // Default directory for your template files (full path)
				// leave the '/' or '\' off the end of the directory.
				'INCLUDE_PATHS' => $include_paths, // The include path is always searched first for a template file before the template_dir is checked

				'MAX_INCLUDES' => 10,                      // Drill depth for tmpl_include's

				'GLOBAL_VARS' => 1,                        // if set to 1, any variables not found in a
				// loop will search for a global var as well

				'GLOBAL_CONTEXT_VARS' => 1,                // if set to 1, vlibTemplate will add global vars
				// reflecting the environment.

				'LOOP_CONTEXT_VARS' => 1,                  // if set to 1, vlibTemplate will add loop specific vars
				// on each row of the loop.

				'SET_LOOP_VAR' => 1,                       // Sets a global variable for each top level loops

				'DEFAULT_ESCAPE' => 'none',                // 1 of the following: html, url, sq, dq, none

				'STRICT' => 0,                             // Dies when encountering an incorrect tmpl_*
				// style tags i.e. tmpl_vae

				'CASELESS' => 0,                           // Removes case sensitivity on all variables

				'UNKNOWNS' => 'ignore',                    // How to handle unknown variables.
				// 1 of the following: ignore, remove, leave,print, comment
				// 1 of the following: ignore, remove, leave, print, comment

				'TIME_PARSE' => '0',                       // Will enable you to time how long vlibTemplate takes to parse
				// your template. You then use the function: getParseTime().

				'ENABLE_PHPINCLUDE' => '1',                // Will allow template to include a php file using <TMPL_PHPINCLUDE>


				/* the following are only used by the vlibTemplateCache class. */

				'CACHE_DIRECTORY' => $conf["template"]["cache_dir"],
				// Directory where the cached filesystem
				// will be set up (full path, and must be writable)
				// '/' or '\' off the end of the directory.

				'CACHE_LIFETIME' => 604800,                // Duration until file is re-cached in seconds (604800 = 1 week)

				'CACHE_EXTENSION' => 'vtc'                  // extention to be used by the cached file i.e. index.php will become
				// index.vtc (vlibTemplate Compiled)
			);

		} // << end function vlibTemplate



		/** config vars for vlibDate */
		public function vlibDate () {

			return array(
				'DEFAULT_LANG' => 'de'                     // default language for the date displays
			);

		}// << end function vlibDate


	}// << end class vlibIni
}
?>
