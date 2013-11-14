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

class db {
	var $dbHost = "";  // hostname of the MySQL server
	var $dbName = "";  // logical database name on that server
	var $dbUser = "";  // database authorized user
	var $dbPass = "";  // user's password
	var $linkId = 0;  // last result of mysql_connect()
	var $queryId = 0;  // last result of mysql_query()
	var $record = array(); // last record fetched
	var $autoCommit = 1;    // Autocommit Transactions
	var $currentRow;  // current row number
	var $errorNumber = 0; // last error number
	var $errorMessage = ""; // last error message
	var $errorLocation = "";// last error location
	var $show_error_messages = false;
	var $transID;

	// constructor
	function __construct()
	{

		global $conf;
		$this->dbHost = $conf["db_host"];
		$this->dbName = $conf["db_database"];
		$this->dbUser = $conf["db_user"];
		$this->dbPass = $conf["db_password"];
		$this->connect();
	}

	function __destruct() {
		$this->closeConn();
	}

	// error handler
	function updateError($location)
	{
		//$this->errorNumber = mysql_errno();
		$this->errorMessage = ibase_errmsg();
		$this->errorLocation = $location;
		if($this->errorNumber && $this->show_error_messages)
		{
			echo '<br /><b>'.$this->errorLocation.'</b><br />'.$this->errorMessage;
			flush();
		}
	}

	function connect()
	{
		if($this->linkId == 0)
		{
			$this->linkId = ibase_connect( $this->dbHost.":".$this->dbName , $this->dbUser, $this->dbPass, 'ISO8859_1', 0, 3 );
			if(!$this->linkId)
			{
				$this->updateError('DB::connect()<br />ibase_pconnect');
				return false;
			}
		}
		return true;
	}

	function query($queryString)
	{
		if(!$this->connect()) {
			return false;
		}

		if($this->autoCommit == 1) {
			//$transID = ibase_trans();
			$this->queryId = @ibase_query($this->linkId, $queryString);
			//ibase_commit();
		} else {
			$this->queryId = @ibase_query($this->linkId, $queryString);
		}


		$this->updateError('DB::query('.$queryString.')<br />ibase_query');
		if(!$this->queryId) {
			return false;
		}
		$this->currentRow = 0;
		return $this->queryId;
	}

	// returns all records in an array
	function queryAllRecords($queryString)
	{
		if(!$this->query($queryString))
		{
			return false;
		}
		$ret = array();
		while($line = $this->nextRecord())
		{
			$ret[] = $line;
		}
		//$this->freeResult();
		ibase_free_result($this->queryId);
		return $ret;
	}

	// returns one record in an array
	function queryOneRecord($queryString)
	{
		if(!$this->query($queryString))
		{
			return false;
		}
		$result = $this->nextRecord();
		ibase_free_result($this->queryId);
		return $result;
	}

	// returns the next record in an array
	function nextRecord()
	{
		$this->record = ibase_fetch_assoc($this->queryId);
		$this->updateError('DB::nextRecord()<br />ibase_fetch_assoc');
		if(!$this->record || !is_array($this->record))
		{
			return false;
		}
		$this->currentRow++;
		return $this->record;
	}

	// returns number of rows returned by the last select query
	function numRows()
	{
		return false;
	}

	// returns mySQL insert id
	function insertID()
	{
		return false;
	}

	// Check der variablen
	// deprecated, now use quote
	function check($formfield)
	{
		return $this->quote($formfield);
	}

	// Check der variablen
	function quote($formfield)
	{
		return str_replace("'", "''", $formfield);
	}

	// Check der variablen
	function unquote($formfield)
	{
		return str_replace("''", "'", $formfield);
	}

	function toLower($record) {
		if(is_array($record)) {
			foreach($record as $key => $val) {
				$key = strtolower($key);
				$out[$key] = $val;
			}
		}
		return $out;
	}


	function insert($tablename, $form, $debug = 0)
	{
		if(is_array($form)){
			foreach($form as $key => $value)
			{
				$sql_key .= "$key, ";
				$sql_value .= "'".$this->quote($value)."', ";
			}
			$sql_key = substr($sql_key, 0, strlen($sql_key) - 2);
			$sql_value = substr($sql_value, 0, strlen($sql_value) - 2);

			$sql = "INSERT INTO $tablename (" . $sql_key . ") VALUES (" . $sql_value .")";

			if($debug == 1) echo "SQL-Statement: ".$sql."<br><br>";
			$this->query($sql);
			if($debug == 1) echo "mySQL Error Message: ".$this->errorMessage;
		}
	}

	function update($tablename, $form, $bedingung, $debug = 0)
	{

		if(is_array($form)){
			foreach($form as $key => $value)
			{
				$insql .= "$key = '".$this->quote($value)."', ";
			}
			$insql = substr($insql, 0, strlen($insql) - 2);
			$sql = "UPDATE $tablename SET " . $insql . " WHERE $bedingung";
			if($debug == 1) echo "SQL-Statement: ".$sql."<br><br>";
			$this->query($sql);
			if($debug == 1) echo "mySQL Error Message: ".$this->errorMessage;
		}
	}

	function closeConn() {
		ibase_close($this->linkId);
	}

	function freeResult() {
		//ibase_free_result();
	}

	function delete() {

	}

	function trans($action, $transID = null) {
		//action = begin, commit oder rollback

		if($action == 'begin') {
			$this->transID = ibase_trans($this->linkId);
			return $this->transID;
		}

		if($action == 'commit' and !empty($this->transID)) {
			ibase_commit($this->linkId, $this->transID);
		}

		if($action == 'rollback') {
			ibase_rollback($this->linkId, $this->transID);
		}

	}

	/*
   $columns = array(action =>   add | alter | drop
                    name =>     Spaltenname
                    name_new => neuer Spaltenname, nur bei 'alter' belegt
                    type =>     42go-Meta-Type: int16, int32, int64, double, char, varchar, text, blob
                    typeValue => Wert z.B. bei Varchar
                    defaultValue =>  Default Wert
                    notNull =>   true | false
                    autoInc =>   true | false
                    option =>   unique | primary | index)


   */

	function createTable($table_name, $columns) {
		$index = "";
		$sql = "CREATE TABLE $table_name (";
		foreach($columns as $col){
			$sql .= $col["name"]." ".$this->mapType($col["type"], $col["typeValue"])." ";

			if($col["defaultValue"] != "") $sql .= "DEFAULT '".$col["defaultValue"]."' ";
			if($col["notNull"] == true) {
				$sql .= "NOT NULL ";
			} else {
				$sql .= "NULL ";
			}
			if($col["autoInc"] == true) $sql .= "auto_increment ";
			$sql.= ",";
			// key Definitionen
			if($col["option"] == "primary") $index .= "PRIMARY KEY (".$col["name"]."),";
			if($col["option"] == "index") $index .= "INDEX (".$col["name"]."),";
			if($col["option"] == "unique") $index .= "UNIQUE (".$col["name"]."),";
		}
		$sql .= $index;
		$sql = substr($sql, 0, -1);
		$sql .= ")";

		$this->query($sql);
		return true;
	}

	/*
   $columns = array(action =>   add | alter | drop
                    name =>     Spaltenname
                    name_new => neuer Spaltenname, nur bei 'alter' belegt
                    type =>     42go-Meta-Type: int16, int32, int64, double, char, varchar, text, blob
                    typeValue => Wert z.B. bei Varchar
                    defaultValue =>  Default Wert
                    notNull =>   true | false
                    autoInc =>   true | false
                    option =>   unique | primary | index)


   */
	function alterTable($table_name, $columns) {
		return false;
	}

	function dropTable($table_name) {
		$this->check($table_name);
		$sql = "DROP TABLE '". $table_name."'";
		return $this->query($sql);
	}

	// gibt Array mit Tabellennamen zurück
	function getTables($database_name) {
		return false;
	}

	// gibt Feldinformationen zur Tabelle zurück
	/*
   $columns = array(action =>   add | alter | drop
                    name =>     Spaltenname
                    name_new => neuer Spaltenname, nur bei 'alter' belegt
                    type =>     42go-Meta-Type: int16, int32, int64, double, char, varchar, text, blob
                    typeValue => Wert z.B. bei Varchar
                    defaultValue =>  Default Wert
                    notNull =>   true | false
                    autoInc =>   true | false
                    option =>   unique | primary | index)


   */

	function tableInfo($table_name) {
		return false;
	}

	function mapType($metaType, $typeValue) {
		global $go_api;
		$metaType = strtolower($metaType);
		switch ($metaType) {
		case 'int16':
			return 'smallint';
			break;
		case 'int32':
			return 'int';
			break;
		case 'int64':
			return 'bigint';
			break;
		case 'double':
			return 'double';
			break;
		case 'char':
			return 'char';
			break;
		case 'varchar':
			if($typeValue < 1) $go_api->errorMessage("Datenbank Fehler: Für diesen Datentyp ist eine Längenangabe notwendig.");
			return 'varchar('.$typeValue.')';
			break;
		case 'text':
			return 'text';
			break;
		case 'blob':
			return 'blob';
			break;
		}
	}

}

?>
