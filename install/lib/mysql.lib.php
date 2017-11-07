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

class db
{
	/**#@+
     * @access private
     */
	private $_iQueryId;
	private $_iConnId;

	private $dbHost = '';  // hostname of the MySQL server
	private $dbPort = '';  // port of the MySQL server
	private $dbName = '';  // logical database name on that server
	private $dbUser = '';  // database authorized user
	private $dbPass = '';  // user's password
	private $dbCharset = 'utf8';// Database charset
	private $dbNewLink = false; // Return a new linkID when connect is called again
	private $dbClientFlags = 0; // MySQL Client falgs
	/**#@-*/

	public $show_error_messages = false; // false in server, true in interface


	/* old things - unused now ////
	private $linkId = 0;  // last result of mysqli_connect()
	private $queryId = 0;  // last result of mysqli_query()
	private $record = array(); // last record fetched
	private $autoCommit = 1;    // Autocommit Transactions
	private $currentRow;  // current row number
	public $errorNumber = 0; // last error number
	public $errorMessage = ''; // last error message
	private $errorLocation = '';// last error location
	private $isConnected = false; // needed to know if we have a valid mysqli object from the constructor
	////
	*/

	public function __destruct() {
		if($this->_iConnId) mysqli_close($this->_iConnId);
	}
	
	private function do_connect() {
		global $conf;
		
		if($this->_iConnId) return true;
		$this->dbHost = $conf['mysql']['host'];
		$this->dbPort = $conf['mysql']['port'];
		$this->dbName = false;//$conf["mysql"]["database"];
		$this->dbUser = $conf["mysql"]["admin_user"];
		$this->dbPass = $conf["mysql"]["admin_password"];
		$this->dbCharset = $conf["mysql"]["charset"];
		$this->dbNewLink = false;
		$this->dbClientFlags = null;
		
		$this->_iConnId = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, '', (int)$this->dbPort);
		$try = 0;
		while((!is_object($this->_iConnId) || mysqli_connect_error()) && $try < 5) {
			if($try > 0) sleep(1);

			$try++;
			$this->_iConnId = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, '', (int)$this->dbPort);
		}

		if(!is_object($this->_iConnId) || mysqli_connect_error()) {
			$this->_iConnId = null;
			$this->_sqlerror('Zugriff auf Datenbankserver fehlgeschlagen! / Database server not accessible!');
			return false;
		}
		
		if($this->dbName) $this->setDBName($this->dbName);

		$this->_setCharset();
	}
	
	public function setDBData($host, $user, $password, $port) {
		$this->dbHost = $host;
		$this->dbUser = $user;
		$this->dbPass = $password;
		$this->dbPort = $port;
	}
	
	public function setDBName($name) {
		$this->dbName = $name;
		$this->_iConnId = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, '', (int)$this->dbPort);
		if(!((bool)mysqli_query( $this->_iConnId, 'USE `' . $this->dbName . '`'))) {
			$this->close();
			$this->_sqlerror('Datenbank nicht gefunden / Database not found');
			return false;
		}
	}
	
	public function close() {
		if($this->_iConnId) mysqli_close($this->_iConnId);
		$this->_iConnId = null;
	}

	/* This allows our private variables to be "read" out side of the class */
	public function __get($var) {
		return isset($this->$var) ? $this->$var : NULL;
	}

	public function _build_query_string($sQuery = '') {
		$iArgs = func_num_args();
		if($iArgs > 1) {
			$aArgs = func_get_args();

			if($iArgs == 3 && $aArgs[1] === true && is_array($aArgs[2])) {
				$aArgs = $aArgs[2];
				$iArgs = count($aArgs);
			} else {
				array_shift($aArgs); // delete the query string that is the first arg!
			}

			$iPos = 0;
			$iPos2 = 0;
			foreach($aArgs as $sKey => $sValue) {
				$iPos2 = strpos($sQuery, '??', $iPos2);
				$iPos = strpos($sQuery, '?', $iPos);

				if($iPos === false && $iPos2 === false) break;

				if($iPos2 !== false && ($iPos === false || $iPos2 <= $iPos)) {
					$sTxt = $this->escape($sValue);

					if(strpos($sTxt, '.') !== false) {
						$sTxt = preg_replace('/^(.+)\.(.+)$/', '`$1`.`$2`', $sTxt);
						$sTxt = str_replace('.`*`', '.*', $sTxt);
					} else $sTxt = '`' . $sTxt . '`';

					$sQuery = substr_replace($sQuery, $sTxt, $iPos2, 2);
					$iPos2 += strlen($sTxt);
					$iPos = $iPos2;
				} else {
					if(is_int($sValue) || is_float($sValue)) {
						$sTxt = $sValue;
					} elseif(is_string($sValue) && (strcmp($sValue, '#NULL#') == 0)) {
						$sTxt = 'NULL';
					} elseif(is_array($sValue)) {
						$sTxt = '';
						foreach($sValue as $sVal) $sTxt .= ',\'' . $this->escape($sVal) . '\'';
						$sTxt = '(' . substr($sTxt, 1) . ')';
						if($sTxt == '()') $sTxt = '(0)';
					} else {
						$sTxt = '\'' . $this->escape($sValue) . '\'';
					}

					$sQuery = substr_replace($sQuery, $sTxt, $iPos, 1);
					$iPos += strlen($sTxt);
					$iPos2 = $iPos;
				}
			}
		}

		return $sQuery;
	}

	/**#@-*/


	/**#@+
     * @access private
     */
	private function _setCharset() {
		mysqli_query($this->_iConnId, 'SET NAMES '.$this->dbCharset);
		mysqli_query($this->_iConnId, "SET character_set_results = '".$this->dbCharset."', character_set_client = '".$this->dbCharset."', character_set_connection = '".$this->dbCharset."', character_set_database = '".$this->dbCharset."', character_set_server = '".$this->dbCharset."'");
	}

	private function _query($sQuery = '') {
		$this->do_connect();

		if ($sQuery == '') {
			$this->_sqlerror('Keine Anfrage angegeben / No query given');
			return false;
		}

		$try = 0;
		do {
			$try++;
			$ok = mysqli_ping($this->_iConnId);
			if(!$ok) {
				if(!mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName, (int)$this->dbPort)) {
					if($this->errorNumber == '111') {
						// server is not available
						if($try > 9) {
							$this->_sqlerror('DB::query -> error connecting');
							exit;
						}
						sleep(30); // additional seconds, please!
					}

					if($try > 9) {
						$this->_sqlerror('DB::query -> reconnect');
						return false;
					} else {
						sleep(($try > 7 ? 5 : 1));
					}
				} else {
					$this->_setCharset();
					$ok = true;
				}
			}
		} while($ok == false);

		$aArgs = func_get_args();
		$sQuery = call_user_func_array(array(&$this, '_build_query_string'), $aArgs);

		$this->_iQueryId = mysqli_query($this->_iConnId, $sQuery);
		if (!$this->_iQueryId) {
			$this->_sqlerror('Falsche Anfrage / Wrong Query', false, 'SQL-Query = ' . $sQuery);
			return false;
		}

		return is_bool($this->_iQueryId) ? $this->_iQueryId : new db_result($this->_iQueryId, $this->_iConnId);
	}

	/**#@-*/





	/**
	 * Executes a query
	 *
	 * Executes a given query string, has a variable amount of parameters:
	 * - 1 parameter
	 *   executes the given query
	 * - 2 parameters
	 *   executes the given query, replaces the first ? in the query with the second parameter
	 * - 3 parameters
	 *   if the 2nd parameter is a boolean true, the 3rd parameter has to be an array containing all the replacements for every occuring ? in the query, otherwise the second parameter replaces the first ?, the third parameter replaces the second ? in the query
	 * - 4 or more parameters
	 *   all ? in the query are replaced from left to right by the parameters 2 to x
	 *
	 * @access public
	 * @param string  $sQuery query string
	 * @param mixed   ... one or more parameters
	 * @return db_result the result object of the query
	 */


	public function query($sQuery = '') {
		$aArgs = func_get_args();
		return call_user_func_array(array(&$this, '_query'), $aArgs);
	}

	/**
	 * Execute a query and get first result array
	 *
	 * Executes a query and returns the first result row as an array
	 * This is like calling $result = $db->query(),  $result->get(), $result->free()
	 * Use of this function @see query
	 *
	 * @access public
	 * @param string  $sQuery query to execute
	 * @param ...     further params (see query())
	 * @return array result row or NULL if none found
	 */
	public function queryOneRecord($sQuery = '') {
		if(!preg_match('/limit \d+\s*,\s*\d+$/i', $sQuery)) $sQuery .= ' LIMIT 0,1';

		$aArgs = func_get_args();
		$oResult = call_user_func_array(array(&$this, 'query'), $aArgs);
		if(!$oResult) return null;

		$aReturn = $oResult->get();
		$oResult->free();

		return $aReturn;
	}

	public function queryOne($sQuery = '') {
		return call_user_func_array(array(&$this, 'queryOneRecord'), func_get_args());
	}

	public function query_one($sQuery = '') {
		return call_user_func_array(array(&$this, 'queryOneRecord'), func_get_args());
	}

	/**
	 * Execute a query and return all rows
	 *
	 * Executes a query and returns all result rows in an array
	 * <strong>Use this with extreme care!!!</strong> Uses lots of memory on big result sets.
	 *
	 * @access public
	 * @param string  $sQuery query to execute
	 * @param ...     further params (see query())
	 * @return array all the rows in the result set
	 */
	public function queryAllRecords($sQuery = '') {
		$aArgs = func_get_args();
		$oResult = call_user_func_array(array(&$this, 'query'), $aArgs);
		if(!$oResult) return array();

		$aResults = array();
		while($aRow = $oResult->get()) {
			$aResults[] = $aRow;
		}
		$oResult->free();

		return $aResults;
	}

	public function queryAll($sQuery = '') {
		return call_user_func_array(array(&$this, 'queryAllRecords'), func_get_args());
	}

	public function query_all($sQuery = '') {
		return call_user_func_array(array(&$this, 'queryAllRecords'), func_get_args());
	}

	/**
	 * Execute a query and return all rows as simple array
	 *
	 * Executes a query and returns all result rows in an array with elements
	 * <strong>Only first column is returned</strong> Uses lots of memory on big result sets.
	 *
	 * @access public
	 * @param string  $sQuery query to execute
	 * @param ...     further params (see query())
	 * @return array all the rows in the result set
	 */
	public function queryAllArray($sQuery = '') {
		$aArgs = func_get_args();
		$oResult = call_user_func_array(array(&$this, 'query'), $aArgs);
		if(!$oResult) return array();

		$aResults = array();
		while($aRow = $oResult->get()) {
			$aResults[] = reset($aRow);
		}
		$oResult->free();

		return $aResults;
	}

	public function query_all_array($sQuery = '') {
		return call_user_func_array(array(&$this, 'queryAllArray'), func_get_args());
	}



	/**
	 * Get id of last inserted row
	 *
	 * Gives you the id of the last inserted row in a table with an auto-increment primary key
	 *
	 * @access public
	 * @return int id of last inserted row or 0 if none
	 */
	public function insert_id() {
		$iRes = mysqli_query($this->_iConnId, 'SELECT LAST_INSERT_ID() as `newid`');
		if(!is_object($iRes)) return false;

		$aReturn = mysqli_fetch_assoc($iRes);
		mysqli_free_result($iRes);

		return $aReturn['newid'];
	}



	/**
	 * get affected row count
	 *
	 * Gets the amount of rows affected by the previous query
	 *
	 * @access public
	 * @return int affected rows
	 */
	public function affected() {
		if(!is_object($this->_iConnId)) return 0;
		$iRows = mysqli_affected_rows($this->_iConnId);
		if(!$iRows) $iRows = 0;
		return $iRows;
	}



	/**
	 * check if a utf8 string is valid
	 *
	 * @access public
	 * @param string  $string the string to check
	 * @return bool true if it is valid utf8, false otherwise
	 */
	private function check_utf8($str) {
		$len = strlen($str);
		for($i = 0; $i < $len; $i++){
			$c = ord($str[$i]);
			if ($c > 128) {
				if (($c > 247)) return false;
				elseif ($c > 239) $bytes = 4;
				elseif ($c > 223) $bytes = 3;
				elseif ($c > 191) $bytes = 2;
				else return false;
				if (($i + $bytes) > $len) return false;
				while ($bytes > 1) {
					$i++;
					$b = ord($str[$i]);
					if ($b < 128 || $b > 191) return false;
					$bytes--;
				}
			}
		}
		return true;
	} // end of check_utf8

	/**
	 * Escape a string for usage in a query
	 *
	 * @access public
	 * @param string  $sString query string to escape
	 * @return string escaped string
	 */
	public function escape($sString) {
		if(!is_string($sString) && !is_numeric($sString)) {
			$sString = '';
		}

		$cur_encoding = mb_detect_encoding($sString);
		if($cur_encoding != "UTF-8") {
			if($cur_encoding != 'ASCII') {
				if($cur_encoding) $sString = mb_convert_encoding($sString, 'UTF-8', $cur_encoding);
				else $sString = mb_convert_encoding($sString, 'UTF-8');
			}
		} elseif(!$this->check_utf8($sString)) {
			$sString = utf8_encode($sString);
		}

		if($this->_iConnId) return mysqli_real_escape_string($this->_iConnId, $sString);
		else return addslashes($sString);
	}

	/**
	 *
	 *
	 * @access private
	 */
	private function _sqlerror($sErrormsg = 'Unbekannter Fehler', $sAddMsg = '') {
		global $conf;

		$mysql_error = (is_object($this->_iConnId) ? mysqli_error($this->_iConnId) : mysqli_connect_error());
		$mysql_errno = (is_object($this->_iConnId) ? mysqli_errno($this->_iConnId) : mysqli_connect_errno());

		//$sAddMsg .= getDebugBacktrace();

		if($this->show_error_messages && $conf['demo_mode'] === false) {
			echo $sErrormsg . $sAddMsg;
		}
	}

	public function affectedRows() {
		return $this->affected();
	}

	// returns mySQL insert id
	public function insertID() {
		return $this->insert_id();
	}


	//* Function to quote strings
	public function quote($formfield) {
		return $this->escape($formfield);
	}

	//* Function to unquotae strings
	public function unquote($formfield) {
		return stripslashes($formfield);
	}

	public function toLower($record) {
		if(is_array($record)) {
			foreach($record as $key => $val) {
				$key = strtolower($key);
				$out[$key] = $val;
			}
		}
		return $out;
	}

	/* TODO: rewrite SQL */
	function insert($tablename, $form, $debug = 0)
	{
		if(is_array($form)){
			foreach($form as $key => $value)
			{
				$sql_key .= "$key, ";
				$sql_value .= "'".$this->check($value)."', ";
			}
			$sql_key = substr($sql_key, 0, strlen($sql_key) - 2);
			$sql_value = substr($sql_value, 0, strlen($sql_value) - 2);

			$sql = "INSERT INTO $tablename (" . $sql_key . ") VALUES (" . $sql_value .")";

			if($debug == 1) echo "SQL-Statement: ".$sql."<br><br>";
			$this->query($sql);
			if($debug == 1) echo "mySQL Error Message: ".$this->errorMessage;
		}
	}
	
	/* TODO: rewrite SQL */
	function update($tablename, $form, $bedingung, $debug = 0)
	{

		if(is_array($form)){
			foreach($form as $key => $value)
			{
				$insql .= "$key = '".$this->check($value)."', ";
			}
			$insql = substr($insql, 0, strlen($insql) - 2);
			$sql = "UPDATE $tablename SET " . $insql . " WHERE $bedingung";
			if($debug == 1) echo "SQL-Statement: ".$sql."<br><br>";
			$this->query($sql);
			if($debug == 1) echo "mySQL Error Message: ".$this->errorMessage;
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
	/* TODO: rewrite SQL */
	public function createTable($table_name, $columns) {
		$index = '';
		$sql = "CREATE TABLE ?? (";
		foreach($columns as $col){
			$sql .= $col['name'].' '.$this->mapType($col['type'], $col['typeValue']).' ';

			if($col['defaultValue'] != '') $sql .= "DEFAULT '".$col['defaultValue']."' ";
			if($col['notNull'] == true) {
				$sql .= 'NOT NULL ';
			} else {
				$sql .= 'NULL ';
			}
			if($col['autoInc'] == true) $sql .= 'auto_increment ';
			$sql.= ',';
			// key Definitionen
			if($col['option'] == 'primary') $index .= 'PRIMARY KEY ('.$col['name'].'),';
			if($col['option'] == 'index') $index .= 'INDEX ('.$col['name'].'),';
			if($col['option'] == 'unique') $index .= 'UNIQUE ('.$col['name'].'),';
		}
		$sql .= $index;
		$sql = substr($sql, 0, -1);
		$sql .= ')';
		/* TODO: secure parameters */
		$this->query($sql, $table_name);
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
    /* TODO: rewrite SQL */
	public function alterTable($table_name, $columns) {
		$index = '';
		$sql = "ALTER TABLE ?? ";
		foreach($columns as $col){
			if($col['action'] == 'add') {
				$sql .= 'ADD '.$col['name'].' '.$this->mapType($col['type'], $col['typeValue']).' ';
			} elseif ($col['action'] == 'alter') {
				$sql .= 'CHANGE '.$col['name'].' '.$col['name_new'].' '.$this->mapType($col['type'], $col['typeValue']).' ';
			} elseif ($col['action'] == 'drop') {
				$sql .= 'DROP '.$col['name'].' ';
			}
			if($col['action'] != 'drop') {
				if($col['defaultValue'] != '') $sql .= "DEFAULT '".$col['defaultValue']."' ";
				if($col['notNull'] == true) {
					$sql .= 'NOT NULL ';
				} else {
					$sql .= 'NULL ';
				}
				if($col['autoInc'] == true) $sql .= 'auto_increment ';
				$sql.= ',';
				// Index definitions
				if($col['option'] == 'primary') $index .= 'PRIMARY KEY ('.$col['name'].'),';
				if($col['option'] == 'index') $index .= 'INDEX ('.$col['name'].'),';
				if($col['option'] == 'unique') $index .= 'UNIQUE ('.$col['name'].'),';
			}
		}
		$sql .= $index;
		$sql = substr($sql, 0, -1);
		/* TODO: secure parameters */
		//die($sql);
		$this->query($sql, $table_name);
		return true;
	}

	public function dropTable($table_name) {
		$this->check($table_name);
		$sql = "DROP TABLE ??";
		return $this->query($sql, $table_name);
	}

	// gibt Array mit Tabellennamen zur�ck
	public function getTables($database_name = '') {
		if(!is_object($this->_iConnId)) return false;
		if($database_name == '') $database_name = $this->dbName;
		$tb_names = $this->queryAllArray("SHOW TABLES FROM ??", $database_name);
		return $tb_names;
	}

	// gibt Feldinformationen zur Tabelle zur�ck
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
	/* TODO: rewrite SQL */
	function tableInfo($table_name) {

		global $go_api, $go_info;
		// Tabellenfelder einlesen

		if($rows = $go_api->db->queryAllRecords('SHOW FIELDS FROM ??', $table_name)){
			foreach($rows as $row) {
				$name = $row['Field'];
				$default = $row['Default'];
				$key = $row['Key'];
				$extra = $row['Extra'];
				$isnull = $row['Null'];
				$type = $row['Type'];


				$column = array();

				$column['name'] = $name;
				//$column['type'] = $type;
				$column['defaultValue'] = $default;
				if(stristr($key, 'PRI')) $column['option'] = 'primary';
				if(stristr($isnull, 'YES')) {
					$column['notNull'] = false;
				} else {
					$column['notNull'] = true;
				}
				if($extra == 'auto_increment') $column['autoInc'] = true;


				// Type in Metatype umsetzen

				if(stristr($type, 'int(')) $metaType = 'int32';
				if(stristr($type, 'bigint')) $metaType = 'int64';
				if(stristr($type, 'char')) {
					$metaType = 'char';
					$tmp_typeValue = explode('(', $type);
					$column['typeValue'] = substr($tmp_typeValue[1], 0, -1);
				}
				if(stristr($type, 'varchar')) {
					$metaType = 'varchar';
					$tmp_typeValue = explode('(', $type);
					$column['typeValue'] = substr($tmp_typeValue[1], 0, -1);
				}
				if(stristr($type, 'text')) $metaType = 'text';
				if(stristr($type, 'double')) $metaType = 'double';
				if(stristr($type, 'blob')) $metaType = 'blob';


				$column['type'] = $metaType;

				$columns[] = $column;
			}
			return $columns;
		} else {
			return false;
		}

	}

	public function mapType($metaType, $typeValue) {
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
			if($typeValue < 1) die('Database failure: Lenght required for these data types.');
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

/**
 * database query result class
 *
 * @package pxFramework
 *
 */
class db_result {

	/**
	 *
	 *
	 * @access private
	 */
	private $_iResId = null;
	private $_iConnection = null;



	/**
	 *
	 *
	 * @access private
	 */
	public function __construct($iResId, $iConnection) {
		$this->_iResId = $iResId;
		$this->_iConnection = $iConnection;
	}



	/**
	 * get count of result rows
	 *
	 * Returns the amount of rows in the result set
	 *
	 * @access public
	 * @return int amount of rows
	 */
	public function rows() {
		if(!is_object($this->_iResId)) return 0;
		$iRows = mysqli_num_rows($this->_iResId);
		if(!$iRows) $iRows = 0;
		return $iRows;
	}



	/**
	 * Get number of affected rows
	 *
	 * Returns the amount of rows affected by the previous query
	 *
	 * @access public
	 * @return int amount of affected rows
	 */
	public function affected() {
		if(!is_object($this->_iConnection)) return 0;
		$iRows = mysqli_affected_rows($this->_iConnection);
		if(!$iRows) $iRows = 0;
		return $iRows;
	}



	/**
	 * Frees the result set
	 *
	 * @access public
	 */
	public function free() {
		if(!is_object($this->_iResId)) return;

		mysqli_free_result($this->_iResId);
		return;
	}



	/**
	 * Get a result row (associative)
	 *
	 * Returns the next row in the result set. To be used in a while loop like while($currow = $result->get()) { do something ... }
	 *
	 * @access public
	 * @return array result row
	 */
	public function get() {
		$aItem = null;

		if(is_object($this->_iResId)) {
			$aItem = mysqli_fetch_assoc($this->_iResId);
			if(!$aItem) $aItem = null;
		}
		return $aItem;
	}



	/**
	 * Get a result row (array with numeric index)
	 *
	 * @access public
	 * @return array result row
	 */
	public function getAsRow() {
		$aItem = null;

		if(is_object($this->_iResId)) {
			$aItem = mysqli_fetch_row($this->_iResId);
			if(!$aItem) $aItem = null;
		}
		return $aItem;
	}

}

/**
 * database query result class
 *
 * emulates a db result set out of an array so you can use array results and db results the same way
 *
 * @package pxFramework
 * @see db_result
 *
 *
 */
class fakedb_result {

	/**
	 *
	 *
	 * @access private
	 */
	private $aResultData = array();

	/**
	 *
	 *
	 * @access private
	 */
	private $aLimitedData = array();



	/**
	 *
	 *
	 * @access private
	 */
	public function __construct($aData) {
		$this->aResultData = $aData;
		$this->aLimitedData = $aData;
		reset($this->aLimitedData);
	}



	/**
	 * get count of result rows
	 *
	 * Returns the amount of rows in the result set
	 *
	 * @access public
	 * @return int amount of rows
	 */
	// Gibt die Anzahl Zeilen zurück
	public function rows() {
		return count($this->aLimitedData);
	}



	/**
	 * Frees the result set
	 *
	 * @access public
	 */
	// Gibt ein Ergebnisset frei
	public function free() {
		$this->aResultData = array();
		$this->aLimitedData = array();
		return;
	}



	/**
	 * Get a result row (associative)
	 *
	 * Returns the next row in the result set. To be used in a while loop like while($currow = $result->get()) { do something ... }
	 *
	 * @access public
	 * @return array result row
	 */
	// Gibt eine Ergebniszeile zurück
	public function get() {
		$aItem = null;

		if(!is_array($this->aLimitedData)) return $aItem;

		if(list($vKey, $aItem) = each($this->aLimitedData)) {
			if(!$aItem) $aItem = null;
		}
		return $aItem;
	}



	/**
	 * Get a result row (array with numeric index)
	 *
	 * @access public
	 * @return array result row
	 */
	public function getAsRow() {
		return $this->get();
	}



	/**
	 * Limit the result (like a LIMIT x,y in a SQL query)
	 *
	 * @access public
	 * @param int     $iStart offset to start read
	 * @param int     iLength amount of datasets to read
	 */
	public function limit_result($iStart, $iLength) {
		$this->aLimitedData = array_slice($this->aResultData, $iStart, $iLength, true);
	}

}


?>
