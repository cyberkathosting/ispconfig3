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

/**
* Action framework for the tform library.
*
* @author Till Brehm <t.brehm@scrigo.org>
* @copyright Copyright &copy; 2005, Till Brehm
*/

class tform_actions {

        var $id;
        var $activeTab;
        var $dataRecord;
        var $plugins = array();

        function onLoad() {
                global $app, $conf, $tform_def_file;

                // Loading template classes and initialize template
                if(!is_object($app->tpl)) $app->uses('tpl');
                if(!is_object($app->tform)) $app->uses('tform');

                $app->tpl->newTemplate("tabbed_form.tpl.htm");

                // Load table definition from file
                $app->tform->loadFormDef($tform_def_file);

                // loading plugins
                $next_tab = $app->tform->getNextTab();
                if(is_array($app->tform->formDef["tabs"][$next_tab]["plugins"])) {
                        $app->load('plugin_base');
                        foreach($app->tform->formDef["tabs"][$next_tab]["plugins"] as $plugin_name => $plugin_settings) {
                                $plugin_class = $plugin_settings["class"];
                                $app->load($plugin_class);
                                $this->plugins[$plugin_name] = new $plugin_class;
                                $this->plugins[$plugin_name]->setOptions($plugin_name,$plugin_settings['options']);
                                $this->plugins[$plugin_name]->onLoad();
                        }
                }

                // Importing ID
                $this->id = intval($_REQUEST["id"]);

                if(count($_POST) > 1) {
                        $this->dataRecord = $_POST;
                        $this->onSubmit();
                } else {
                        $this->onShow();
                }
        }

        /**
        * Function called on page submit
        */

        function onSubmit() {
                global $app, $conf;

                // Calling the action functions
                if($this->id > 0) {
                        $this->onUpdate();
                } else {
                        $this->onInsert();
                }
        }

        /**
        * Function called on data update
        */

        function onUpdate() {
                global $app, $conf;

                $ext_where = '';
                $sql = $app->tform->getSQL($this->dataRecord,$app->tform->getCurrentTab(),'UPDATE',$this->id,$ext_where);
                if($app->tform->errorMessage == '') {

                        if(!empty($sql)) {
                                $app->db->query($sql);
                                if($app->db->errorMessage != '') die($app->db->errorMessage);
                        }

                        // Call plugin
                        foreach($this->plugins as $plugin) {
                                $plugin->onInsert();
                        }

                                                $this->onAfterUpdate();

                        if($_REQUEST["next_tab"] == '') {
                                $list_name = $_SESSION["s"]["form"]["return_to"];
                                                                if($list_name != '' && $_SESSION["s"]["list"][$list_name]["parent_id"] != $this->id && $_SESSION["s"]["list"][$list_name]["parent_name"] != $app->tform->formDef["name"]) {
                                        $redirect = "Location: ".$_SESSION["s"]["list"][$list_name]["parent_script"]."?id=".$_SESSION["s"]["list"][$list_name]["parent_id"]."&next_tab=".$_SESSION["s"]["list"][$list_name]["parent_tab"];
                                        $_SESSION["s"]["form"]["return_to"] = '';
                                        session_write_close();
                                        header($redirect);
                                } else {
                                    header("Location: ".$app->tform->formDef['list_default']);
                                }
                        exit;
                    } else {
                                $this->onShow();
                        }
                } else {
                        $this->onError();
                }
        }

        /**
        * Function called on data insert
        */

        function onInsert() {
                global $app, $conf;

                $ext_where = '';
                $sql = $app->tform->getSQL($this->dataRecord,$app->tform->getCurrentTab(),'INSERT',$this->id,$ext_where);
                if($app->tform->errorMessage == '') {
                        $app->db->query($sql);
                        if($app->db->errorMessage != '') die($app->db->errorMessage);
                        $this->id = $app->db->insertID();

                        // Call plugin
                        foreach($this->plugins as $plugin) {
                                $plugin->onInsert();
                        }

                                                $this->onAfterInsert();

                        if($_REQUEST["next_tab"] == '') {
                            $list_name = $_SESSION["s"]["form"]["return_to"];
                                                                if($list_name != '' && $_SESSION["s"]["list"][$list_name]["parent_id"] != $this->id && $_SESSION["s"]["list"][$list_name]["parent_name"] != $app->tform->formDef["name"]) {
                                        $redirect = "Location: ".$_SESSION["s"]["list"][$list_name]["parent_script"]."?id=".$_SESSION["s"]["list"][$list_name]["parent_id"]."&next_tab=".$_SESSION["s"]["list"][$list_name]["parent_tab"];
                                        $_SESSION["s"]["form"]["return_to"] = '';
                                        session_write_close();
                                        header($redirect);
                                } else {
                                    header("Location: ".$app->tform->formDef['list_default']);
                                }
                        exit;
                    } else {
                                $this->onShow();
                        }
                } else {
                        $this->onError();
                }
        }

                function onAfterUpdate() {
                        global $app, $conf;
                }

                function onAfterInsert() {
                        global $app, $conf;
                }


        /**
        * Function called on data insert or update error
        */

        function onError() {
                global $app, $conf;

                $app->tpl->setVar("error","<b>".$app->lng('Error').":</b><br>".$app->tform->errorMessage);
                $app->tpl->setVar($this->dataRecord);
                $this->onShow();
        }

        /**
        * Function called on data delete
        */

        function onDelete() {
                global $app, $conf,$list_def_file,$tform_def_file;

                include_once($list_def_file);

                // Loading tform framework
                if(!is_object($app->tform)) $app->uses('tform');

                // Load table definition from file
                $app->tform->loadFormDef($tform_def_file);

                // importing ID
                $this->id = intval($_REQUEST["id"]);

                if($this->id > 0) {

                        // checking permissions
                        if($app->tform->formDef['auth'] == 'yes') {
                                if($app->tform->checkPerm($this->id,'d') == false) $app->error($app->lng('error_no_delete_permission'));
                        }

                        $record_old = $app->db->queryOneRecord("SELECT * FROM ".$liste["table"]." WHERE ".$liste["table_idx"]." = ".$this->id);

                        // Saving record to datalog when db_history enabled
                        if($form["db_history"] == 'yes') {
                                $diffrec = array();

                                foreach($record_old as $key => $val) {
                                        // Record has changed
                                        $diffrec[$key] = array('old' => $val,
                                                                                           'new' => '');
                                }

                                $diffstr = $app->db->quote(serialize($diffrec));
                                $username = $app->db->quote($_SESSION["s"]["user"]["username"]);
                                $dbidx = $app->tform->formDef['db_table_idx'].":".$this->id;
                                $sql = "INSERT INTO sys_datalog (dbtable,dbidx,action,tstamp,user,data) VALUES ('".$app->tform->formDef['db_table']."','$dbidx','d','".time()."','$username','$diffstr')";
                                $app->db->query($sql);
                        }

                        $app->db->query("DELETE FROM ".$liste["table"]." WHERE ".$liste["table_idx"]." = ".$this->id);

                        // Call plugin
                        foreach($this->plugins as $plugin) {
                                $plugin->onInsert();
                        }
                }

                //header("Location: ".$liste["file"]."?PHPSESSID=".$_SESSION["s"]["id"]);
                $list_name = $_SESSION["s"]["form"]["return_to"];
                                if($list_name != '' && $_SESSION["s"]["list"][$list_name]["parent_id"] != $this->id && $_SESSION["s"]["list"][$list_name]["parent_name"] != $app->tform->formDef["name"]) {
                        $redirect = "Location: ".$_SESSION["s"]["list"][$list_name]["parent_script"]."?id=".$_SESSION["s"]["list"][$list_name]["parent_id"]."&next_tab=".$_SESSION["s"]["list"][$list_name]["parent_tab"];
                        $_SESSION["s"]["form"]["return_to"] = '';
                        session_write_close();
                        header($redirect);
                } else {
                    header("Location: ".$liste["file"]);
                }
                exit;

        }

        /**
        * Function called on page show
        */

        function onShow() {
                global $app, $conf;

                // Which tab do we render
                $this->active_tab = $app->tform->getNextTab();

                if($this->id > 0) {
                        $this->onShowEdit();
                } else {
                        $this->onShowNew();
                }

                // make Form and Tabs
                $app->tform->showForm();

                // Setting default values
                $app->tpl_defaults();

                // Calling the Plugin onShow Events and set the data in the
                // plugins placeholder in the template
                foreach($this->plugins as $plugin_name => $plugin) {
                        $app->tpl->setVar($plugin_name,$plugin->onShow());
                }

                // Parse the templates and send output to the browser
                $this->onShowEnd();

        }

        /**
        * Function called on new record
        */

        function onShowNew() {
                global $app, $conf;

                if($app->tform->errorMessage == '') {
                        $record = array();
                        $record = $app->tform->getHTML($record, $app->tform->formDef['tab_default'],'NEW');
                } else {
                        $record = $app->tform->getHTML($app->tform->encode($_POST,$this->active_tab),$this->active_tab,'EDIT');
                }

                $app->tpl->setVar($record);
        }

        /**
        * Function called on edit record
        */

        function onShowEdit() {
                global $app, $conf;

                // bestehenden Datensatz anzeigen
                if($app->tform->errorMessage == '') {
                        if($app->tform->formDef['auth'] == 'no') {
                                $sql = "SELECT * FROM ".$app->tform->formDef['db_table']." WHERE ".$app->tform->formDef['db_table_idx']." = ".$this->id;
                        } else {
                                $sql = "SELECT * FROM ".$app->tform->formDef['db_table']." WHERE ".$app->tform->formDef['db_table_idx']." = ".$this->id." AND ".$app->tform->getAuthSQL('u');
                        }
                        if(!$record = $app->db->queryOneRecord($sql)) $app->error($app->lng('error_no_view_permission'));
                } else {
                        $record = $app->tform->encode($_POST,$this->active_tab);
                }

                $this->dataRecord = $record;

            // Userdaten umwandeln
                $record = $app->tform->getHTML($record, $this->active_tab,'EDIT');
                $record['id'] = $this->id;

                $app->tpl->setVar($record);
        }

        function onShowEnd() {
                global $app, $conf;

                // Template parsen
                $app->tpl->pparse();
        }


}

?>