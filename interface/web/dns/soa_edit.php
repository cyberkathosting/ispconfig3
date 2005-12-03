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

                        $app->db->query("UPDATE rr SET name = '".substr($rr['name'], 0, -(strlen($this->dataRecord['origin']))).$this->dataRecord['origin']."' WHERE name LIKE '%".$soa['origin']."' AND type != 'PTR'");
                        $app->db->query("UPDATE rr SET data = '".substr($rr['data'], 0, -(strlen($this->dataRecord['origin']))).$this->dataRecord['origin']."' WHERE data LIKE '%".$soa['origin']."' AND type != 'PTR'");

                        if($conf['auto_create_ptr'] == 1 && trim($conf['default_ns']) != '' && trim($conf['default_mbox']) != ''){
                          $app->db->query("UPDATE rr SET name = '".substr($rr['name'], 0, -(strlen($this->dataRecord['origin']))).$this->dataRecord['origin']."' WHERE name LIKE '%".$soa['origin']."' AND type = 'PTR'");
                          $app->db->query("UPDATE rr SET data = '".substr($rr['data'], 0, -(strlen($this->dataRecord['origin']))).$this->dataRecord['origin']."' WHERE data LIKE '%".$soa['origin']."' AND type = 'PTR'");
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