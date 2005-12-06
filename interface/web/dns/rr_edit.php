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

                if($this->dataRecord['id'] > 0){
                  if(!$app->tform->checkPerm($this->dataRecord['id'],'u')) $app->error($app->tform->wordbook['error_no_permission']);
                } else {
                  if(!$app->tform->checkPerm($this->dataRecord['id'],'i')) $app->error($app->tform->wordbook['error_no_permission']);
                }

                $this->dataRecord["zone"] = $_SESSION['s']['list']['rr']['parent_id'];

                $app->uses('validate_dns');
                $app->tform->errorMessage .= $app->validate_dns->validate_rr($this->dataRecord);

                $increased_serials[] = -1;
                if($app->tform->errorMessage == ''){
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
                    $new_serial = $app->validate_dns->increase_serial($serial);
                    $increased_serials[] = $soa['id'];
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
                          if(!in_array($ptr_soa_exist['id'], $increased_serials)){
                            $ptr_soa_new_serial = $app->validate_dns->increase_serial($ptr_soa_exist['serial']);
                            $increased_serials[] = $ptr_soa_exist['id'];
                            $app->db->query("UPDATE soa SET serial = '".$ptr_soa_new_serial."' WHERE id = ".$ptr_soa_exist['id']);
                          }
                        }
                      }

                      // if IP address changes, delete/change old PTR record
                      if(!empty($old_record)){
                        list($oa, $ob, $oc, $od) = explode('.', $old_record['data']);
                        if($a_rr_with_same_ip = $app->db->queryOneRecord("SELECT rr.*, soa.origin FROM rr, soa WHERE rr.type = 'A' AND rr.data = '".$old_record['data']."' AND rr.zone = soa.id AND soa.active = 'Y' AND rr.id != ".$this->dataRecord["id"])){
                          if(substr($a_rr_with_same_ip['name'], -1) == '.'){
                            $new_ptr_soa_rr_data = $a_rr_with_same_ip['name'];
                          } else {
                            $new_ptr_soa_rr_data = $a_rr_with_same_ip['name'].(trim($a_rr_with_same_ip['name']) == '' ? '' : '.').$a_rr_with_same_ip['origin'];
                          }
                          $app->db->query("UPDATE rr SET data = '".$new_ptr_soa_rr_data."' WHERE zone = '".$ptr_soa_exist['id']."' AND name = '".$od."' AND type = 'PTR'");
                        } else {
                          $old_ptr_soa = $oc.'.'.$ob.'.'.$oa.'.in-addr.arpa.';
                          $old_ptr_soa_exist = $app->db->queryOneRecord("SELECT * FROM soa WHERE origin = '".$old_ptr_soa."'");
                          $app->db->query("DELETE FROM rr WHERE zone = '".$old_ptr_soa_exist['id']."' AND name = '".$od."' AND type = 'PTR'");
                          //die("DELETE FROM rr WHERE zone = '".$old_record['zone']."' AND name = '".$od."' AND type = 'PTR'");
                          if(!$app->db->queryOneRecord("SELECT * FROM rr WHERE zone = '".$old_ptr_soa_exist['id']."'")){
                            $app->db->query("DELETE FROM soa WHERE id = ".$old_ptr_soa_exist['id']);
                          } else {
                            // increase serial
                            if(!in_array($old_ptr_soa_exist['id'], $increased_serials)){
                              $new_serial = $app->validate_dns->increase_serial($old_ptr_soa_exist['serial']);
                              $app->db->query("UPDATE soa SET serial = '".$new_serial."' WHERE id = ".$old_ptr_soa_exist['id']);
                            }
                          }
                        }
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