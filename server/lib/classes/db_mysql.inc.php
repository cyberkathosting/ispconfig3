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
		var $dbHost = '';		// hostname of the MySQL server
		var $dbName = '';		// logical database name on that server
		var $dbUser = '';		// database authorized user
		var $dbPass = '';		// user's password
		var $dbCharset = 'utf8';// Database charset
		var $dbNewLink = false; // Return a new linkID when connect is called again
		var $dbClientFlags = 0; // MySQL Client falgs
		var $linkId = 0;		// last result of mysql_connect()
		var $queryId = 0;		// last result of mysql_query()
		var $record	= array();	// last record fetched
		var $autoCommit = 1;    // Autocommit Transactions
		var $currentRow;		// current row number
		var $errorNumber = 0;	// last error number
		var $errorMessage = '';	// last error message
		var $errorLocation = '';// last error location
		var $show_error_messages = true;

		// constructor
		public function __construct() {
			
			global $conf;
			$this->dbHost = $conf['db_host'];
			$this->dbName = $conf['db_database'];
			$this->dbUser = $conf['db_user'];
			$this->dbPass = $conf['db_password'];
			$this->dbCharset = $conf['db_charset'];
			$this->dbNewLink = $conf['db_new_link'];
			$this->dbClientFlags = $conf['db_client_flags'];
			//$this->connect();
		}
		
		public function __destruct() {
			$this->closeConn();
		}

		// error handler
		function updateError($location)
		{
			global $app;
			$this->errorNumber = @mysql_errno($this->linkId);
			$this->errorMessage = @mysql_error($this->linkId);
			$this->errorLocation = $location;
			if($this->errorNumber && $this->show_error_messages && method_exists($app,'log'))
			{
				// echo('<br /><b>'.$this->errorLocation.'</b><br />'.$this->errorMessage);
				$app->log($this->errorLocation.' '.$this->errorMessage,LOGLEVEL_WARN);
				//flush();
			}
		}

		function connect()
		{
			if($this->linkId == 0)
			{
				$this->linkId = @mysql_connect($this->dbHost, $this->dbUser, $this->dbPass, $this->dbNewLink, $this->dbClientFlags);
				if(!$this->linkId)
				{
					$this->updateError('DB::connect()-> mysql_connect');
					return false;
				}
				$this->queryId = @mysql_query('SET NAMES '.$this->dbCharset, $this->linkId);
				$this->queryId = @mysql_query("SET character_set_results = '".$this->dbCharset."', character_set_client = '".$this->dbCharset."', character_set_connection = '".$this->dbCharset."', character_set_database = '".$this->dbCharset."', character_set_server = '".$this->dbCharset."'", $this->linkId);
			}
			return true;
		}

		function query($queryString)
		{
			if(!$this->connect())
			{
				return false;
			}
			if(!mysql_select_db($this->dbName, $this->linkId))
			{
				$this->updateError('DB::connect()-> mysql_select_db');
				return false;
			}
			$this->queryId = @mysql_query($queryString, $this->linkId);
			$this->updateError('DB::query('.$queryString.') -> mysql_query');
			if(!$this->queryId)
			{
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
			return $ret;
		}

		// returns one record in an array
		function queryOneRecord($queryString)
		{
			if(!$this->query($queryString) || $this->numRows() == 0)
			{
				return false;
			}
			return $this->nextRecord();
		}

		// returns the next record in an array
		function nextRecord()
		{
            $this->record = mysql_fetch_assoc($this->queryId);
			$this->updateError('DB::nextRecord()-> mysql_fetch_array');
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
			return mysql_num_rows($this->queryId);
		}
		
		// returns mySQL insert id
		function insertID()
		{
			return mysql_insert_id($this->linkId);
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
            if(!$this->connect()){
				$this->updateError('WARNING: mysql_connect: Used addslashes instead of mysql_real_escape_string');
				return addslashes($formfield);
			}
			
			return mysql_real_escape_string($formfield, $this->linkId);
        }
		
		// Check der variablen
        function unquote($formfield)
        {
            return stripslashes($formfield);
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
       
       /*
	   //* These functions are deprecated and will be removed.
       function insert($tablename,$form,$debug = 0)
       {
         if(is_array($form)){
	       foreach($form as $key => $value) 
   		    {
   		    $sql_key .= "$key, ";
            $sql_value .= "'".$this->check($value)."', ";
      		 }
       	$sql_key = substr($sql_key,0,strlen($sql_key) - 2);
        $sql_value = substr($sql_value,0,strlen($sql_value) - 2);
        
       	$sql = "INSERT INTO $tablename (" . $sql_key . ") VALUES (" . $sql_value .")";
       
      		 if($debug == 1) echo "SQL-Statement: ".$sql."<br><br>";
      		 $this->query($sql);
      		 if($debug == 1) echo "mySQL Error Message: ".$this->errorMessage;
          }
       }
       
       function update($tablename,$form,$bedingung,$debug = 0)
       {
       
	     if(is_array($form)){
           foreach($form as $key => $value) 
   		    {
   		    $insql .= "$key = '".$this->check($value)."', ";
      		 }
       	        $insql = substr($insql,0,strlen($insql) - 2);
       	        $sql = "UPDATE $tablename SET " . $insql . " WHERE $bedingung";
      		 if($debug == 1) echo "SQL-Statement: ".$sql."<br><br>";
      		 $this->query($sql);
      		 if($debug == 1) echo "mySQL Error Message: ".$this->errorMessage;
           }
       }
	   */
	   
	   public function diffrec($record_old, $record_new) {
		$diffrec_full = array();
		$diff_num = 0;

		if(is_array($record_old) && count($record_old) > 0) {
			foreach($record_old as $key => $val) {
				// if(!isset($record_new[$key]) || $record_new[$key] != $val) {
				if($record_new[$key] != $val) {
					// Record has changed
					$diffrec_full['old'][$key] = $val;
					$diffrec_full['new'][$key] = $record_new[$key];
					$diff_num++;
				} else {
					$diffrec_full['old'][$key] = $val;
					$diffrec_full['new'][$key] = $val;
				}
			}
		} elseif(is_array($record_new)) {
			foreach($record_new as $key => $val) {
				if(isset($record_new[$key]) && @$record_old[$key] != $val) {
					// Record has changed
					$diffrec_full['new'][$key] = $val;
					$diffrec_full['old'][$key] = @$record_old[$key];
					$diff_num++;
				} else {
					$diffrec_full['new'][$key] = $val;
					$diffrec_full['old'][$key] = $val;
				}
			}
		}
		
		return array('diff_num' => $diff_num, 'diff_rec' => $diffrec_full);
		
	}
	
	//** Function to fill the datalog with a full differential record.
	public function datalogSave($db_table, $action, $primary_field, $primary_id, $record_old, $record_new) {
		global $app,$conf;

		// Insert backticks only for incomplete table names.
		if(stristr($db_table,'.')) {
			$escape = '';
		} else {
			$escape = '`';
		}

		$tmp = $this->diffrec($record_old, $record_new);
		$diffrec_full = $tmp['diff_rec'];
		$diff_num = $tmp['diff_num'];
		unset($tmp);
		
		// Insert the server_id, if the record has a server_id
		$server_id = (isset($record_old['server_id']) && $record_old['server_id'] > 0)?$record_old['server_id']:0;
		if(isset($record_new['server_id'])) $server_id = $record_new['server_id'];
		

		if($diff_num > 0) {
			//print_r($diff_num);
			//print_r($diffrec_full);
			$diffstr = $app->db->quote(serialize($diffrec_full));
			$username = $app->db->quote($_SESSION['s']['user']['username']);
			$dbidx = $primary_field.':'.$primary_id;
						
			if($action == 'INSERT') $action = 'i';
			if($action == 'UPDATE') $action = 'u';
			if($action == 'DELETE') $action = 'd';
			$sql = "INSERT INTO sys_datalog (dbtable,dbidx,server_id,action,tstamp,user,data) VALUES ('".$db_table."','$dbidx','$server_id','$action','".time()."','$username','$diffstr')";
			$app->db->query($sql);
		}

		return true;
	}
	
	//** Inserts a record and saves the changes into the datalog
	public function datalogInsert($tablename, $insert_data, $index_field) {
		global $app;
		
		$old_rec = array();
		$this->query("INSERT INTO $tablename $insert_data");
		$index_value = $this->insertID();
		$new_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
		$this->datalogSave($tablename, 'INSERT', $index_field, $index_value, $old_rec, $new_rec);
		
		return $index_value;
	}
	
	//** Updates a record and saves the changes into the datalog
	public function datalogUpdate($tablename, $update_data, $index_field, $index_value) {
		global $app;
		
		$old_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
		$this->query("UPDATE $tablename SET $update_data WHERE $index_field = '$index_value'");
		$new_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
		$this->datalogSave($tablename, 'UPDATE', $index_field, $index_value, $old_rec, $new_rec);
		
		return true;
	}
	
	//** Deletes a record and saves the changes into the datalog
	public function datalogDelete($tablename, $index_field, $index_value) {
		global $app;
		
		$old_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
		$this->query("DELETE FROM $tablename WHERE $index_field = '$index_value'");
		$new_rec = array();
		$this->datalogSave($tablename, 'DELETE', $index_field, $index_value, $old_rec, $new_rec);
		
		return true;
	}

 
       public function closeConn()
    	{
    		if($this->linkId)
    		{
    			mysql_close($this->linkId);
    			return true;
    		} else { return false; }
    	}
       
    	public function freeResult($query) 
    	{
    		if(mysql_free_result($query))
    		{
    			return true;
    		} else {
    			return false;
    		}
    	}
       
       function delete() {
       
       }
       
       function Transaction($action) {
       //action = begin, commit oder rollback
       
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
       
       function createTable($table_name,$columns) {
       $index = '';
       $sql = "CREATE TABLE $table_name (";
       foreach($columns as $col){
            $sql .= $col['name'].' '.$this->mapType($col['type'],$col['typeValue']).' ';
       
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
       $sql = substr($sql,0,-1);
       $sql .= ')';
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
       function alterTable($table_name,$columns) {
       $index = '';
       $sql = "ALTER TABLE $table_name ";
       foreach($columns as $col){
            if($col['action'] == 'add') {
                $sql .= 'ADD '.$col['name'].' '.$this->mapType($col['type'],$col['typeValue']).' ';
            } elseif ($col['action'] == 'alter') {
                $sql .= 'CHANGE '.$col['name'].' '.$col['name_new'].' '.$this->mapType($col['type'],$col['typeValue']).' ';
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
       $sql = substr($sql,0,-1);
       
       //die($sql);
       $this->query($sql);
       return true;
       }
       
       function dropTable($table_name) {
       $this->check($table_name);
       $sql = "DROP TABLE '". $table_name."'";
       return $this->query($sql);
       }
       
       // gibt Array mit Tabellennamen zur�ck
       function getTables($database_name = '') {
	   	
			if($database_name == '') $database_name = $this->dbName;
            $result = mysql_list_tables($database_name);
            for ($i = 0; $i < mysql_num_rows($result); $i++) {
                $tb_names[$i] = mysql_tablename($result, $i);
            }
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
       
       function tableInfo($table_name) {
       
       global $go_api,$go_info;
       // Tabellenfelder einlesen
        
        if($rows = $go_api->db->queryAllRecords('SHOW FIELDS FROM '.$table_name)){
        foreach($rows as $row) {
            $name = $row[0];
            $default = $row[4];
            $key = $row[3];
            $extra = $row[5];
            $isnull = $row[2];
            $type = $row[1];
        
            
            $column = array();
        
            $column['name'] = $name;
            //$column['type'] = $type;
            $column['defaultValue'] = $default;
            if(stristr($key,'PRI')) $column['option'] = 'primary';
            if(stristr($isnull,'YES')) {
                $column['notNull'] = false;
            } else {
               $column['notNull'] = true; 
            }
            if($extra == 'auto_increment') $column['autoInc'] = true;
            
            
            // Type in Metatype umsetzen
            
            if(stristr($type,'int(')) $metaType = 'int32';
            if(stristr($type,'bigint')) $metaType = 'int64';
            if(stristr($type,'char')) {
                $metaType = 'char';
                $tmp_typeValue = explode('(',$type);
                $column['typeValue'] = substr($tmp_typeValue[1],0,-1);  
            }
            if(stristr($type,'varchar')) {
                $metaType = 'varchar';
                $tmp_typeValue = explode('(',$type);
                $column['typeValue'] = substr($tmp_typeValue[1],0,-1);  
            }
            if(stristr($type,'text')) $metaType = 'text';
            if(stristr($type,'double')) $metaType = 'double';
            if(stristr($type,'blob')) $metaType = 'blob';
            
            
            $column['type'] = $metaType;
            
        $columns[] = $column;
        }
            return $columns;
        } else {
            return false;
        }
        
        
        //$this->createTable('tester',$columns);
        
        /*
        $result = mysql_list_fields($go_info["server"]["db_name"],$table_name);
        $fields = mysql_num_fields ($result);
        $i = 0;
        $table = mysql_field_table ($result, $i);
        while ($i < $fields) {
            $name  = mysql_field_name  ($result, $i);
            $type  = mysql_field_type  ($result, $i);
            $len   = mysql_field_len   ($result, $i);
            $flags = mysql_field_flags ($result, $i);
            print_r($flags);
            
            $columns = array(name => $name,
                        type =>     "",
                        defaultValue =>  "",
                        isnull =>   1,
                        option =>   "");
            $returnvar[] = $columns;
            
            $i++;
        }
        */
        
        
       
       }
       
       function mapType($metaType,$typeValue) {
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

?>
