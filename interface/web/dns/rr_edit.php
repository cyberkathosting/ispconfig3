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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/rr.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

// Checking module permissions
if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
        header("Location: ../index.php");
        exit;
}

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

        function onSubmit() {
                global $app, $conf;

                $this->dataRecord["zone"] = $_SESSION['s']['list']['rr']['parent_id'];

                $app->uses('validate_dns');
                $app->tform->errorMessage .= $app->validate_dns->validate_rr($this->dataRecord);

                // update serial
                $soa = $app->db->queryOneRecord("SELECT * FROM soa WHERE id = ".$this->dataRecord["zone"]);
                $serial = $soa['serial'];
                $update = 0;
                if($old_record = $app->db->queryOneRecord("SELECT * FROM rr WHERE id = ".$this->dataRecord["id"])){
                  foreach($old_record as $key => $val){
                    if($this->dataRecord[$key] != $val) $update += 1;
                  }
                } else { // new record
                  $update = 1;
                }
                if($update > 0){
                  $serial_date = substr($serial, 0, 8);
                  $count = intval(substr($serial, 8, 2));
                  $current_date = date("Ymd");
                  if($serial_date == $current_date){
                    $count += 1;
                    $count = str_pad($count, 2, "0", STR_PAD_LEFT);
                    $new_serial = $current_date.$count;
                  } else {
                    $new_serial = $current_date.'01';
                  }
                  $app->db->query("UPDATE soa SET serial = '".$new_serial."' WHERE id = ".$this->dataRecord["zone"]);
                }

                // PTR
                if($conf['auto_create_ptr'] == 1 && trim($conf['default_ns']) != '' && trim($conf['default_mbox']) != ''){
                  if($this->dataRecord['type'] == 'A' || $this->dataRecord['type'] == 'AAAA'){
                    list($a, $b, $c, $d) = explode('.', $this->dataRecord['data']);
                    $ptr_soa = $c.'.'.$b.'.'.$a.'.in-addr.arpa.';
                    if(substr($this->dataRecord['name'], -1) == '.'){
                      $ptr_soa_rr_data = $this->dataRecord['name'];
                    } else {
                      $ptr_soa_rr_data = $this->dataRecord['name'].(trim($this->dataRecord['name']) == '' ? '' : '.').$soa['origin'];
                    }
                    if(!$ptr_soa_exist = $app->db->queryOneRecord("SELECT * FROM soa WHERE origin = '".$ptr_soa."'")){
                      $app->db->query("INSERT INTO soa (origin, ns, mbox, serial, refresh, retry, expire, minimum, ttl, active) VALUES ('".$ptr_soa."', '".trim($conf['default_ns'])."', '".trim($conf['default_mbox'])."', '".date("Ymd").'01'."', '".$conf['default_refresh']."', '".$conf['default_retry']."', '".$conf['default_expire']."', '".$conf['default_minimum_ttl']."', '".$conf['default_ttl']."', 'Y')");
                      $ptr_soa_id = $app->db->insertID();
                      $app->db->query("INSERT INTO rr (zone, name, type, data, aux, ttl) VALUES ('".$ptr_soa_id."', '".$d."', 'PTR', '".$ptr_soa_rr_data."', '0', '".$conf['default_ttl']."')");
                    } else {
                      if($ptr_soa_exist['active'] != 'Y') $app->db->query("UPDATE soa SET active = 'Y' WHERE id = ".$ptr_soa_exist['id']);
                      if(!$ptr_soa_rr_exist = $app->db->queryOneRecord("SELECT * FROM rr WHERE zone = '".$ptr_soa_exist['id']."' AND name = '".$d."' AND type = 'PTR'")){
                        $app->db->query("INSERT INTO rr (zone, name, type, data, aux, ttl) VALUES ('".$ptr_soa_exist['id']."', '".$d."', 'PTR', '".$ptr_soa_rr_data."', '0', '".$conf['default_ttl']."')");
                        // increase serial of PTR SOA
                        $ptr_soa_serial_date = substr($ptr_soa_exist['serial'], 0, 8);
                        $ptr_soa_count = intval(substr($ptr_soa_exist['serial'], 8, 2));
                        $ptr_soa_current_date = date("Ymd");
                        if($ptr_soa_serial_date == $ptr_soa_current_date){
                          $ptr_soa_count += 1;
                          $ptr_soa_count = str_pad($ptr_soa_count, 2, "0", STR_PAD_LEFT);
                          $ptr_soa_new_serial = $ptr_soa_current_date.$ptr_soa_count;
                        } else {
                          $ptr_soa_new_serial = $ptr_soa_current_date.'01';
                        }
                        $app->db->query("UPDATE soa SET serial = '".$ptr_soa_new_serial."' WHERE id = ".$ptr_soa_exist['id']);
                      }
                    }
                  }
                }


                parent::onSubmit();
        }

}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();

?>