<?php

/*
Copyright (c) 2005, Till Brehm, Falko Timme, projektfarm Gmbh
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

$list_def_file = "list/soa.list.php";
$tform_def_file = "form/soa.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

// Checke Berechtigungen für Modul
if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
        header("Location: ../index.php");
        exit;
}

// Loading classes
$app->load('tform_actions');

class page_action extends tform_actions {

        function onDelete() {
                global $app, $conf;

                $app->uses('tform');
                if(!$soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ".$_REQUEST['id']." AND ".$app->tform->getAuthSQL('d'))) $app->error($app->tform->wordbook['error_no_permission']);

                // PTR
                if($conf['auto_create_ptr'] == 1 && trim($conf['default_ns']) != '' && trim($conf['default_mbox']) != ''){
                  //$soa = $app->db->queryOneRecord("SELECT * FROM soa WHERE id = ".$_REQUEST['id']);
                  $rrs = $app->db->queryAllRecords("SELECT * FROM dns_rr WHERE zone = '".$_REQUEST['id']."' AND (type = 'A' OR type = 'AAAA')");
                  if(!empty($rrs)){
                    foreach($rrs as $rr){
                      list($a, $b, $c, $d) = explode('.', $rr['data']);
                      $ptr_soa = $c.'.'.$b.'.'.$a.'.in-addr.arpa.';
                      if(substr($rr['name'], -1) == '.'){
                        $ptr_soa_rr_data = $rr['name'];
                      } else {
                        $ptr_soa_rr_data = $rr['name'].(trim($rr['name']) == '' ? '' : '.').$soa['origin'];
                      }
                      if($ptr_soa_exist = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE origin = '".$ptr_soa."'")){
                        if($ptr_soa_rr_exist = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = '".$ptr_soa_exist['id']."' AND name = '".$d."' AND type = 'PTR' AND data = '".$ptr_soa_rr_data."'")){
                          $app->db->query("DELETE FROM dns_rr WHERE id = ".$ptr_soa_rr_exist['id']);
                          // is there another A/AAAA record with that IP address?
                          if($other_rr = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE (type = 'A' OR type = 'AAAA') AND data = '".$rr['data']."' AND id != ".$rr['id']." AND zone != ".$rr['zone'])){
                            $other_soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ".$other_rr['zone']);
                            if(substr($other_rr['name'], -1) == '.'){
                              $other_ptr_soa_rr_data = $other_rr['name'];
                            } else {
                              $other_ptr_soa_rr_data = $other_rr['name'].(trim($other_rr['name']) == '' ? '' : '.').$other_soa['origin'];
                            }
                            $app->db->query("INSERT INTO dns_rr (zone, name, type, data, aux, ttl, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other) VALUES ('".$ptr_soa_exist['id']."', '".$d."', 'PTR', '".$other_ptr_soa_rr_data."', '0', '".$conf['default_ttl']."', '".$_SESSION['s']['user']['sys_userid']."', '".$_SESSION['s']['user']['sys_groupid']."', '".$_SESSION['s']['user']['sys_perm_user']."', '".$_SESSION['s']['user']['sys_perm_group']."', '".$_SESSION['s']['user']['sys_perm_other']."')");
                          }

                          // if no more records exist for the ptr_soa, delete it
                          if(!$ptr_soa_rr = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = '".$ptr_soa_exist['id']."'")){
                            $app->db->query("DELETE FROM dns_soa WHERE id = ".$ptr_soa_exist['id']);
                          } else { // increment serial
                            $serial_date = substr($ptr_soa_exist['serial'], 0, 8);
                            $count = intval(substr($ptr_soa_exist['serial'], 8, 2));
                            $current_date = date("Ymd");
                            if($serial_date == $current_date){
                              $count += 1;
                              $count = str_pad($count, 2, "0", STR_PAD_LEFT);
                              $new_serial = $current_date.$count;
                            } else {
                              $new_serial = $current_date.'01';
                            }
                            $app->db->query("UPDATE dns_soa SET serial = '".$new_serial."' WHERE id = ".$ptr_soa_exist['id']);
                          }
                        }
                      }
                    }
                  }
                }

                // delete associated records
                $app->db->query("DELETE FROM dns_rr WHERE zone = ".$_REQUEST['id']);

                parent::onDelete();
        }

}

$app->tform_actions = new page_action;
$app->tform_actions->onDelete();

?>