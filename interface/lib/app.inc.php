<?php
/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

ob_start("ob_gzhandler");

class app {

        var $_language_inc = 0;
        var $_wb;

        function app() {

                global $conf;

                if($conf["start_db"] == true) {
                        $this->load('db_'.$conf["db_type"]);
                        $this->db = new db;
                }

                if($conf["start_session"] == true) {
                        session_start();
                        $_SESSION["s"]['id'] = session_id();
                        if($_SESSION["s"]["theme"] == '') $_SESSION["s"]['theme'] = $conf['theme'];
                        if($_SESSION["s"]["language"] == '') $_SESSION["s"]['language'] = $conf['language'];
                }

        }

        function uses($classes) {
                global $conf;

                $cl = explode(',',$classes);
                if(is_array($cl)) {
                        foreach($cl as $classname) {
                                if(!is_object($this->$classname)) {
                                        include_once($conf['classpath'] . "/".$classname.".inc.php");
                                        $this->$classname = new $classname;
                                }
                        }
                }

        }

        function load($files) {

                global $conf;
                $fl = explode(',',$files);
                if(is_array($fl)) {
                        foreach($fl as $file) {
                                include_once($conf['classpath'] . "/".$file.".inc.php");
                        }
                }

        }

        /*
         0 = DEBUG
         1 = WARNING
         2 = ERROR
        */

        function log($msg, $priority = 0) {

                if($priority >= $conf["log_priority"]) {
                        if (is_writable($conf["log_file"])) {

                            if (!$fp = fopen ($conf["log_file"], "a")) {
                                $this->error("Logfile konnte nicht geöffnet werden.");
                            }
                            if (!fwrite($fp, date("d.m.Y-H:i")." - ". $msg."\r\n")) {
                                $this->error("Schreiben in Logfile nicht möglich.");
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

        function error($msg, $next_link = '', $stop = true, $priority = 1) {
                //$this->uses("error");
                //$this->error->message($msg, $priority);
                if($stop == true){
                  $msg = '<html>
<head>
<title>Error</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../themes/default/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<br><br><br>
<table width="100%" border="0" cellspacing="0" cellpadding="2">
<tr>
<td class="error"><b>Error:</b><br>'.$msg;
                  if($next_link != "") $msg .= '<a href="'.$next_link.'">Next</a><br>';
                  $msg .= '</td>
</tr>
</table>
</body>
</html>';
                  die($msg);
                } else {
                  echo $msg;
                  if($next_link != "") echo "<a href='$next_link'>Next</a>";
                }
        }

        function lng($text)
      {
        global $conf;
        if($this->_language_inc != 1) {
            // loading global and module Wordbook
            @include_once($conf["rootpath"]."/lib/lang/".$_SESSION["s"]["language"].".lng");
            @include_once($conf["rootpath"]."/web/".$_SESSION["s"]["module"]["name"]."/lib/lang/".$_SESSION["s"]["language"].".lng");
            $this->_wb = $wb;
            $this->_language_inc = 1;
        }

        if(!empty($this->_wb[$text])) {
            $text = $this->_wb[$text];
        }

        return $text;
      }

          function tpl_defaults() {
                global $conf;

                $this->tpl->setVar('theme',$_SESSION["s"]["theme"]);
                $this->tpl->setVar('phpsessid',session_id());
                $this->tpl->setVar('html_content_encoding',$conf["html_content_encoding"]);
                if($conf["logo"] != '' && @is_file($conf["logo"])){
                  $this->tpl->setVar('logo', '<img src="'.$conf["logo"].'" border="0" alt="">');
                } else {
                  $this->tpl->setVar('logo', '&nbsp;');
                }
                $this->tpl->setVar('app_title',$conf["app_title"]);
                $this->tpl->setVar('delete_confirmation',$this->lng('delete_confirmation'));

          }

}

/*
 Initialize application (app) object
*/

$app = new app;

?>