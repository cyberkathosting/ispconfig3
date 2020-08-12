<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

class tform_actions {

	public $id;
	public $activeTab;
	public $dataRecord;
	public $plugins = array();
	public $oldDataRecord; // This array is only filled during updates and when db_history is enabled.

	function onLoad() {
		global $app, $conf, $tform_def_file;

		// Loading template classes and initialize template
		if(!is_object($app->tpl)) $app->uses('tpl');
		if(!is_object($app->tform)) $app->uses('tform');

		$app->tpl->newTemplate("tabbed_form.tpl.htm");

		// Load table definition from file
		//$app->tform->loadFormDef($tform_def_file, (isset($_SESSION['s']['module']['name']) ? $_SESSION['s']['module']['name'] : ''));
		$app->tform->loadFormDef($tform_def_file);

		// Importing ID
		$this->id = (isset($_REQUEST["id"]))?$app->functions->intval($_REQUEST["id"]):0;

		// show print version of the form
		if(isset($_GET["print_form"]) && $_GET["print_form"] == 1) {
			die('Function disabled.');
			$this->onPrintForm();
		}

		// send this form by email
		if(isset($_GET["send_form_by_mail"]) && $_GET["send_form_by_mail"] == 1) {
			die('Function disabled.');
			$this->onMailSendForm();
		}

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

		// check if the client is locked - he may not change anything, then.
		if(!$app->auth->is_admin()) {
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT client.locked FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			if(is_array($client) && $client['locked'] == 'y') {
				$app->tform->errorMessage .= $app->lng("client_you_are_locked")."<br />";
			}
		}

		// Calling the action functions
		if($this->id > 0) {
			$app->tform->action == 'EDIT';
			$this->onUpdate();
		} else {
			$app->tform->action == 'NEW';
			$this->onInsert();
		}
	}


	/**
	 * Function called on data update
	 */
	function onUpdate() {
		global $app, $conf;

		$this->onBeforeUpdate();
		$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_before_update', $this);

		$ext_where = '';
		// pre-fill dataRecord array with elements from database to avoid overwriting fields set by admin but hidden for client
		//$tmp_old_record = $app->tform->getDataRecord($this->id);
		//if($tmp_old_record && is_array($tmp_old_record)) $this->dataRecord = array_merge($tmp_old_record, $this->dataRecord);
		// disabled due to problems with mail users, see https://git.ispconfig.org/ispconfig/ispconfig3/issues/4513
		$sql = $app->tform->getSQL($this->dataRecord, $app->tform->getCurrentTab(), 'UPDATE', $this->id, $ext_where);
		if($app->tform->errorMessage == '') {

			$this->oldDataRecord = $app->tform->getDataRecord($this->id);

			// Save record in database
			$this->onUpdateSave($sql);
			$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_update_save', array('page_form'=>$this, 'sql'=>$sql));

			// loading plugins
			$next_tab = $app->tform->getCurrentTab();
			$this->loadPlugins($next_tab);

			// Call plugin
			foreach($this->plugins as $plugin) {
				$plugin->onUpdate();
			}

			$this->onAfterUpdate();
			$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_after_update', $this);

			// Write data history (sys_datalog)
			if($app->tform->formDef['db_history'] == 'yes') {
				$new_data_record = $app->tform->getDataRecord($this->id);
				$app->tform->datalogSave('UPDATE', $this->id, $this->oldDataRecord, $new_data_record);
				unset($new_data_record);
				unset($old_data_record);
			}

			if($_REQUEST["next_tab"] == '') {
				$list_name = $_SESSION["s"]["form"]["return_to"];
				// When a list is embedded inside of a form

				//if($list_name != '' && $_SESSION["s"]["list"][$list_name]["parent_id"] != $this->id && $_SESSION["s"]["list"][$list_name]["parent_name"] != $app->tform->formDef["name"]) {
				if($list_name != '' && $_SESSION["s"]["list"][$list_name]["parent_name"] != $app->tform->formDef["name"]) {
					$redirect = "Location: ".$_SESSION["s"]["list"][$list_name]["parent_script"]."?id=".$_SESSION["s"]["list"][$list_name]["parent_id"]."&next_tab=".$_SESSION["s"]["list"][$list_name]["parent_tab"];
					$_SESSION["s"]["form"]["return_to"] = '';
					session_write_close();
					header($redirect);
					// When a returnto variable is set
				} elseif (isset($_SESSION["s"]["form"]["return_to_url"]) && $_SESSION["s"]["form"]["return_to_url"] != '') {
					$redirect = $_SESSION["s"]["form"]["return_to_url"];
					$_SESSION["s"]["form"]["return_to_url"] = '';
					session_write_close();
					header("Location: ".$redirect);
					exit;
					// Use the default list of the form
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

	/*
		 Save record in database
		*/

	function onUpdateSave($sql) {
		global $app;
		if(!empty($sql) && !$app->tform->isReadonlyTab($app->tform->getCurrentTab(), $this->id)) {
			$app->db->query($sql);
			if($app->db->errorMessage != '') die($app->db->errorMessage);
		}
	}





	/**
	 * Function called on data insert
	 */
	function onInsert() {
		global $app, $conf;

		$this->onBeforeInsert();
		$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_before_insert', $this);

		$ext_where = '';
		$sql = $app->tform->getSQL($this->dataRecord, $app->tform->getCurrentTab(), 'INSERT', $this->id, $ext_where);
		if($app->tform->errorMessage == '') {

			$this->id = $this->onInsertSave($sql);
			$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_insert_save', array('page_form'=>$this, 'sql'=>$sql));

			// loading plugins
			$next_tab = $app->tform->getCurrentTab();
			$this->loadPlugins($next_tab);

			// Call plugin
			foreach($this->plugins as $plugin) {
				$plugin->onInsert();
			}

			$this->onAfterInsert();
			$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_after_insert', $this);

			// Write data history (sys_datalog)
			if($app->tform->formDef['db_history'] == 'yes') {
				$new_data_record = $app->tform->getDataRecord($this->id);
				$app->tform->datalogSave('INSERT', $this->id, array(), $new_data_record);
				unset($new_data_record);
			}


			if($_REQUEST["next_tab"] == '') {
				$list_name = $_SESSION["s"]["form"]["return_to"];
				// if($list_name != '' && $_SESSION["s"]["list"][$list_name]["parent_id"] != $this->id && $_SESSION["s"]["list"][$list_name]["parent_name"] != $app->tform->formDef["name"]) {
				if($list_name != '' && $_SESSION["s"]["list"][$list_name]["parent_name"] != $app->tform->formDef["name"]) {
					$redirect = "Location: ".$_SESSION["s"]["list"][$list_name]["parent_script"]."?id=".$_SESSION["s"]["list"][$list_name]["parent_id"]."&next_tab=".$_SESSION["s"]["list"][$list_name]["parent_tab"];
					$_SESSION["s"]["form"]["return_to"] = '';
					session_write_close();
					header($redirect);
					exit;
				} elseif ($_SESSION["s"]["form"]["return_to_url"] != '') {
					$redirect = $_SESSION["s"]["form"]["return_to_url"];
					$_SESSION["s"]["form"]["return_to_url"] = '';
					session_write_close();
					header("Location: ".$redirect);
					exit;
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

	/*
		 Save record in database
		*/

	function onInsertSave($sql) {
		global $app, $conf;
		$app->db->query($sql);
		if($app->db->errorMessage != '') die($app->db->errorMessage);
		return $app->db->insertID();
	}

	function onBeforeUpdate() {
		global $app, $conf;
	}

	function onBeforeInsert() {
		global $app, $conf;
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

		$app->tpl->setVar("error", "<li>".$app->tform->errorMessage."</li>");
		$app->tpl->setVar($this->dataRecord, null, true);
		$this->onShow();
	}


	/**
	 * Function called on data delete
	 */
	function onDelete() {
		global $app, $conf, $list_def_file, $tform_def_file;
		
		// Check CSRF Token
		$app->auth->csrf_token_check('GET');

		include_once $list_def_file;

		// Loading tform framework
		if(!is_object($app->tform)) $app->uses('tform');

		// Load table definition from file
		$app->tform->loadFormDef($tform_def_file);

		// importing ID
		$this->id = $app->functions->intval($_REQUEST["id"]);

		if($this->id > 0) {

			// checking permissions
			if($app->tform->formDef['auth'] == 'yes' && $_SESSION["s"]["user"]["typ"] != 'admin') {
				if($app->tform->checkPerm($this->id, 'd') == false) $app->error($app->lng('error_no_delete_permission'));
			}

			$this->dataRecord = $app->tform->getDataRecord($this->id);

			$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_check_delete', $this);
			$this->onBeforeDelete();
			$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_before_delete', $this);

			// Saving record to datalog when db_history enabled
			if($app->tform->formDef["db_history"] == 'yes') {
				//$old_data_record = $app->tform->getDataRecord($this->id);
				$app->tform->datalogSave('DELETE', $this->id, $this->dataRecord, array());
			}

			$app->db->query("DELETE FROM ?? WHERE ?? = ? LIMIT 1", $app->tform->formDef['db_table'], $app->tform->formDef['db_table_idx'], $this->id);


			// loading plugins
			$next_tab = $app->tform->getCurrentTab();
			$this->loadPlugins($next_tab);


			// Call plugin
			foreach($this->plugins as $plugin) {
				$plugin->onDelete();
			}

			$this->onAfterDelete();
			$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'].':'.'on_after_delete', $this);
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

	function onBeforeDelete() {
		global $app, $conf;
	}

	function onAfterDelete() {
		global $app, $conf;
	}





	/**
	 * Function to print the form content
	 */
	function onPrintForm() {
		global $app, $conf;

		if($app->tform->formDef['template_print'] == '') die('No print template available.');

		$app->tpl->newTemplate("print.tpl.htm");
		$app->tpl->setInclude("content_tpl", $app->tform->formDef['template_print']);

		if($app->tform->formDef['auth'] == 'no') {
			$sql = "SELECT * FROM ?? WHERE ?? = ?";
		} else {
			$sql = "SELECT * FROM ?? WHERE ?? = ? AND ".$app->tform->getAuthSQL('r');
		}
		if(!$record = $app->db->queryOneRecord($sql, $app->tform->formDef['db_table'], $app->tform->formDef['db_table_idx'], $this->id)) $app->error($app->lng('error_no_view_permission'));

		$record["datum"] = date("d.m.Y");

		$app->tpl->setVar($app->tform->wordbook);

		$app->tpl->setVar($record);
		$app->tpl_defaults();
		$app->tpl->pparse();
		exit;

	}





	/**
	 * Function to print the form content
	 */
	function onMailSendForm() {
		global $app, $conf;

		if($app->tform->formDef['template_mailsend'] == '') die('No print template available.');

		if($_POST["email"] == '' && $_POST["sender"] == '') {
			// Zeige Formular zum versenden an.
			$app->tpl->newTemplate("form.tpl.htm");
			$app->tpl->setInclude("content_tpl", $app->tform->formDef['template_mailsend']);
			$app->tpl->setVar('show_form', 1);
			$app->tpl->setVar("form_action", $app->tform->formDef['action'].'?send_form_by_mail=1');
			$app->tpl->setVar("id", $this->id);
			$app->tpl_defaults();
			$app->tpl->pparse();
			exit;
		} else {
			$app->tpl->newTemplate("mail.tpl.htm");
			$app->tpl->setInclude("content_tpl", $app->tform->formDef['template_mailsend']);
			$app->tpl->setVar('show_mail', 1);
			if($app->tform->formDef['auth'] == 'no') {
				$sql = "SELECT * FROM ?? WHERE ?? = ?";
			} else {
				$sql = "SELECT * FROM ?? WHERE ?? = ? AND ".$app->tform->getAuthSQL('r');
			}
			if(!$record = $app->db->queryOneRecord($sql, $app->tform->formDef['db_table'], $app->tform->formDef['db_table_idx'], $this->id)) $app->error($app->lng('error_no_view_permission'));

			$record["datum"] = date("d.m.Y");
			$record["mailmessage"] = $_POST["message"];

			$app->tpl->setVar($app->tform->wordbook);

			$app->tpl->setVar($record);
			$app->tpl_defaults();

			$email_message = $app->tpl->grab();
			$email = $_POST["email"];
			$sender = $_POST["sender"];

			$headers  = "MIME-Version: 1.0\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1\n";
			$headers .= "From: $sender\n";

			if (!preg_match('/^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+' . '@' . '([-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.)+' . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$/', $sender)) {
				$sender = 'noreply@iprguard.de';
			}

			if (preg_match('/^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+' . '@' . '([-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.)+' . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$/', $email)) {
				mail($email, 'Domainrecherche Statement '.$record["domain"], $email_message, $headers);
			}
			echo "<p>&nbsp;</p><p>Email wurde versand.</p>";
			exit;
		}



		if($app->tform->formDef['auth'] == 'no') {
			$sql = "SELECT * FROM ?? WHERE ?? = ?";
		} else {
			$sql = "SELECT * FROM ?? WHERE ?? = ? AND ".$app->tform->getAuthSQL('r');
		}
		if(!$record = $app->db->queryOneRecord($sql, $app->tform->formDef['db_table'], $app->tform->formDef['db_table_idx'], $this->id)) $app->error($app->lng('error_no_view_permission'));

		$record["datum"] = date("d.m.Y");

		$app->tpl->setVar($app->tform->wordbook);

		$app->tpl->setVar($record);
		$app->tpl_defaults();
		$app->tpl->pparse();
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

		// Show the navigation bar of the form
		if(isset($app->tform->formDef['navibar']) && $app->tform->formDef['navibar'] == 'yes') {
			$navibar = '';
			if($app->tform->formDef['template_print'] != '') {
				$navibar .= '<a href="'.$app->tform->formDef['action'].'?id='.$this->id.'&print_form=1" target="_blank"><img src="../themes/iprg/icons/printer.png" border="0" alt="Drucken" /></a> &nbsp;';
			}
			if($app->tform->formDef['template_mailsend'] != '') {
				$navibar .= "<a href=\"#\" onclick=\"window.open('".$app->tform->formDef['action'].'?id='.$this->id."&send_form_by_mail=1','send','width=370,height=240')\"><img src=\"../themes/iprg/icons/mail.png\" border=\"0\" alt=\"Als E-Mail versenden\" /></a>";
			}
			$app->tpl->setVar('form_navibar', $navibar);
		}

		if(isset($_SESSION['show_info_msg'])) {
			$app->tpl->setVar('show_info_msg', $_SESSION['show_info_msg']);
			unset($_SESSION['show_info_msg']);
		}
		if(isset($_SESSION['show_error_msg'])) {
			$app->tpl->setVar('show_error_msg', $_SESSION['show_error_msg']);
			unset($_SESSION['show_error_msg']);
		}

		// loading plugins
		$this->loadPlugins($this->active_tab);

		// Calling the Plugin onShow Events and set the data in the
		// plugins placeholder in the template
		foreach($this->plugins as $plugin_name => $plugin) {
			$app->tpl->setVar($plugin_name, $plugin->onShow());
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
			$record = $app->tform->getHTML($record, $app->tform->formDef['tab_default'], 'NEW');
		} else {
			$record = $app->tform->getHTML($app->tform->encode($_POST, $this->active_tab), $this->active_tab, 'EDIT');
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
			if($app->tform->formDef['auth'] == 'yes' && $_SESSION["s"]["user"]["typ"] != 'admin') {
				$sql = "SELECT * FROM ?? WHERE ?? = ? AND ".$app->tform->getAuthSQL('r');
			} else {
				$sql = "SELECT * FROM ?? WHERE ?? = ?";
			}
			if(!$record = $app->db->queryOneRecord($sql, $app->tform->formDef['db_table'], $app->tform->formDef['db_table_idx'], $this->id)) $app->error($app->lng('error_no_view_permission'));
		} else {
			// $record = $app->tform->encode($_POST,$this->active_tab);
			$record = $app->tform->encode($this->dataRecord, $this->active_tab, false);
		}

		$this->dataRecord = $record;

		// Userdaten umwandeln
		$record = $app->tform->getHTML($record, $this->active_tab, 'EDIT');
		$record['id'] = $this->id;

		$app->tpl->setVar($record);
	}

	function onShowEnd() {
		global $app, $conf;

		// Template parsen
		$app->tpl->pparse();
	}

	function loadPlugins($next_tab) {
		global $app;
		if(@is_array($app->tform->formDef["tabs"][$next_tab]["plugins"])) {
			$app->load('plugin_base');
			foreach($app->tform->formDef["tabs"][$next_tab]["plugins"] as $plugin_name => $plugin_settings) {
				$plugin_class = $plugin_settings["class"];
				$app->load($plugin_class);
				$this->plugins[$plugin_name] = new $plugin_class;
				$this->plugins[$plugin_name]->setOptions($plugin_name, $plugin_settings['options']);
				// Make the data of the form easily accessible for the plugin
				$this->plugins[$plugin_name]->form = $this;
				$this->plugins[$plugin_name]->onLoad();
			}
		}
	}


}

?>
