<?php
/*
Copyright (c) 2012, ISPConfig UG
Contributors: web wack creations,  http://www.web-wack.at
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
require_once 'aps_base.inc.php';

@set_time_limit(0);
@ignore_user_abort(1);

class ApsInstaller extends ApsBase
{
	private $handle_type = '';
	private $domain = '';
	private $document_root = '';
	private $sublocation = '';
	private $local_installpath = '';
	private $dbhost = '';
	private $newdb_name = '';
	private $newdb_user = '';
	private $file_owner_user = '';
	private $file_owner_group = '';
	private $putenv = array();

	/**
	 * Constructor
	 *
	 * @param $app the application instance (db handle + log method)
	 * @param $interface_mode act in interface (true) or server mode (false)
	 */


	public function __construct($app, $interface_mode = false)
	{
		parent::__construct($app, 'APS installer: ', $interface_mode);
	}



	/**
	 * Before the cron is executed, make sure all necessary options are set
	 * and all functions are available
	 */
	private function checkRequirements()
	{
		global $app;
		try
		{
			// Check if exec() is not disabled
			$disabled_func = explode(',', @ini_get('disable_functions'));
			if(in_array('exec', $disabled_func)) throw new Exception('the call of exec() is disabled');

			// Check if safe_mode is disabled (needed for correct putenv, chmod, chown handling)
			if(@ini_get('safe_mode')) throw new Exception('the safe_mode restriction is on');

			return true;
		}

		catch(Exception $e)
		{
			$app->log('Aborting execution because '.$e->getMessage(), 1);
			return false;
		}
	}



	/**
	 * Get a file from a ZIP archive and either return it's content or
	 * extract it to a given destination
	 *
	 * @param $zipfile the ZIP file to work with
	 * @param $subfile the file from which to get the content
	 * @param $destfolder the optional extraction destination
	 * @param $destname the optional target file name when extracting
	 * @return string or boolean
	 */
	private function getContentFromZIP($zipfile, $subfile, $destfolder = '', $destname = '')
	{
		try
		{
			$zip = new ZipArchive;
			$res = $zip->open(realpath($zipfile));
			if(!$res) throw new Exception('Cannot open ZIP file '.$zipfile);

			// If no destination is given, the content is returned, otherwise
			// the $subfile is extracted to $destination
			if($destfolder == '')
			{
				$fh = $zip->getStream($subfile);
				if(!$fh) throw new Exception('Cannot read '.$subfile.' from '.$zipfile);

				$subfile_content = '';
				while(!feof($fh)) $subfile_content .= fread($fh, 8192);

				fclose($fh);

				return $subfile_content;
			}
			else
			{
				// extractTo would be suitable but has no target name parameter
				//$ind = $zip->locateName($subfile);
				//$ex = $zip->extractTo($destination, array($zip->getNameIndex($ind)));
				if($destname == '') $destname = basename($subfile);
				$ex = @copy('zip://'.$zipfile.'#'.$subfile, $destfolder.$destname);
				if(!$ex) throw new Exception('Cannot extract '.$subfile.' to '.$destfolder);
			}

			$zip->close();

		}

		catch(Exception $e)
		{
			// The exception message is only interesting for debugging reasons
			// echo $e->getMessage();
			return false;
		}
	}



	/**
	 * Extract the complete directory of a ZIP file
	 *
	 * @param $filename the file to unzip
	 * @param $directory the ZIP inside directory to unzip
	 * @param $destination the place where to extract the data
	 * @return boolean
	 */
	private function extractZip($filename, $directory, $destination)
	{
		if(!file_exists($filename)) return false;

		// Fix the paths
		if(substr($directory, -1) == '/') $directory = substr($directory, 0, strlen($directory) - 1);
		if(substr($destination, -1) != '/') $destination .= '/';

		// Read and extract the ZIP file
		$ziphandle = zip_open(realpath($filename));
		if(is_resource($ziphandle))
		{
			while($entry = zip_read($ziphandle))
			{
				if(substr(zip_entry_name($entry), 0, strlen($directory)) == $directory)
				{
					// Modify the relative ZIP file path
					$new_path = substr(zip_entry_name($entry), strlen($directory));

					if(substr($new_path, -1) == '/') // Identifier for directories
						{
						if(!file_exists($destination.$new_path)) mkdir($destination.$new_path, 0777, true);
					}
					else // Handle files
						{
						if(zip_entry_open($ziphandle, $entry))
						{
							$new_dir = dirname($destination.$new_path);
							if(!file_exists($new_dir)) mkdir($new_dir, 0777, true);

							$file = fopen($destination.$new_path, 'wb');
							if($file)
							{
								while($line = zip_entry_read($entry)) fwrite($file, $line);
								fclose($file);
							}
							else return false;
						}
					}
				}
			}

			zip_close($ziphandle);
			return true;
		}

		return false;
	}

	/**
	 * Setup the path environment variables for the install script
	 *
	 * @param $parent_mapping the SimpleXML instance with the current mapping position
	 * @param $url the relative path within the mapping tree
	 * @param $path the absolute path within the mapping tree
	 */
	private function processMappings($parent_mapping, $url, $path)
	{
		if($parent_mapping && $parent_mapping != null)
		{
			$writable = parent::getXPathValue($parent_mapping, 'php:permissions/@writable');
			$readable = parent::getXPathValue($parent_mapping, 'php:permissions/@readable');

			// set the write permission
			if($writable == 'true')
			{
				if(is_dir($path)) chmod($path, 0775);
				else chmod($path, 0664);
			}

			// set non-readable permission
			if($readable == 'false')
			{
				if(is_dir($path)) chmod($path, 0333);
				else chmod($path, 0222);
			}
		}

		// Set the environment variables
		$env = str_replace('/', '_', $url);
		$this->putenv[] = 'WEB_'.$env.'_DIR='.$path;

		// Step recursively into further mappings
		if($parent_mapping && $parent_mapping != null)
		{
			foreach($parent_mapping->mapping as $mapping)
			{
				if($url == '/') $this->processMappings($mapping, $url.$mapping['url'], $path.$mapping['url']);
				else $this->processMappings($mapping, $url.'/'.$mapping['url'], $path.'/'.$mapping['url']);
			}
		}
	}



	/**
	 * Setup the environment with data for the install location
	 *
	 * @param $task an array containing all install related data
	 */
	private function prepareLocation($task)
	{
		global $app;

		// Get the domain name to use for the installation
		// Would be possible in one query too, but we use 2 for easier debugging
		$main_domain = $app->db->queryOneRecord("SELECT value FROM aps_instances_settings WHERE name = 'main_domain' AND instance_id = ?", $task['instance_id']);
		$this->domain = $main_domain['value'];

		// Get the document root
		$domain_res = $app->db->queryOneRecord("SELECT document_root, web_folder, type FROM web_domain WHERE domain = ?", $this->domain);
		$this->document_root = $domain_res['document_root'];

		// Get the sub location
		$location_res = $app->db->queryOneRecord("SELECT value FROM aps_instances_settings WHERE name = 'main_location' AND instance_id = ?", $task['instance_id']);
		$this->sublocation = $location_res['value'];

		// Make sure the document_root ends with /
		if(substr($this->document_root, -1) != '/') $this->document_root .= '/';

		// Attention: ISPConfig Special: web files are in subfolder 'web' -> append it:
        if(($domain_res['type'] == 'vhostsubdomain') || ($domain_res['type'] == 'vhostalias'))
        	$this->document_root .= $domain_res['web_folder'] . '/';
		else $this->document_root .= 'web/';

		// If a subfolder is given, make sure it's path doesn't begin with / i.e. /phpbb
		if(substr($this->sublocation, 0, 1) == '/') $this->sublocation = substr($this->sublocation, 1);

		// If the package isn't installed to a subfolder, remove the / at the end of the document root
		if(empty($this->sublocation)) $this->document_root = substr($this->document_root, 0, strlen($this->document_root) - 1);

		// Set environment variables, later processed by the package install script
		$this->putenv[] = 'BASE_URL_SCHEME=http';
		// putenv('BASE_URL_PORT') -> omitted as it's 80 by default
		$this->putenv[] = 'BASE_URL_HOST='.$this->domain;
		$this->putenv[] = 'BASE_URL_PATH='.$this->sublocation.'/';
	}



	/**
	 * Setup a database (if needed) and the appropriate environment variables
	 *
	 * @param $task an array containing all install related data
	 * @param $sxe a SimpleXMLElement handle, holding APP-META.xml
	 */
	private function prepareDatabase($task, $sxe)
	{
		global $app;

		$db_id = parent::getXPathValue($sxe, '//db:id');
		if(empty($db_id)) return; // No database needed

		$mysqlver_res = $app->db->queryOneRecord('SELECT VERSION() as ver');
		$mysqlver = $mysqlver_res['ver'];

		$tmp = $app->db->queryOneRecord("SELECT value FROM aps_instances_settings WHERE name = 'main_database_password' AND instance_id = ?", $task['instance_id']);
		$newdb_pw = $tmp['value'];

		$tmp = $app->db->queryOneRecord("SELECT value FROM aps_instances_settings WHERE name = 'main_database_host' AND instance_id = ?", $task['instance_id']);
		$newdb_host = $tmp['value'];

		$tmp = $app->db->queryOneRecord("SELECT value FROM aps_instances_settings WHERE name = 'main_database_name' AND instance_id = ?", $task['instance_id']);
		$newdb_name = $tmp['value'];

		$tmp = $app->db->queryOneRecord("SELECT value FROM aps_instances_settings WHERE name = 'main_database_login' AND instance_id = ?", $task['instance_id']);
		$newdb_login = $tmp['value'];
		
		/* Test if the new mysql connection is laready working to ensure that db servers in multiserver
		   setups get enough time to create the database */
		if($this->handle_type == 'install') {
			for($n = 1; $n < 15; $n++) {
				$link = mysqli_connect($newdb_host, $newdb_login, $newdb_pw);
				if (!$link) {
					unset($link);
					sleep(5);
				} else {
					unset($link);
					break;
				}
			}
		}

		$this->putenv[] = 'DB_'.$db_id.'_TYPE=mysql';
		$this->putenv[] = 'DB_'.$db_id.'_NAME='.$newdb_name;
		$this->putenv[] = 'DB_'.$db_id.'_LOGIN='.$newdb_login;
		$this->putenv[] = 'DB_'.$db_id.'_PASSWORD='.$newdb_pw;
		$this->putenv[] = 'DB_'.$db_id.'_HOST='.$newdb_host;
		$this->putenv[] = 'DB_'.$db_id.'_PORT=3306';
		$this->putenv[] = 'DB_'.$db_id.'_VERSION='.$mysqlver;
	}



	/**
	 * Extract all needed files from the package
	 *
	 * @param $task an array containing all install related data
	 * @param $sxe a SimpleXMLElement handle, holding APP-META.xml
	 * @return boolean
	 */
	private function prepareFiles($task, $sxe)
	{
		global $app;

		// Basically set the mapping for APS version 1.0, if not available -> newer way
		$mapping = $sxe->mapping;
		$mapping_path = $sxe->mapping['path'];
		$mapping_url = $sxe->mapping['url'];
		if(empty($mapping))
		{
			$mapping = $sxe->service->provision->{'url-mapping'}->mapping;
			$mapping_path = $sxe->service->provision->{'url-mapping'}->mapping['path'];
			$mapping_url = $sxe->service->provision->{'url-mapping'}->mapping['url'];
		}

		try
		{
			// Make sure we have a valid mapping path (at least /)
			if(empty($mapping_path)) throw new Exception('Unable to determine a mapping path');

			$this->local_installpath = $this->document_root.$this->sublocation.'/';

			// Now delete an existing folder (affects install and removal in the same way)
			@chdir($this->local_installpath);
			if(file_exists($this->local_installpath)){
				// make sure we don't delete error and stats folders
				if($this->local_installpath == $this->document_root.'/'){
					if(is_dir($this->document_root)){
						$files = array_diff(scandir($this->document_root), array('.', '..', 'error', 'stats'));
						foreach($files as $file){
							if(is_dir($this->document_root.'/'.$file)){
								$app->file->removeDirectory($this->document_root.'/'.$file);
							} else {
								@unlink($this->document_root.'/'.$file);
							}
						}
					} else {
						@unlink($this->document_root);
						mkdir($this->document_root, 0777, true);
					}
				} else {
					$app->system->exec_safe("rm -Rf ?*", $this->local_installpath);
				}
			} else {
				mkdir($this->local_installpath, 0777, true);
			}

			if($this->handle_type == 'install')
			{
				// Now check if the needed folder is there
				if(!file_exists($this->local_installpath))
					throw new Exception('Unable to create a new folder for the package '.$task['path']);

				// Extract all files and assign them a new owner
				if( ($this->extractZip($this->packages_dir.'/'.$task['path'], $mapping_path, $this->local_installpath) === false)
					|| ($this->extractZip($this->packages_dir.'/'.$task['path'], 'scripts', $this->local_installpath.'install_scripts/') === false) )
				{
					// Clean already extracted data
					$app->system->exec_safe("rm -Rf ?*", $this->local_installpath);
					throw new Exception('Unable to extract the package '.$task['path']);
				}

				$this->processMappings($mapping, $mapping_url, $this->local_installpath);

				// Set the appropriate file owner
				$main_domain = $app->db->queryOneRecord("SELECT value FROM aps_instances_settings WHERE name = 'main_domain' AND instance_id = ?", $task['instance_id']);
				$owner_res = $app->db->queryOneRecord("SELECT system_user, system_group FROM web_domain WHERE domain = ?", $main_domain['value']);
				$this->file_owner_user = $owner_res['system_user'];
				$this->file_owner_group = $owner_res['system_group'];
				$app->system->exec_safe('chown -R ?:? ?', $this->file_owner_user, $this->file_owner_group, $this->local_installpath);

				//* Chown stats directory back
				if(is_dir($this->local_installpath.'stats')) {
					$app->system->exec_safe('chown -R root:root ?', $this->local_installpath.'stats');
				}
			}
		}
		catch(Exception $e)
		{
			$app->dbmaster->query('UPDATE aps_instances SET instance_status = ? WHERE id = ?', INSTANCE_ERROR, $task['instance_id']);
			$app->log($e->getMessage(), 1);
			return false;
		}

		return true;
	}



	/**
	 * Get all user config variables and set them to environment variables
	 *
	 * @param $task an array containing all install related data
	 */
	private function prepareUserInputData($task)
	{
		global $app;

		$userdata = $app->db->queryAllRecords("SELECT name, value FROM aps_instances_settings WHERE instance_id = ?", $task['instance_id']);
		if(empty($userdata)) return false;

		foreach($userdata as $data)
		{
			// Skip unnecessary data
			if($data['name'] == 'main_location'
				|| $data['name'] == 'main_domain'
				|| $data['name'] == 'main_database_password'
				|| $data['name'] == 'main_database_name'
				|| $data['name'] == 'main_database_host'
				|| $data['name'] == 'main_database_login'
				|| $data['name'] == 'license') continue;

			$this->putenv[] = 'SETTINGS_'.$data['name'].'='.$data['value'];
		}
	}



	/**
	 * Fetch binary data from a given array
	 * The data is retrieved in binary mode and
	 * then directly written to an output file
	 *
	 * @param $input a specially structed array
	 * @see $this->startUpdate()
	 */
	private function fetchFiles($input)
	{
		$fh = array();
		$url = array();
		$conn = array();

		// Build the single cURL handles and add them to a multi handle
		$mh = curl_multi_init();

		// Process each app
		for($i = 0; $i < count($input); $i++)
		{
			$conn[$i] = curl_init($input[$i]['url']);
			$fh[$i] = fopen($input[$i]['localtarget'], 'wb');

			curl_setopt($conn[$i], CURLOPT_BINARYTRANSFER, true);
			curl_setopt($conn[$i], CURLOPT_FILE, $fh[$i]);
			curl_setopt($conn[$i], CURLOPT_TIMEOUT, 0);
			curl_setopt($conn[$i], CURLOPT_FAILONERROR, 1);
			curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($conn[$i], CURLOPT_SSL_VERIFYPEER, 0);

			curl_multi_add_handle($mh, $conn[$i]);
		}

		$active = 0;
		do curl_multi_exec($mh, $active);
		while($active > 0);

		// Close the handles
		for($i = 0; $i < count($input); $i++)
		{
			fclose($fh[$i]);
			curl_multi_remove_handle($mh, $conn[$i]);
			curl_close($conn[$i]);
		}
		curl_multi_close($mh);
	}



	/**
	 * The installation script should be executed
	 *
	 * @param $task an array containing all install related data
	 * @param $sxe a SimpleXMLElement handle, holding APP-META.xml
	 * @return boolean
	 */
	private function doInstallation($task, $sxe)
	{
		global $app;

		try
		{
			// Check if the install directory exists
			if(!is_dir($this->local_installpath.'install_scripts/'))
				throw new Exception('The install directory '.$this->local_installpath.' is not existing');

			// Set the executable bit to the configure script
			$cfgscript = @(string)$sxe->service->provision->{'configuration-script'}['name'];
			if(!$cfgscript) $cfgscript = 'configure';
			chmod($this->local_installpath.'install_scripts/'.$cfgscript, 0755);

			// Change to the install folder (import for the exec() below!)
			chdir($this->local_installpath.'install_scripts/');

			// Set the enviroment variables
			foreach($this->putenv as $var) {
				putenv($var);
			}

			$shell_retcode = true;
			$shell_ret = array();
			$app->system->exec_safe('php ? install 2>&1', $this->local_installpath.'install_scripts/'.$cfgscript);
			$shell_ret = $app->system->last_exec_out();
			$shell_retcode = $app->system->last_exec_retcode();
			$shell_ret = array_filter($shell_ret);
			$shell_ret_str = implode("\n", $shell_ret);

			// Although $shell_retcode might be 0, there can be PHP errors. Filter them:
			if(substr_count($shell_ret_str, 'Warning: ') > 0) $shell_retcode = 1;

			// If an error has occurred, the return code is != 0
			if($shell_retcode != 0) throw new Exception($shell_ret_str);
			else
			{
				// The install succeeded, chown newly created files too
				$app->system->exec_safe('chown -R ?:? ?', $this->file_owner_user, $this->file_owner_group, $this->local_installpath);

				//* Chown stats directory back
				if(is_dir($this->local_installpath.'stats')) {
					$app->system->exec_safe('chown -R root:root ?', $this->local_installpath.'stats');
				}

				$app->dbmaster->query('UPDATE aps_instances SET instance_status = ? WHERE id = ?', INSTANCE_SUCCESS, $task['instance_id']);
			}
		}

		catch(Exception $e)
		{
			$app->dbmaster->query('UPDATE aps_instances SET instance_status = ? WHERE id = ?', INSTANCE_ERROR, $task['instance_id']);
			$app->log($e->getMessage(), 1);
			return false;
		}

		return true;
	}



	/**
	 * Cleanup: Remove install scripts, remove tasks and update the database
	 *
	 * @param $task an array containing all install related data
	 * @param $sxe a SimpleXMLElement handle, holding APP-META.xml
	 */
	private function cleanup($task, $sxe)
	{
		global $app;
		chdir($this->local_installpath);
		$app->system->exec_safe("rm -Rf ?", $this->local_installpath.'install_scripts');
	}



	/**
	 * The main method which performs the actual package installation
	 *
	 * @param $instanceid the instanceID to install
	 * @param $type the type of task to perform (installation, removal)
	 */
	public function installHandler($instanceid, $type)
	{
		global $app;

		// Set the given handle type, currently supported: install, delete
		if($type == 'install' || $type == 'delete') $this->handle_type = $type;
		else return false;

		// Get all instance metadata
		$task = $app->db->queryOneRecord("SELECT * FROM aps_instances AS i INNER JOIN aps_packages AS p ON i.package_id = p.id WHERE i.id = ?", $instanceid);
		if(!$task) return false;  // formerly: throw new Exception('The InstanceID doesn\'t exist.');
		if(!isset($task['instance_id'])) $task['instance_id'] = $instanceid;

		// Download aps package
		if(!file_exists($this->packages_dir.'/'.$task['path']) || filesize($this->packages_dir.'/'.$task['path']) == 0) {
			$ch = curl_init();
			$fh = fopen($this->packages_dir.'/'.$task['path'], 'wb');
			curl_setopt($ch, CURLOPT_FILE, $fh);
			//curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_URL, $task['package_url']);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 0);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			if(curl_exec($ch) === false) $app->log(curl_error($ch), 1);
			fclose($fh);
			curl_close($ch);
		}

		/*
		$app_to_dl[] = array('name' => $task['path'],
                            'url' => $task['package_url'],
                            'filesize' => 0,
                            'localtarget' => $this->packages_dir.'/'.$task['path']);

        $this->fetchFiles($app_to_dl);
		*/

		// Make sure the requirements are given so that this script can execute
		$req_ret = $this->checkRequirements();
		if(!$req_ret) return false;

		$metafile = $this->getContentFromZIP($this->packages_dir.'/'.$task['path'], 'APP-META.xml');
		// Check if the meta file is existing
		if(!$metafile)
		{
			$app->dbmaster->query('UPDATE aps_instances SET instance_status = ? WHERE id = ?', INSTANCE_ERROR, $task['instance_id']);
			$app->log('Unable to find the meta data file of package '.$task['path'], 1);
			return false;
		}

		// Rename namespaces and register them
		$metadata = str_replace("xmlns=", "ns=", $metafile);
		$sxe = new SimpleXMLElement($metadata);
		$namespaces = $sxe->getDocNamespaces(true);
		foreach($namespaces as $ns => $url) $sxe->registerXPathNamespace($ns, $url);

		// Setup the environment with data for the install location
		$this->prepareLocation($task);

		// Create the database if necessary
		$this->prepareDatabase($task, $sxe);

		// Unpack the install scripts from the packages
		if($this->prepareFiles($task, $sxe) && $this->handle_type == 'install')
		{
			// Setup the variables from the install script
			$this->prepareUserInputData($task);

			// Do the actual installation
			$this->doInstallation($task, $sxe);

			// Remove temporary files
			$this->cleanup($task, $sxe);
		}

		// Finally delete the instance entry + settings
		if($this->handle_type == 'delete')
		{
			$app->db->query('DELETE FROM aps_instances WHERE id = ?', $task['instance_id']);
			$app->db->query('DELETE FROM aps_instances_settings WHERE instance_id = ?', $task['instance_id']);
			if ($app->dbmaster != $app->db) {
				$app->dbmaster->query('DELETE FROM aps_instances WHERE id = ?', $task['instance_id']);
				$app->dbmaster->query('DELETE FROM aps_instances_settings WHERE instance_id = ?', $task['instance_id']);
			}
		}

		unset($sxe);
	}

}

?>
