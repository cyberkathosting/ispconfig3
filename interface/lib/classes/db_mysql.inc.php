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

class db extends mysqli
{
  private $dbHost = '';		// hostname of the MySQL server
  private $dbName = '';		// logical database name on that server
  private $dbUser = '';		// database authorized user
  private $dbPass = '';		// user's password
  private $dbCharset = 'utf8';// Database charset
  private $dbNewLink = false; // Return a new linkID when connect is called again
  private $dbClientFlags = 0; // MySQL Client falgs
  private $linkId = 0;		// last result of mysqli_connect()
  private $queryId = 0;		// last result of mysqli_query()
  private $record	= array();	// last record fetched
  private $autoCommit = 1;    // Autocommit Transactions
  private $currentRow;		// current row number
  private $errorNumber = 0;	// last error number
  public $errorMessage = '';	// last error message
  private $errorLocation = '';// last error location
  public $show_error_messages = true; // false in server, true in interface

  // constructor
  public function __construct($prefix = '') {
    global $conf;
    if($prefix != '') $prefix .= '_';
    $this->dbHost = $conf[$prefix.'db_host'];
    $this->dbName = $conf[$prefix.'db_database'];
    $this->dbUser = $conf[$prefix.'db_user'];
    $this->dbPass = $conf[$prefix.'db_password'];
    $this->dbCharset = $conf[$prefix.'db_charset'];
    $this->dbNewLink = $conf[$prefix.'db_new_link'];
    $this->dbClientFlags = $conf[$prefix.'db_client_flags'];
    parent::__construct($conf[$prefix.'db_host'], $conf[$prefix.'db_user'],$conf[$prefix.'db_password'],$conf[$prefix.'db_database']);
    if ($this->connect_error) {
      $this->updateError('DB::__construct');
      return false;
    }
    parent::query( 'SET NAMES '.$this->dbCharset); 
    parent::query( "SET character_set_results = '".$this->dbCharset."', character_set_client = '".$this->dbCharset."', character_set_connection = '".$this->dbCharset."', character_set_database = '".$this->dbCharset."', character_set_server = '".$this->dbCharset."'");

  }

  public function __destruct() {
    $this->close(); // helps avoid memory leaks, and persitent connections that don't go away.
  }

  // error handler
  public function updateError($location) {
    global $app;

    if($this->connect_error) {
      $this->errorNumber = $this->connect_errno;
      $this->errorMessage = $this->connect_error;
    } else {
      $this->errorNumber = $this->errno;
      $this->errorMessage = $this->error;
    }

    $this->errorLocation = $location;
    if($this->errorNumber) {
      $error_msg = $this->errorLocation .' '. $this->errorMessage;
      // This right here will allow us to use the samefile for server & interface
      if($this->show_error_messages) {
	echo $error_msg;
      } else if(method_exists($app, 'log')) {
	$app->log($error_msg, LOGLEVEL_WARN);
      }
    }
  }

  public function query($queryString) {
    parent::ping();
	$this->queryId = parent::query($queryString);
    $this->updateError('DB::query('.$queryString.') -> mysqli_query');
    if($this->errorNumber) debug_print_backtrace();
    if(!$this->queryId) {
      return false;
    }
    $this->currentRow = 0;
    return $this->queryId;
  }

  // returns all records in an array
  public function queryAllRecords($queryString) {
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
  public function queryOneRecord($queryString) {
    if(!$this->query($queryString) || $this->numRows() == 0)
    {
      return false;
    }
    return $this->nextRecord();
  }

  // returns the next record in an array
  public function nextRecord() {
    $this->record = $this->queryId->fetch_assoc();
    $this->updateError('DB::nextRecord()-> mysql_fetch_array');
    if(!$this->record || !is_array($this->record))
    {
      return false;
    }
    $this->currentRow++;
    return $this->record;
  }

  // returns number of rows returned by the last select query
  public function numRows() {
    return $this->queryId->num_rows;
  }
  
  public function affectedRows() {
	return $this->queryId->affected_rows;
  }

  // returns mySQL insert id
  public function insertID() {
    return $this->insert_id;
  }


  //* Function to quote strings
  public function quote($formfield) {
    return $this->escape_string($formfield);
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
    public function datalogSave($db_table, $action, $primary_field, $primary_id, $record_old, $record_new, $force_update = false) {
      global $app,$conf;

      // Insert backticks only for incomplete table names.
      if(stristr($db_table,'.')) {
	$escape = '';
      } else {
	$escape = '`';
      }

		if($force_update == true) {
			//* We force a update even if no record has changed
			$diffrec_full = array('new' => $record_new,'old' => $record_old);
			$diff_num = count($record_new);
		} else {
			//* get the difference record between old and new record
			$tmp = $this->diffrec($record_old, $record_new);
			$diffrec_full = $tmp['diff_rec'];
			$diff_num = $tmp['diff_num'];
			unset($tmp);
		}

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
	  
	  if(is_array($insert_data)) {
			$key_str = '';
			$val_str = '';
			foreach($insert_data as $key => $val) {
				$key_str .= "`".$key ."`,";
				$val_str .= "'".$this->quote($val)."',";
			}
			$key_str = substr($key_str,0,-1);
			$val_str = substr($val_str,0,-1);
			$insert_data_str = '('.$key_str.') VALUES ('.$val_str.')';
		} else {
			$insert_data_str = $insert_data;
		}

      $old_rec = array();
      $this->query("INSERT INTO $tablename $insert_data_str");
      $index_value = $this->insertID();
      $new_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
      $this->datalogSave($tablename, 'INSERT', $index_field, $index_value, $old_rec, $new_rec);

      return $index_value;
    }

    //** Updates a record and saves the changes into the datalog
    public function datalogUpdate($tablename, $update_data, $index_field, $index_value, $force_update = false) {
		global $app;
      
	  $old_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
	  
	  if(is_array($update_data)) {
			$update_data_str = '';
			foreach($update_data as $key => $val) {
				$update_data_str .= "`".$key ."` = '".$this->quote($val)."',";
			}
			$update_data_str = substr($update_data_str,0,-1);
		} else {
			$update_data_str = $update_data;
		}
		
      $this->query("UPDATE $tablename SET $update_data_str WHERE $index_field = '$index_value'");
      $new_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
      $this->datalogSave($tablename, 'UPDATE', $index_field, $index_value, $old_rec, $new_rec, $force_update);

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
    
    //* get the current datalog status for the specified login (or currently logged in user)
    public function datalogStatus($login = '') {
        global $app;
        
        $return = array('count' => 0, 'entries' => array());
        if($_SESSION['s']['user']['typ'] == 'admin') return $return; // these information should not be displayed to admin users
        
        if($login == '' && isset($_SESSION['s']['user'])) {
            $login = $_SESSION['s']['user']['username'];
        }
        
        $result = $this->queryAllRecords("SELECT COUNT( * ) AS cnt, sys_datalog.action, sys_datalog.dbtable FROM sys_datalog, server WHERE server.server_id = sys_datalog.server_id AND sys_datalog.user = '" . $this->quote($login) . "' AND sys_datalog.datalog_id > server.updated GROUP BY sys_datalog.dbtable, sys_datalog.action");
        foreach($result as $row) {
            if(!$row['dbtable'] || in_array($row['dbtable'], array('aps_instances', 'aps_instances_settings', 'mail_access', 'mail_content_filter'))) continue; // ignore some entries, maybe more to come
            $return['entries'][] = array('table' => $row['dbtable'], 'action' => $row['action'], 'count' => $row['cnt'], 'text' => $app->lng('datalog_status_' . $row['action'] . '_' . $row['dbtable']));
            $return['count'] += $row['cnt'];
        }
        unset($result);
        
        return $return;
    }


    public function freeResult($query) 
    {
      if(is_object($query) && (get_class($query) == "mysqli_result")) {
	$query->free();
	return true;
      } else {
	return false;
      }
    }

    /* TODO: Does anything use this? */
    public function delete() {

    }

    /* TODO: Does anything use this? */
    public function Transaction($action) {
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

    public function createTable($table_name,$columns) {
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
    public function alterTable($table_name,$columns) {
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

    public function dropTable($table_name) {
      $this->check($table_name);
      $sql = "DROP TABLE '". $table_name."'";
      return $this->query($sql);
    }

    // gibt Array mit Tabellennamen zur�ck
    public function getTables($database_name = '') {

      if($database_name == '') $database_name = $this->dbName;
      $result = parent::query("SHOW TABLES FROM $database_name");
      for ($i = 0; $i < $result->num_rows; $i++) {
	$tb_names[$i] = (($result->data_seek( $i) && (($___mysqli_tmp = $result->fetch_row()) !== NULL)) ? array_shift($___mysqli_tmp) : false);
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

      global $go_api,$go_info,$app;
      // Tabellenfelder einlesen

      if($rows = $app->db->queryAllRecords('SHOW FIELDS FROM '.$table_name)){
	foreach($rows as $row) {
	  /*
	  $name = $row[0];
	  $default = $row[4];
	  $key = $row[3];
	  $extra = $row[5];
	  $isnull = $row[2];
	  $type = $row[1];
	  */
	  
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

    public function mapType($metaType,$typeValue) {
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