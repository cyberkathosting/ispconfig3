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

$tform_def_file = "form/soa.tform.php";

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

                $app->uses('validate_dns');
                $app->tform->errorMessage .= $app->validate_dns->validate_soa($this->dataRecord);

                $increased_serials[] = -1;
                // update serial
                $soa = $app->db->queryOneRecord("SELECT * FROM soa WHERE id = ".$this->dataRecord["id"]);
                $serial = $soa['serial'];
                $update = 0;
                if($soa){
                  foreach($soa as $key => $val){
                    if($this->dataRecord[$key] != $val && $key != 'active') $update += 1;
                  }
                } else { // new record
                  $update = 1;
                }
                if(strlen($this->dataRecord["serial"]) == 10 && intval($this->dataRecord["serial"]) == $this->dataRecord["serial"] && $this->dataRecord["serial"] != $serial){
                  $update = 0;
                  $increased_serials[] = $soa['id'];
                }
                if($update > 0){
                  $new_serial = $app->validate_dns->increase_serial($serial);
                  $increased_serials[] = $soa['id'];
                  $this->dataRecord["serial"] = $new_serial;
                }

                if($soa){
                  // update rr if origin has changed
                  if($soa['origin'] != $this->dataRecord['origin']){

                    if($rrs = $app->db->queryAllRecords("SELECT * FROM rr")){
                      $update_soas = array();
                      foreach($rrs as $rr){
                        if($soa['origin'] == substr($rr['name'], -(strlen($soa['origin']))) || $soa['origin'] == substr($rr['data'], -(strlen($soa['origin'])))) $update_soas[] = $rr['zone'];
                        //$update_soas[] = $app->db->queryAllRecords("SELECT DISTINCT zone FROM rr WHERE name LIKE '%".$soa['origin']."' OR data LIKE '%".$soa['origin']."'");

                        $app->db->query("UPDATE rr SET name = '".substr($rr['name'], 0, -(strlen($this->dataRecord['origin']))).$this->dataRecord['origin']."' WHERE name LIKE '%".$soa['origin']."' AND type != 'PTR' AND id = ".$rr['id']);

                        $app->db->query("UPDATE rr SET data = '".substr($rr['data'], 0, -(strlen($this->dataRecord['origin']))).$this->dataRecord['origin']."' WHERE data LIKE '%".$soa['origin']."' AND type != 'PTR' AND id = ".$rr['id']);

                        if($conf['auto_create_ptr'] == 1 && trim($conf['default_ns']) != '' && trim($conf['default_mbox']) != ''){
                          $app->db->query("UPDATE rr SET name = '".substr($rr['name'], 0, -(strlen($this->dataRecord['origin']))).$this->dataRecord['origin']."' WHERE name LIKE '%".$soa['origin']."' AND type = 'PTR' AND id = ".$rr['id']);

                          $app->db->query("UPDATE rr SET data = '".substr($rr['data'], 0, -(strlen($this->dataRecord['origin']))).$this->dataRecord['origin']."' WHERE data LIKE '%".$soa['origin']."' AND type = 'PTR' AND id = ".$rr['id']);

                        }
                      }

                      // increase serial
                      if(!empty($update_soas)){
                        $update_soas = array_unique($update_soas);
                        foreach($update_soas as $update_soa){
                          $u_soa = $app->db->queryOneRecord("SELECT * FROM soa WHERE id = ".$update_soa);
                          if(!in_array($u_soa['id'], $increased_serials)){
                            $new_serial = $app->validate_dns->increase_serial($u_soa['serial']);
                            if($conf['auto_create_ptr'] == 1 && trim($conf['default_ns']) != '' && trim($conf['default_mbox']) != ''){
                              $app->db->query("UPDATE soa SET serial = '".$new_serial."' WHERE id = ".$update_soa);
                            } else {
                              $app->db->query("UPDATE soa SET serial = '".$new_serial."' WHERE id = ".$update_soa." AND origin NOT LIKE '%.in-addr.arpa.'");
                            }
                            $increased_serials[] = $u_soa['id'];
                          }
                        }
                      }
                    }
                  }


                  // PTR
                  if($conf['auto_create_ptr'] == 1 && trim($conf['default_ns']) != '' && trim($conf['default_mbox']) != ''){

                    if($soa['active'] = 'Y' && $this->dataRecord['active'][0] == 'N'){

                      if($soa_rrs = $app->db->queryAllRecords("SELECT * FROM rr WHERE zone = ".$this->dataRecord['id']." AND type = 'A'")){
                        foreach($soa_rrs as $soa_rr){
                          if(substr($soa_rr['name'], -1) == '.'){
                            $fqdn = $soa_rr['name'];
                          } else {
                            $fqdn = $soa_rr['name'].(trim($soa_rr['name']) == '' ? '' : '.').$this->dataRecord['origin'];
                          }
                          list($a, $b, $c, $d) = explode('.', $soa_rr['data']);
                          $ptr_soa = $c.'.'.$b.'.'.$a.'.in-addr.arpa.';
                          if($ptr = $app->db->queryOneRecord("SELECT soa.id, soa.serial FROM soa, rr WHERE rr.type = 'PTR' AND rr.data = '".$fqdn."' AND rr.zone = soa.id AND soa.origin = '".$ptr_soa."'")){
                            ############
                            if($a_rr_with_same_ip = $app->db->queryOneRecord("SELECT rr.*, soa.origin FROM rr, soa WHERE rr.type = 'A' AND rr.data = '".$soa_rr['data']."' AND rr.zone = soa.id AND soa.active = 'Y' AND rr.id != ".$soa_rr["id"]." AND rr.zone != '".$this->dataRecord['zone']."'")){
                              if(substr($a_rr_with_same_ip['name'], -1) == '.'){
                                $new_ptr_soa_rr_data = $a_rr_with_same_ip['name'];
                              } else {
                                $new_ptr_soa_rr_data = $a_rr_with_same_ip['name'].(trim($a_rr_with_same_ip['name']) == '' ? '' : '.').$a_rr_with_same_ip['origin'];
                              }
                              $app->db->query("UPDATE rr SET data = '".$new_ptr_soa_rr_data."' WHERE zone = '".$ptr['id']."' AND name = '".$d."' AND type = 'PTR'");
                            } else {
                              $app->db->query("DELETE FROM rr WHERE zone = '".$ptr['id']."' AND name = '".$d."' AND type = 'PTR'");

                              if(!$app->db->queryOneRecord("SELECT * FROM rr WHERE zone = '".$ptr['id']."'")){
                                $app->db->query("DELETE FROM soa WHERE id = ".$ptr['id']);
                              } else {
                                // increase serial
                                if(!in_array($ptr['id'], $increased_serials)){
                                  $new_serial = $app->validate_dns->increase_serial($ptr['serial']);
                                  $app->db->query("UPDATE soa SET serial = '".$new_serial."' WHERE id = ".$ptr['id']);
                                  $increased_serials[] = $ptr['id'];
                                }
                              }
                            }
                            ############
                          }
                        }
                      }

                     /* */


                    }

                    if($soa['active'] = 'N' && $this->dataRecord['active'][0] == 'Y'){

                      if($soa_rrs = $app->db->queryAllRecords("SELECT * FROM rr WHERE zone = ".$this->dataRecord['id']." AND type = 'A'")){
                        foreach($soa_rrs as $soa_rr){
                          #################
                          list($a, $b, $c, $d) = explode('.', $soa_rr['data']);
                          $ptr_soa = $c.'.'.$b.'.'.$a.'.in-addr.arpa.';
                          if(substr($soa_rr['name'], -1) == '.'){
                            $ptr_soa_rr_data = $soa_rr['name'];
                          } else {
                            $ptr_soa_rr_data = $soa_rr['name'].(trim($soa_rr['name']) == '' ? '' : '.').$this->dataRecord['origin'];
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
                          ################
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