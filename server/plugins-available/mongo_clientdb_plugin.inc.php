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

/**
 * The MongoDB client plugin is used by ISPConfig to control the management of MongoDB.
 * If handles everything from creating DBs/Users, update them or delete them.
 */


class mongo_clientdb_plugin {

	/**
	 * ISPConfig internal identifiers.
	 */
	var $plugin_name = 'mongo_clientdb_plugin';
	var $class_name  = 'mongo_clientdb_plugin';


	/**
	 * This function is called during ISPConfig installation.
	 * It determines if a symlink shall be created for this plugin.
	 *
	 * @return bool true if symlink should be created
	 */
	function onInstall() {
		global $conf;
		
		/*if($conf['services']['db'] == true && class_exists('MongoClient')) {
			return true;
		} else {
			return false;
		}*/
		
		// Disable mongodb plugin in ISPConfig 3.1
		return false;
	}


	/**
	 * This function is called when the plugin is loaded.
	 * Each plugin/module needs to register itself to ISPConfig events from which
	 * it want to receive changes/get notified.
	 *
	 * Since this is a MongoDB plugin we are interested in DB changes and everything related
	 * to it, like users.
	 */
	function onLoad() {
		global $app;

		//* Databases
		$app->plugins->registerEvent('database_insert', $this->plugin_name, 'db_insert');
		$app->plugins->registerEvent('database_update', $this->plugin_name, 'db_update');
		$app->plugins->registerEvent('database_delete', $this->plugin_name, 'db_delete');

		//* Database users
		$app->plugins->registerEvent('database_user_insert', $this->plugin_name, 'db_user_insert');
		$app->plugins->registerEvent('database_user_update', $this->plugin_name, 'db_user_update');
		$app->plugins->registerEvent('database_user_delete', $this->plugin_name, 'db_user_delete');
	}


	/**
	 * MongoDB
	 * ------------------------------------------------------------------------
	 * The following needs to be done before using this plugin:
	 * - 1. install MongoDB server from 10gen sources (or another one with >= 2.4)
	 * - 2. install php5-dev package (apt-get install php5-dev)
	 * - 3. install mongo PECL extension (pecl install mongo)
	 * - 4. enable mongo (echo "extension=mongo.so" > /etc/php5/mods-available/mongo.ini && php5enmod mongo)
	 * - 5. create administrative user manager in Mongo (mongo -> use admin -> db.addUser({user: "root", pwd: "123456", roles: [ "userAdminAnyDatabase", "readWriteAnyDatabase", "dbAdminAnyDatabase", "clusterAdmin" ]}))
	 * - 6. enable auth for Mongo (nano /etc/mongodb.conf -> auth = true)
	 * - 7. restart MongoDB (service mongodb restart)
	 *
	 * Unlike MySQL, MongoDB manages users per database.
	 * Therefor we cannot use one user for multiple databases. Instead, we have to add him each time via the admin user.
	 */

	/**
	 * Stores the MongoDB connection.
	 * @var object
	 */
	private $_connection = null;

	/**
	 * Stores the MongoDB admin user.
	 * @var string
	 */
	const USER = "root";

	/**
	 * Stores the MongoDB admin password.
	 * @var string
	 */
	const PW = "123456";

	/**
	 * Stores the MongoDB host address.
	 * @var string
	 */
	const HOST = "127.0.0.1";

	/**
	 * Stores the MongoDB port.
	 * @var int
	 */
	const PORT = 27017;

	/**
	 * Adds the user to given database.
	 * If no connection exists, the user already exists or the database doesn't exist,
	 * null is returned.
	 *
	 * @param string $db the database to use
	 * @param array $user the user to add
	 * @return bool true if user added
	 */
	private function addUser($db, $user) {
		if ($this->isConnected() && !$this->userExists($db, $user)) {
			$roles = "";

			foreach ($user['roles'] as $index => $role) {
				$roles .= "\"".$role."\"";

				if ($index !== count($user['roles']) - 1) {
					$roles .= ", ";
				}
			}

			return $this->exec($db, "db.system.users.insert({ user: \"".$user['username']."\", pwd: \"".$user['password']."\", roles: [ ".$roles." ] })");
			//return $this->exec($db, "db.addUser({ user: \"".$user['username']."\", pwd: \"".$user['password']."\", roles: [ ".$roles." ] })");
		}

		return null;
	}

	/**
	 * Changes the users password in given DB.
	 * If no connection exists, the user doesn't exist or the DB doesn't exist,
	 * null is returned.
	 *
	 * @param string $db the database name
	 * @param string $user the user to change
	 * @param string $password the new password
	 * @return bool true if password changes
	 */
	private function changePassword($db, $user, $password) {
		if ($this->isConnected() && $this->dbExists($db) && $this->userExists($db, $user)) {
			$old_user = $this->getUser($db, $user);

			if ($this->dropUser($user, $db)) {
				return $this->addUser($db, array(
						'username' => $user,
						'password' => $password,
						'roles' => $old_user['roles']
					));
			}

			return false;
		}

		return null;
	}

	/**
	 * Connects to the server and authentificates.
	 * If the authentificaten goes wrong or another error encounters,
	 * false is returned.
	 * If we already have an open connection we try to disconnect and connect again.
	 * If this fails, false is returned.
	 *
	 * @return object $connection the MongoDB connection
	 */
	private function connect() {
		try {
			if ($this->isConnected() && !$this->disconnect()) {
				return false;
			}

			$this->_connection = new MongoClient("mongodb://".self::USER.":".self::PW."@".self::HOST.":".self::PORT."/admin");

			return $this->_connection;
		} catch (MongoConnnectionException $e) {
			$app->log('Unable to connect to MongoDB: '.$e, LOGLEVEL_ERROR);
			$this->_connection = null;

			return false;
		}
	}

	/**
	 * Checks if the database exists.
	 * If no connection exists,
	 * null is returned.
	 *
	 * @param string $db the database name
	 * @return bool true if exists
	 */
	private function dbExists($db) {
		if ($this->isConnected()) {
			return in_array($db, $this->getDBs());
		}

		return null;
	}

	/**
	 * Closes the MongoDB connection.
	 * If no connection exists and nothing is done,
	 * null is returned.
	 *
	 * @return bool true if closed
	 */
	private function disconnect() {
		if ($this->isConnected()) {
			$status = $this->_connection->close();

			if ($status) {
				$this->_connection = null;
			}

			return $status;
		}

		return null;
	}

	/**
	 * Drops the given database.
	 * If no connection exists or the database doesn't exist,
	 * null is returned.
	 *
	 * @param string $db the database's to drop name
	 */
	private function dropDB($db) {
		if ($this->isConnected() && $this->dbExists($db)) {
			return (bool) $this->_connection->dropDB($db)['ok'];
		}

		return null;
	}

	/**
	 * Drops the given user from database.
	 * If no DB is defined, the user is dropped from all databases.
	 * If there's an error when dropping the user from all DBs, an array containing the
	 * names of the failed DBs is returned.
	 * If no connection exists, the database doesn't exist or the user is not in DB,
	 * null is returned.
	 *
	 * @param string $user the user to drop
	 * @param string $db the database name
	 * @return bool true if dropped
	 */
	private function dropUser($user, $db = null) {
		if ($this->isConnected()) {
			if ($db !== null && $this->dbExists($db) && $this->userExists($db, $user)) {
				return $this->exec($db, "db.removeUser(\"".$user."\")");
			} else {
				$dbs = $this->getDBs();

				if ((bool) $dbs) {
					$failures = array();

					foreach ($dbs as $db) {
						$exists = $this->userExists($db, $user);

						if ($exists) {
							if (!$this->dropUser($user, $db)) {
								$failures[] = $db;
							}
						}
					}
				}

				return (bool) $failures ? $failures : true;
			}
		}

		return null;
	}

	/**
	 * Executed the command on the MongoDB server.
	 * If no connection exists and thus nothing can be done,
	 * null is returned.
	 *
	 * @param string $db the database to query
	 * @param string $query the command to execute
	 * @return array the result of the query
	 */
	private function exec($db, $query) {
		if ($this->isConnected()) {
			$db = $this->selectDB($db);
			$result = $db->execute($query);

			if ((bool) $result['ok']) {
				return $result;
			}

			return false;
		}

		return null;
	}

	/**
	 * Checks if the connection exists.
	 *
	 * @return true if connected
	 */
	private function isConnected() {
		return $this->_connection !== null;
	}

	/**
	 * Generates a MongoDB compatible password.
	 *
	 * @param string $user the username
	 * @param string $password the user password
	 * @return string the MD5 string
	 */
	private function generatePassword($user, $password) {
		return md5($user.":mongo:".$password);
	}

	/**
	 * Returns the databases found on connection.
	 * If no connection exists and therefor no DBs can be found,
	 * null is returned.
	 *
	 * @return array $names the databases's name
	 */
	private function getDBs() {
		if ($this->isConnected()) {
			$dbs = $this->_connection->listDBs();

			if ((bool) $dbs && isset($dbs['databases'])) {
				$names = array();

				foreach ($dbs['databases'] as $db) {
					$names[] = $db['name'];
				}

				return $names;
			}
		}

		return null;
	}

	/**
	 * Returns the user entry for given database.
	 * If no connection exists, the database doesn't exist or the user doesn't exist
	 * null is returned.
	 *
	 * @param string $db the database name
	 * @param string $user the user to return
	 * @return array $user the user in DB
	 */
	private function getUser($db, $user) {
		if ($this->isConnected() && $this->dbExists($db) && $this->userExists($db, $user)) {
			$result = $this->selectDB($db)->selectCollection("system.users")->find(array( 'user' => $user ));

			// ugly fix to return user
			foreach ($result as $user) {
				return $user;
			}
		}

		return null;
	}

	/**
	 * Returns the users for given database.
	 * If no connection exists or the database doesn't exist,
	 * null is returned.
	 *
	 * @param string $db the database name
	 * @return array $users the users in DB
	 */
	private function getUsers($db) {
		if ($this->isConnected() && $this->dbExists($db)) {
			$result = $this->selectDB($db)->selectCollection("system.users")->find();

			$users = array();

			foreach ($result as $record) {
				$users[] = $record['user'];
			}

			return $users;
		}

		return null;
	}

	/**
	 * Checks if the given user exists in given database.
	 * If no connection exists or the given database doesn't exist
	 * null is returned.
	 *
	 * @param string $db the database name
	 * @param string $user the user to check
	 * @return bool true if user exists
	 */
	private function userExists($db, $user) {
		if ($this->isConnected() && $this->dbExists($db)) {
			$users = $this->getUsers($db);

			return in_array($user, $users);
		}

		return null;
	}

	/**
	 * Renames the MongoDB database to provided name.
	 * If no connection exists, the source DB doesn't exist or the target DB already exists,
	 * null is returned.
	 *
	 * @param string $old_name the old database name
	 * @param string $new_name the new database name
	 * @return bool true if renamed
	 */
	private function renameDB($old_name, $new_name) {
		if ($this->isConnected() && $this->dbExists($old_name) && !$this->dbExists($new_name)) {
			if ($this->exec($old_name, "db.copyDatabase(\"".$old_name."\", \"".$new_name."\", \"".self::HOST."\", \"".self::USER."\", \"".self::PW."\")")) {
				$this->dropDB($old_name);

				return true;
			}

			return false;
		}

		return null;
	}

	/**
	 * Switched the selected database.
	 * MongoDB acts on a per-DB level (user management) and we always need to
	 * ensure we have the right DB selected.
	 * If no connection exists and thus nothing is done,
	 * null is returned.
	 *
	 * @param string $db the database to use
	 * @return object the MongoDB database object
	 */
	private function selectDB($db) {
		if ($this->isConnected()) {
			return $this->_connection->selectDB($db);
		}

		return null;
	}


	/**
	 * This function is called when a DB is created from within the ISPConfig3 interface.
	 * We need to create the DB and allow all users to connect to it that are choosen.
	 * Since MongoDB doesn't create a DB before any data is stored in it, it's important
	 * to store the users so it contains data -> is created.
	 *
	 * @param string $event_name the name of the event (insert, update, delete)
	 * @param array $data the event data (old and new)
	 * @return only if something is wrong
	 */
	function db_insert($event_name, $data) {
		global $app, $conf;

		// beside checking for MongoDB we also check if the DB is active because only then we add users
		// -> MongoDB needs users to create the DB
		if ($data['new']['type'] == 'mongo' && $data['new']['active'] == 'y') {
			if ($this->connect() === false) {
				$app->log("Unable to connect to MongoDB: Connecting using connect() failed.", LOGLEVEL_ERROR);
				return;
			}

			$db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password_mongo` FROM `web_database_user` WHERE `database_user_id` = ?", $data['new']['database_user_id']);
			$db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password_mongo` FROM `web_database_user` WHERE `database_user_id` = ?", $data['new']['database_ro_user_id']);

			$user = $db_user['database_user'];
			$password = $db_user['database_password_mongo'];

			$ro_user = $db_ro_user['database_user'];
			$ro_password = $db_ro_user['database_password_mongo'];

			$db = $data['new']['database_name'];

			if ((bool) $db_user) {
				if ($user == 'root') {
					$app->log("User root not allowed for client databases", LOGLEVEL_WARNING);
				} else {
					if (!$this->addUser($db, array(
								'username' => $user,
								'password' => $password,
								'roles' => array(
									"readWrite",
									"dbAdmin"
								)
							))) {
						$app->log("Error while adding user: ".$user." to DB: ".$db, LOGLEVEL_WARNING);
					}
				}
			}

			if ($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
				if ($user == 'root') {
					$app->log("User root not allowed for client databases", LOGLEVEL_WARNING);
				} else {
					if (!$this->addUser($db, array(
								'username' => $ro_user,
								'password' => $ro_password,
								'roles' => array(
									"read"
								)
							))) {
						$app->log("Error while adding read-only user: ".$user." to DB: ".$db, LOGLEVEL_WARNING);
					}
				}
			}

			$this->disconnect();
		}
	}


	/**
	 * This function is called when a DB is updated from within the ISPConfig interface.
	 * Updating the DB needs a lot of changes. First, we need to recheck all users that
	 * have permissions to access the DB. Maybe we also need to rename the DB and change
	 * it's type (MySQL, MongoDB etc.)...hard work here :)
	 *
	 * @param string $event_name the name of the event (insert, update, delete)
	 * @param array $data the event data (old and new)
	 * @return only if something is wrong
	 */
	function db_update($event_name, $data) {
		global $app, $conf;

		if ($data['old']['active'] == 'n' && $data['new']['active'] == 'n') {
			return;
		}

		// currently switching from MongoDB <-> MySQL isn't supported
		if ($data['old']['type'] == 'mongo' && $data['new']['type'] == 'mongo') {
			if ($this->connect() === false) {
				$app->log("Unable to connect to MongoDB: Connecting using connect() failed.", LOGLEVEL_ERROR);
				return;
			}

			$db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password_mongo` FROM `web_database_user` WHERE `database_user_id` = ?", $data['new']['database_user_id']);
			$db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password_mongo` FROM `web_database_user` WHERE `database_user_id` = ?", $data['new']['database_ro_user_id']);

			$user = $db_user['database_user'];
			$password = $db_user['database_password_mongo'];

			$ro_user = $db_ro_user['database_user'];
			$ro_password = $db_ro_user['database_password_mongo'];

			$db = $data['new']['database_name'];

			// create the database user if database was disabled before
			if ($data['new']['active'] == 'y' && $data['old']['active'] == 'n') {
				// since MongoDB creates DBs on-the-fly we can use the db_insert method which takes care of adding
				// users to a given DB
				$this->db_insert($event_name, $data);
			} else if ($data['new']['active'] == 'n' && $data['old']['active'] == 'y') {
					$users = $this->getUsers($db);

					if ((bool) $users) {
						foreach ($users as $user) {
							$this->dropUser($user, $db);
						}
					}
				} else {
				// selected user has changed -> drop old one
				if ($data['new']['database_user_id'] != $data['old']['database_user_id']) {
					$old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password_mongo` FROM `web_database_user` WHERE `database_user_id` = ?", $data['old']['database_user_id']);

					if ((bool) $old_db_user) {
						if ($old_db_user['database_user'] == 'root') {
							$app->log("User root not allowed for client databases", LOGLEVEL_WARNING);
						} else {
							$this->dropUser($old_db_user['database_user'], $db);
						}
					}
				}

				// selected read-only user has changed -> drop old one
				if ($data['new']['database_ro_user_id'] != $data['old']['database_ro_user_id']) {
					$old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password_mongo` FROM `web_database_user` WHERE `database_user_id` = ?", $data['old']['database_ro_user_id']);

					if ((bool) $old_db_user) {
						if ($old_db_user['database_user'] == 'root') {
							$app->log("User root not allowed for client databases", LOGLEVEL_WARNING);
						} else {
							$this->dropUser($old_db_user['database_user'], $db);
						}
					}
				}

				// selected user has changed -> add new one
				if ($data['new']['database_user_id'] != $data['old']['database_user_id']) {
					if ((bool) $db_user) {
						if ($user == 'root') {
							$app->log("User root not allowed for client databases", LOGLEVEL_WARNING);
						} else {
							$this->addUser($db, array(
									'username' => $user,
									'password' => $password,
									'roles' => array(
										"readWrite",
										"dbAdmin"
									)
								));
						}
					}
				}

				// selected read-only user has changed -> add new one
				if ($data['new']['database_ro_user_id'] != $data['old']['database_ro_user_iduser_id']) {
					if ((bool) $db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
						if ($ro_user == 'root') {
							$app->log("User root not allowed for client databases", LOGLEVEL_WARNING);
						} else {
							$this->addUser($db, array(
									'username' => $ro_user,
									'password' => $ro_password,
									'roles' => array(
										"read"
									)
								));
						}
					}
				}

				// renamed?
				/*
				if ($data['old']['database_name'] != $data['new']['database_name']) {
					$old_name = $data['old']['database_name'];
					$new_name = $data['new']['database_name'];

					if ($this->renameDB($oldName, $newName)) {
						$app->log("Renamed MongoDB database: ".$old_name." -> ".$new_name, LOGLEVEL_DEBUG);
					} else {
						$app->log("Renaming MongoDB database failed: ".$old_name." -> ".$new_name, LOGLEVEL_WARNING);
					}
				}
				*/
			}

			// switching from MySQL <-> Mongo isn't supported
			// no idea what we should do here...would be best to permit in interface?

			// remote access isn't supported by MongoDB (limiting to IP),
			// we therefor don't listen for it's changes
		}

		$this->disconnect();
	}


	/**
	 * This function is called when a DB is deleted from within the ISPConfig interface.
	 * All we need to do is to delete the database.
	 *
	 * @param string $event_name the name of the event (insert, update, delete)
	 * @param array $data the event data (old and new)
	 * @return only if something is wrong
	 */
	function db_delete($event_name, $data) {
		global $app, $conf;

		if ($data['old']['type'] == 'mongo') {
			if ($this->connect() === false) {
				$app->log("Unable to connect to MongoDB: Connecting using connect() failed.", LOGLEVEL_ERROR);
				return;
			}

			$db_to_drop = $data['old']['database_name'];

			if ($this->dropDB($db_to_drop)) {
				$app->log("Dropping MongoDB database: ".$db_to_drop, LOGLEVEL_DEBUG);
			} else {
				$app->log("Error while dropping MongoDB database: ".$db_to_drop, LOGLEVEL_WARNING);
			}

			$this->disconnect();
		}
	}


	/**
	 * This function is called when a user is inserted from within the ISPConfig interface.
	 * Since users are separated from databases we don't do anything here.
	 * As soon as an user is associated to a DB, we add him there.
	 *
	 * @param string $event_name the name of the event (insert, update, delete)
	 * @param array $data the event data (old and new)
	 */
	function db_user_insert($event_name, $data) {}


	/**
	 * This function is called when a user is updated from within the ISPConfig interface.
	 * The only thing we need to listen for here are password changes.
	 * We than need to change those in all databases the user uses.
	 *
	 * @param string $event_name the name of the event (insert, update, delete)
	 * @param array $data the event data (old and new)
	 * @return only if something is wrong
	 */
	function db_user_update($event_name, $data) {
		global $app, $conf;

		if ($data['old']['database_user'] == $data['new']['database_user']
			&& ($data['old']['database_password'] == $data['new']['database_password']
				|| $data['new']['database_password'] == '')) {
			return;
		}

		if ($this->connect() === false) {
			$app->log("Unable to connect to MongoDB: Connecting using connect() failed.", LOGLEVEL_ERROR);
			return;
		}

		if ($data['old']['database_user'] != $data['new']['database_user']) {
			// username has changed
			$dbs = $this->getDBs();

			if ((bool) $dbs) {
				foreach ($dbs as $db) {
					if ($this->userExists($db, $data['old']['database_user'])) {
						if (!$this->userExists($db, $data['new']['database_user'])) {
							$user = $this->getUser($db, $data['old']['database_user']);

							if ($this->dropUser($data['old']['database_user'], $db)) {
								if ($this->addUser($db, array(
											'username' => $data['new']['database_user'],
											'password' => md5($data['new']['database_password_mongo']),
											'roles' => $user['roles']
										))) {
									$app->log("Created user: ".$data['new']['database_user']." in DB: ".$db, LOGLEVEL_DEBUG);
								} else {
									$app->log("Couldn't create user: ".$data['new']['database_user']." in DB: ".$db, LOGLEVEL_WARNING);
								}
							} else {
								$app->log("Couldn't drop user: ".$data['old']['database_user']." in DB: ".$db, LOGLEVEL_WARNING);
							}
						} else {
							$app->log("User: ".$data['new']['database_user']." already exists in DB: ".$db, LOGLEVEL_WARNING);
						}
					}
				}
			}
		}

		if ($data['old']['database_password'] != $data['new']['database_password']
			|| $data['old']['database_user'] != $data['new']['database_user']) {
			// password only has changed
			$dbs = $this->getDBs();

			if ((bool) $dbs) {
				foreach ($dbs as $db) {
					if ($this->userExists($db, $data['new']['database_user'])) {
						if ($this->changePassword($db, $data['new']['database_user'], md5($data['new']['database_password_mongo']))) {
							$app->log("Changed user's: ".$data['new']['database_user']." password in DB: ".$db, LOGLEVEL_DEBUG);
						} else {
							$app->log("Couldn't change user's: ".$data['new']['database_user']." password in DB: ".$db, LOGLEVEL_WARNING);
						}
					}
				}
			}
		}

		$this->disconnect();
	}


	/**
	 * This function is called when a user is deleted from within the ISPConfig interface.
	 * Since MongoDB uses per-DB user management, we have to find every database where the user is
	 * activated and delete him there.
	 *
	 * @param string $event_name the name of the event (insert, update, delete)
	 * @param array $data the event data (old and new)
	 * @return only if something is wrong
	 */
	function db_user_delete($event_name, $data) {
		global $app, $conf;

		if ($this->connect() === false) {
			$app->log("Unable to connect to MongoDB: Connecting using connect() failed.", LOGLEVEL_ERROR);
			return;
		}

		if ($this->dropUser($data['old']['database_user']) === true) {
			$app->log("Dropped MongoDB user: ".$data['old']['database_user'], LOGLEVEL_DEBUG);
		} else {
			$app->log("Error while dropping MongoDB user: ".$data['old']['database_user'], LOGLEVEL_WARNING);
		}

		$this->disconnect();
	}

}
