<?php
/*
Copyright (c) 2006, Till Brehm, projektfarm Gmbh
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

class app {

        function app() {

                global $conf;

                if($conf["start_db"] == true) {
                	$this->load('db_'.$conf["db_type"]);
                	$this->db = new db;
                }

        }

        function uses($classes) {
			global $conf;

			$cl = explode(',',$classes);
			if(is_array($cl)) {
				foreach($cl as $classname) {
					if(!is_object($this->$classname)) {
						if(is_file($conf['classpath'] . "/".$classname.".inc.php") && !is_link($conf['classpath'] . "/".$classname.".inc.php")) {
							include_once($conf['classpath'] . "/".$classname.".inc.php");
							$this->$classname = new $classname;
						}
					}
				}
			}
        }

        function load($files) {

                global $conf;
                $fl = explode(',',$files);
                if(is_array($fl)) {
                        foreach($fl as $file) {
							if(is_file($conf['classpath'] . "/".$classname.".inc.php") && !is_link($conf['classpath'] . "/".$classname.".inc.php")) {
                                include_once($conf['classpath'] . "/".$file.".inc.php");
							}
                        }
                }

        }

        /*
         0 = DEBUG
         1 = WARNING
         2 = ERROR
        */

        function log($msg, $priority = 0) {
				
				global $conf;
				
                if($priority >= $conf["log_priority"]) {
                        if (is_writable($conf["log_file"])) {

                            if (!$fp = fopen ($conf["log_file"], "a")) {
                                die("Unable to open Logfile.");
                            }
							switch ($priority) {
								case: 0;
									$priority_txt = "DEBUG";
								break;
								case: 1;
									$priority_txt = "WARNING";
								break;
								case: 2;
									$priority_txt = "ERROR";
								break;
							}
							
                            if (!fwrite($fp, date("d.m.Y-H:i")." - ".$priority_txt." - ". $msg."\r\n")) {
                                die("Unable to write to logfile.");
                            }
                            fclose($fp);

                        } else {
                            $this->error("Logfile ist nicht beschreibbar.");
                        }
                } // if
        } // func

        /*
         0 = DEBUG
         1 = WARNING
         2 = ERROR
        */

        function error($msg) {
        	$this->log($msg,3);
			die();
        }

}

/*
 Initialize application (app) object
*/

$app = new app;

?>