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
 * Class backup
 * All code that makes actual backup and restore of web files and database is here.
 * @author Ramil Valitov <ramilvalitov@gmail.com>
 * @see backup::run_backup() to run a single backup
 * @see backup::run_all_backups() to run all backups
 * @see backup::restoreBackupDatabase() to restore a database
 * @see backup::restoreBackupWebFiles() to restore web files
 */
class backup
{
    /**
     * Returns file extension for specified backup format
     * @param string $format backup format
     * @return string|null
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function getBackupDbExtension($format)
    {
        $prefix = '.sql';
        switch ($format) {
            case 'gzip':
                return $prefix . '.gz';
            case 'bzip2':
                return $prefix . '.bz2';
            case 'xz':
                return $prefix . '.xz';
            case 'zip':
            case 'zip_bzip2':
                return '.zip';
            case 'rar':
                return '.rar';
        }
        if (strpos($format, "7z_") === 0) {
            return $prefix . '.7z';
        }
        return null;
    }

    /**
     * Returns file extension for specified backup format
     * @param string $format backup format
     * @return string|null
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function getBackupWebExtension($format)
    {
        switch ($format) {
            case 'tar_gzip':
                return '.tar.gz';
            case 'tar_bzip2':
                return '.tar.bz2';
            case 'tar_xz':
                return '.tar.xz';
            case 'zip':
            case 'zip_bzip2':
                return '.zip';
            case 'rar':
                return '.rar';
        }
        if (strpos($format, "tar_7z_") === 0) {
            return '.tar.7z';
        }
        return null;
    }

    /**
     * Sets file ownership to $web_user for all files and folders except log, ssl and web/stats
     * @param string $web_document_root
     * @param string $web_user
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function restoreFileOwnership($web_document_root, $web_user)
    {
        global $app;

        $app->log('Restoring permissions for ' . $web_document_root, LOGLEVEL_DEBUG);
        $app->system->exec_safe('cd ? && find . -not -path "./web/stats/*" -and -not -path "./log" -and -not -path "./log/*" -and -not -path "./ssl" -and -not -path "./ssl/*" -and -not -path "." -exec chown ?:? {} \;', $web_document_root, $web_user, $web_user);
    }

    /**
     * Returns default backup format used in previous versions of ISPConfig
     * @param string $backup_mode can be 'userzip' or 'rootgz'
     * @param string $backup_type can be 'web' or 'mysql'
     * @return string
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function getDefaultBackupFormat($backup_mode, $backup_type)
    {
        //We have a backup from old version of ISPConfig
        switch ($backup_type) {
            case 'mysql':
                return 'gzip';
            case 'web':
                return ($backup_mode == 'userzip') ? 'zip' : 'tar_gzip';
        }
        return "";
    }

    /**
     * Restores a database backup.
     * The backup directory must be mounted before calling this method.
     * @param string $backup_format
     * @param string $password password for encrypted backup or empty string if archive is not encrypted
     * @param string $backup_dir
     * @param string $filename
     * @param string $backup_mode
     * @param string $backup_type
     * @return bool true if succeeded
     * @see backup_plugin::mount_backup_dir()
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    public static function restoreBackupDatabase($backup_format, $password, $backup_dir, $filename, $backup_mode, $backup_type)
    {
        global $app;

        //* Load sql dump into db
        include 'lib/mysql_clientdb.conf';

        if (empty($backup_format)) {
            $backup_format = self::getDefaultBackupFormat($backup_mode, $backup_type);
        }
        $extension = self::getBackupDbExtension($backup_format);
        if (!empty($extension)) {
            //Replace dots for preg_match search
            $extension = str_replace('.', '\.', $extension);
        }
        $success = false;
        $full_filename = $backup_dir . '/' . $filename;

        $app->log('Restoring MySQL backup ' . $full_filename . ', backup format "' . $backup_format . '", backup mode "' . $backup_mode . '"', LOGLEVEL_DEBUG);

        if (file_exists($full_filename) && !empty($extension)) {
            preg_match('@^(manual-)?db_(?P<db>.+)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}' . $extension . '$@', $filename, $matches);
            if (!isset($matches['db']) || empty($matches['db'])) {
                $app->log('Failed to detect database name during restore of ' . $full_filename, LOGLEVEL_ERROR);
                return false;
            }
            $db_name = $matches['db'];
            switch ($backup_format) {
                case "gzip":
                    $command = "gunzip --stdout ? | mysql -h ? -u ? -p? ?";
                    break;
                case "zip":
                case "zip_bzip2":
                    $command = "unzip -qq -p -P " . escapeshellarg($password) . " ? | mysql -h ? -u ? -p? ?";
                    break;
                case "bzip2":
                    $command = "bunzip2 -q -c ? | mysql -h ? -u ? -p? ?";
                    break;
                case "xz":
                    $command = "unxz -q -q -c ? | mysql -h ? -u ? -p? ?";
                    break;
                case "rar":
                    //First, test that the archive is correct and we have a correct password
                    $options = self::getUnrarOptions($password);
                    $app->system->exec_safe("rar t " . $options . " ?", $full_filename);
                    if ($app->system->last_exec_retcode() == 0) {
                        $app->log('Archive test passed for ' . $full_filename, LOGLEVEL_DEBUG);
                        $command = "rar x " . $options. " ? | mysql -h ? -u ? -p? ?";
                    }
                    break;
            }
            if (strpos($backup_format, "7z_") === 0) {
                $options = self::get7zDecompressOptions($password);
                //First, test that the archive is correct and we have a correct password
                $app->system->exec_safe("7z t " . $options . " ?", $full_filename);
                if ($app->system->last_exec_retcode() == 0) {
                    $app->log('Archive test passed for ' . $full_filename, LOGLEVEL_DEBUG);
                    $command = "7z x " . $options . " -so ? | mysql -h ? -u ? -p? ?";
                } else
                    $command = null;
            }
            if (!empty($command)) {
                /** @var string $clientdb_host */
                /** @var string $clientdb_user */
                /** @var string $clientdb_password */
                $app->system->exec_safe($command, $full_filename, $clientdb_host, $clientdb_user, $clientdb_password, $db_name);
                $retval = $app->system->last_exec_retcode();
                if ($retval == 0) {
                    $app->log('Restored MySQL backup ' . $full_filename, LOGLEVEL_DEBUG);
                    $success = true;
                } else {
                    $app->log('Failed to restore web backup ' . $full_filename . ', exit code ' . $retval, LOGLEVEL_ERROR);
                }
            } else {
                $app->log('Archive test failed for ' . $full_filename, LOGLEVEL_DEBUG);
            }
        } else {
            $app->log('Failed to process MySQL backup ' . $full_filename, LOGLEVEL_ERROR);
        }
        unset($clientdb_host);
        unset($clientdb_user);
        unset($clientdb_password);

        return $success;
    }

    /**
     * Restores web files backup.
     * The backup directory must be mounted before calling this method.
     * @param string $backup_format
     * @param string $password password for encrypted backup or empty string if archive is not encrypted
     * @param string $backup_dir
     * @param string $filename
     * @param string $backup_mode
     * @param string $backup_type
     * @param string $web_root
     * @param string $web_user
     * @param string $web_group
     * @return bool true if succeed
     * @see backup_plugin::mount_backup_dir()
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    public static function restoreBackupWebFiles($backup_format, $password, $backup_dir, $filename, $backup_mode, $backup_type, $web_root, $web_user, $web_group)
    {
        global $app;

        if (empty($backup_format)) {
            $backup_format = self::getDefaultBackupFormat($backup_mode, $backup_type);
        }
        $full_filename = $backup_dir . '/' . $filename;
        $result = false;

        $app->log('Restoring web backup ' . $full_filename . ', backup format "' . $backup_format . '", backup mode "' . $backup_mode . '"', LOGLEVEL_DEBUG);

        if (!empty($backup_format)) {
            $app->system->web_folder_protection($web_root, false);
            if ($backup_mode == 'userzip' || $backup_mode == 'rootgz') {
                $user_mode = $backup_mode == 'userzip';
                $filename = $user_mode ? ($web_root . '/backup/' . $filename) : $full_filename;

                if (file_exists($full_filename) && $web_root != '' && $web_root != '/' && !stristr($full_filename, '..') && !stristr($full_filename, 'etc')) {
                    if ($user_mode) {
                        if (file_exists($filename)) rename($filename, $filename . '.bak');
                        copy($full_filename, $filename);
                        chgrp($filename, $web_group);
                    }
                    $user_prefix_cmd = $user_mode ? 'sudo -u ' . escapeshellarg($web_user) : '';
                    $success = false;
                    $retval = 0;
                    switch ($backup_format) {
                        case "tar_gzip":
                        case "tar_bzip2":
                        case "tar_xz":
                            $command = $user_prefix_cmd . ' tar xf ? --directory ?';
                            $app->system->exec_safe($command, $filename, $web_root);
                            $retval = $app->system->last_exec_retcode();
                            $success = ($retval == 0 || $retval == 2);
                            break;
                        case "zip":
                        case "zip_bzip2":
                            $command = $user_prefix_cmd . ' unzip -qq -P ' . escapeshellarg($password) . ' -o ? -d ? 2> /dev/null';
                            $app->system->exec_safe($command, $filename, $web_root);
                            $retval = $app->system->last_exec_retcode();
                            /*
                             * Exit code 50 can happen when zip fails to overwrite files that do not
                             * belong to selected user, so we can consider this situation as success
                             * with warnings.
                             */
                            $success = ($retval == 0 || $retval == 50);
                            if ($success) {
                                self::restoreFileOwnership($web_root, $web_user);
                            }
                            break;
                        case 'rar':
                            $options = self::getUnRarOptions($password);
                            //First, test that the archive is correct and we have a correct password
                            $command = $user_prefix_cmd . " rar t " . $options . " ? ?";
                            //Rar requires trailing slash
                            $app->system->exec_safe($command, $filename, $web_root . '/');
                            $success = ($app->system->last_exec_retcode() == 0);
                            if ($success) {
                                //All good, now we can extract
                                $app->log('Archive test passed for ' . $full_filename, LOGLEVEL_DEBUG);
                                $command = $user_prefix_cmd . " rar x " . $options . " ? ?";
                                //Rar requires trailing slash
                                $app->system->exec_safe($command, $filename, $web_root . '/');
                                $retval = $app->system->last_exec_retcode();
                                //Exit code 9 can happen when we have file permission errors, in this case some
                                //files will be skipped during extraction.
                                $success = ($retval == 0 || $retval == 1 || $retval == 9);
                            } else {
                                $app->log('Archive test failed for ' . $full_filename, LOGLEVEL_DEBUG);
                            }
                            break;
                    }
                    if (strpos($backup_format, "tar_7z_") === 0) {
                        $options = self::get7zDecompressOptions($password);
                        //First, test that the archive is correct and we have a correct password
                        $command = $user_prefix_cmd . " 7z t " . $options . " ?";
                        $app->system->exec_safe($command, $filename);
                        $success = ($app->system->last_exec_retcode() == 0);
                        if ($success) {
                            //All good, now we can extract
                            $app->log('Archive test passed for ' . $full_filename, LOGLEVEL_DEBUG);
                            $command = $user_prefix_cmd . " 7z x " . $options . " -so ? | tar xf - --directory ?";
                            $app->system->exec_safe($command, $filename, $web_root);
                            $retval = $app->system->last_exec_retcode();
                            $success = ($retval == 0 || $retval == 2);
                        } else {
                            $app->log('Archive test failed for ' . $full_filename, LOGLEVEL_DEBUG);
                        }
                    }
                    if ($user_mode) {
                        unlink($filename);
                        if (file_exists($filename . '.bak')) rename($filename . '.bak', $filename);
                    }
                    if ($success) {
                        $app->log('Restored web backup ' . $full_filename, LOGLEVEL_DEBUG);
                        $result = true;
                    } else {
                        $app->log('Failed to restore web backup ' . $full_filename . ', exit code ' . $retval, LOGLEVEL_ERROR);
                    }
                }
            } else {
                $app->log('Failed to restore web backup ' . $full_filename . ', backup mode "' . $backup_mode . '" not recognized.', LOGLEVEL_DEBUG);
            }
            $app->system->web_folder_protection($web_root, true);
        } else {
            $app->log('Failed to restore web backup ' . $full_filename . ', backup format not recognized.', LOGLEVEL_DEBUG);
        }
        return $result;
    }

    /**
     * Returns a compression method, for example returns bzip2 for tar_7z_bzip2
     * @param string $format
     * @return false|string
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function getCompressionMethod($format)
    {
        $pos = strrpos($format, "_");
        return substr($format, $pos + 1);
    }

    /**
     * Returns default options for compressing rar
     * @param string $backup_tmp temporary directory that rar can use
     * @param string|null $password backup password if any
     * @return string options for rar
     */
    protected static function getRarOptions($backup_tmp, $password)
    {
        /**
         * All rar options are listed here:
         * https://documentation.help/WinRAR/HELPCommands.htm
         * https://documentation.help/WinRAR/HELPSwitches.htm
         * Some compression profiles and different versions of rar may use different default values, so it's better
         * to specify everything explicitly.
         * The difference between compression methods is not big in terms of file size, but is huge in terms of
         * CPU and RAM consumption. Therefore it makes sense only to use fastest the compression method.
         */
        $options = array(
            /**
             * Start with fastest compression method (least compressive)
             */
            '-m1',

            /**
             * Disable solid archiving.
             * Never use solid archive: it's very slow and requires to read and sort all files first
             */
            '-S-',

            /**
             * Ignore default profile and environment variables
             * https://documentation.help/WinRAR/HELPSwCFGm.htm
             */
            '-CFG-',

            /**
             *  Disable error messages output
             * https://documentation.help/WinRAR/HELPSwINUL.htm
             */
            '-inul',

            /**
             * Lock archive: this switch prevents any further archive modifications by rar
             * https://documentation.help/WinRAR/HELPSwK.htm
             */
            '-k',

            /**
             * Create archive in RAR 5.0 format
             * https://documentation.help/WinRAR/HELPSwMA.htm
             */
            '-ma',

            /**
             * Set dictionary size to 16Mb.
             * When archiving, rar needs about 6x memory of specified dictionary size.
             * https://documentation.help/WinRAR/HELPSwMD.htm
             */
            '-md16m',

            /**
             * Use only one CPU thread
             * https://documentation.help/WinRAR/HELPSwMT.htm
             */
            '-mt1',

            /**
             * Use this switch when archiving to save file security information and when extracting to restore it.
             * It stores file owner, group, file permissions and audit information.
             * https://documentation.help/WinRAR/HELPSwOW.htm
             */
            '-ow',

            /**
             * Overwrite all
             * https://documentation.help/WinRAR/HELPSwO.htm
             */
            '-o+',

            /**
             * Exclude base folder from names.
             * Required for correct directory structure inside archive
             * https://documentation.help/WinRAR/HELPSwEP1.htm
             */
            '-ep1',

            /**
             * Never add quick open information.
             * This information is useful only if you want to read the contents of archive (list of files).
             * Besides it can increase the archive size. As we need the archive only for future complete extraction,
             * there's no need to use this information at all.
             * https://documentation.help/WinRAR/HELPSwQO.htm
             */
            '-qo-',

            /**
             * Set lowest task priority (1) and 10ms sleep time between read/write operations.
             * https://documentation.help/WinRAR/HELPSwRI.htm
             */
            '-ri1:10',

            /**
             * Temporary folder
             * https://documentation.help/WinRAR/HELPSwW.htm
             */
            '-w' . escapeshellarg($backup_tmp),

            /**
             * Assume Yes on all queries
             * https://documentation.help/WinRAR/HELPSwY.htm
             */
            '-y',
        );

        $options = implode(" ", $options);

        if (!empty($password)) {
            /**
             * Encrypt both file data and headers
             * https://documentation.help/WinRAR/HELPSwHP.htm
             */
            $options .= ' -HP' . escapeshellarg($password);
        }
        return $options;
    }

    /**
     * Returns default options for decompressing rar
     * @param string|null $password backup password if any
     * @return string options for rar
     */
    protected static function getUnRarOptions($password)
    {
        /**
         * All rar options are listed here:
         * https://documentation.help/WinRAR/HELPCommands.htm
         * https://documentation.help/WinRAR/HELPSwitches.htm
         * Some compression profiles and different versions of rar may use different default values, so it's better
         * to specify everything explicitly.
         * The difference between compression methods is not big in terms of file size, but is huge in terms of
         * CPU and RAM consumption. Therefore it makes sense only to use fastest the compression method.
         */
        $options = array(
            /**
             * Ignore default profile and environment variables
             * https://documentation.help/WinRAR/HELPSwCFGm.htm
             */
            '-CFG-',

            /**
             *  Disable error messages output
             * https://documentation.help/WinRAR/HELPSwINUL.htm
             */
            '-inul',

            /**
             * Use only one CPU thread
             * https://documentation.help/WinRAR/HELPSwMT.htm
             */
            '-mt1',

            /**
             * Use this switch when archiving to save file security information and when extracting to restore it.
             * It stores file owner, group, file permissions and audit information.
             * https://documentation.help/WinRAR/HELPSwOW.htm
             */
            '-ow',

            /**
             * Overwrite all
             * https://documentation.help/WinRAR/HELPSwO.htm
             */
            '-o+',

            /**
             * Set lowest task priority (1) and 10ms sleep time between read/write operations.
             * https://documentation.help/WinRAR/HELPSwRI.htm
             */
            '-ri1:10',

            /**
             * Assume Yes on all queries
             * https://documentation.help/WinRAR/HELPSwY.htm
             */
            '-y',
        );

        $options = implode(" ", $options);

        if (!empty($password)) {
            $options .= ' -P' . escapeshellarg($password);
        }
        return $options;
    }

    /**
     * Returns compression options for 7z
     * @param string $format compression format used in 7z
     * @param string $password password if any
     * @return string
     */
    protected static function get7zCompressOptions($format, $password)
    {
        $method = self::getCompressionMethod($format);
        /**
         * List of 7z options is here:
         * https://linux.die.net/man/1/7z
         * https://sevenzip.osdn.jp/chm/cmdline/syntax.htm
         * https://sevenzip.osdn.jp/chm/cmdline/switches/
         */
        $options = array(
            /**
             * Use 7z format (container)
             */
            '-t7z',

            /**
             * Compression method (LZMA, LZMA2, etc.)
             * https://sevenzip.osdn.jp/chm/cmdline/switches/method.htm
             */
            '-m0=' . $method,

            /**
             * Fastest compression method
             */
            '-mx=1',

            /**
             * Disable solid mode
             */
            '-ms=off',

            /**
             * Disable multithread mode, use less CPU
             */
            '-mmt=off',

            /**
             * Disable multithread mode for filters, use less CPU
             */
            '-mmtf=off',

            /**
             * Disable progress indicator
             */
            '-bd',

            /**
             * Assume yes on all queries
             * https://sevenzip.osdn.jp/chm/cmdline/switches/yes.htm
             */
            '-y',
        );
        $options = implode(" ", $options);
        switch (strtoupper($method)) {
            case 'LZMA':
            case 'LZMA2':
                /**
                 * Dictionary size is 5Mb.
                 * 7z can use 12 times more RAM
                 */
                $options .= ' -md=5m';
                break;
            case 'PPMD':
                /**
                 * Dictionary size is 64Mb.
                 * It's the maximum RAM that 7z is allowed to use.
                 */
                $options .= ' -mmem=64m';
                break;
        }
        if (!empty($password)) {
            $options .= ' -mhe=on -p' . escapeshellarg($password);
        }
        return $options;
    }

    /**
     * Returns decompression options for 7z
     * @param string $password password if any
     * @return string
     */
    protected static function get7zDecompressOptions($password)
    {
        /**
         * List of 7z options is here:
         * https://linux.die.net/man/1/7z
         * https://sevenzip.osdn.jp/chm/cmdline/syntax.htm
         * https://sevenzip.osdn.jp/chm/cmdline/switches/
         */
        $options = array(
            /**
             * Disable multithread mode, use less CPU
             */
            '-mmt=off',

            /**
             * Disable progress indicator
             */
            '-bd',

            /**
             * Assume yes on all queries
             * https://sevenzip.osdn.jp/chm/cmdline/switches/yes.htm
             */
            '-y',
        );
        $options = implode(" ", $options);
        if (!empty($password)) {
            $options .= ' -p' . escapeshellarg($password);
        }
        return $options;
    }

    /**
     * Clears expired backups.
     * The backup directory must be mounted before calling this method.
     * @param integer $server_id
     * @param integer $web_id id of the website
     * @param integer $max_backup_copies number of backup copies to keep, all files beyond the limit will be erased
     * @param string $backup_dir directory to scan
     * @return bool
     * @see backup_plugin::backups_garbage_collection() call this method first
     * @see backup_plugin::mount_backup_dir()
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function clearBackups($server_id, $web_id, $max_backup_copies, $backup_dir)
    {
        global $app;

        $files = self::get_files($backup_dir);
        usort($files, function ($a, $b) use ($backup_dir) {
            $time_a = filemtime($backup_dir . '/' . $a);
            $time_b = filemtime($backup_dir . '/' . $b);
            return ($time_a > $time_b) ? -1 : 1;
        });

        $db_list = array($app->db);
        if ($app->db->dbHost != $app->dbmaster->dbHost)
            array_push($db_list, $app->dbmaster);

        //Delete old files that are beyond the limit
        for ($n = $max_backup_copies; $n < sizeof($files); $n++) {
            $filename = $files[$n];
            $full_filename = $backup_dir . '/' . $filename;
            $app->log('Backup file ' . $full_filename . ' is beyond the limit of ' . $max_backup_copies . " copies and will be deleted from disk and database", LOGLEVEL_DEBUG);
            $sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
            foreach ($db_list as $db) {
                $db->query($sql, $server_id, $web_id, $filename);
            }
            @unlink($full_filename);
        }
        return true;
    }

    /**
     * Garbage collection: deletes records from database about files that do not exist and deletes untracked files.
     * The backup directory must be mounted before calling this method.
     * @param int $server_id
     * @param string|null $backup_type if defined then process only backups of this type
     * @param string|null $domain_id if defined then process only backups that belong to this domain
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     * @see backup_plugin::mount_backup_dir()
     */
    protected static function backups_garbage_collection($server_id, $backup_type = null, $domain_id = null)
    {
        global $app;

        //First check that all records in database have related files and delete records without files on disk
        $args = array();
        $args_domains = array();
        $server_config = $app->getconf->get_server_config($server_id, 'server');
        $backup_dir = trim($server_config['backup_dir']);
        $sql = "SELECT * FROM web_backup WHERE server_id = ?";
        $sql_domains = "SELECT domain_id,system_user,system_group,backup_interval FROM web_domain WHERE server_id = ? AND (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias')";
        array_push($args, $server_id);
        array_push($args_domains, $server_id);
        if (!empty($backup_type)) {
            $sql .= " AND backup_type = ?";
            array_push($args, $backup_type);
        }
        if (!empty($domain_id)) {
            $sql .= " AND parent_domain_id = ?";
            $sql_domains .= " AND domain_id = ?";
            array_push($args, $domain_id);
            array_push($args_domains, $domain_id);
        }
        array_unshift($args, $sql);
        array_unshift($args_domains, $sql_domains);

        $db_list = array($app->db);
        if ($app->db->dbHost != $app->dbmaster->dbHost)
            array_push($db_list, $app->dbmaster);

        foreach ($db_list as $db) {
            $backups = call_user_func_array(array($db, "queryAllRecords"), $args);
            foreach ($backups as $backup) {
                $backup_file = $backup_dir . '/web' . $backup['parent_domain_id'] . '/' . $backup['filename'];
                if (!is_file($backup_file)) {
                    $app->log('Backup file ' . $backup_file . ' does not exist on disk, deleting this entry from database', LOGLEVEL_DEBUG);
                    $sql = "DELETE FROM web_backup WHERE backup_id = ?";
                    $db->query($sql, $backup['backup_id']);
                }
            }
        }

        foreach ($db_list as $db) {
            $domains = call_user_func_array(array($db, "queryAllRecords"), $args_domains);
            foreach ($domains as $rec) {
                $domain_id = $rec['domain_id'];
                $domain_backup_dir = $backup_dir . '/web' . $domain_id;
                $files = self::get_files($domain_backup_dir);

                //Delete files that are in backup directory, but do not exist in database
                if (!empty($files)) {
                    $sql = "SELECT backup_id,filename FROM web_backup WHERE server_id = ? AND parent_domain_id = ?";
                    $backups = $db->queryAllRecords($sql, $server_id, $domain_id);
                    foreach ($backups as $backup) {
                        if (!in_array($backup['filename'],$files)) {
                            $backup_file = $backup_dir . '/web' . $domain_id . '/' . $backup['filename'];
                            $app->log('Backup file ' . $backup_file . ' is not contained in database, deleting this file from disk', LOGLEVEL_DEBUG);
                            @unlink($backup_file);
                        }
                    }
                }

                //Remove backupdir symlink and create as directory instead
                $web_path = $rec['document_root'];
                $app->system->web_folder_protection($web_path, false);

                $backup_download_dir = $web_path . '/backup';
                if (is_link($backup_download_dir)) {
                    unlink($backup_download_dir);
                }
                if (!is_dir($backup_download_dir)) {
                    mkdir($backup_download_dir);
                    chown($backup_download_dir, $rec['system_user']);
                    chgrp($backup_download_dir, $rec['system_group']);
                }

                $app->system->web_folder_protection($web_path, true);

                // delete old files from backup download dir (/var/www/example.com/backup)
                if (is_dir($backup_download_dir)) {
                    $dir_handle = dir($backup_download_dir);
                    $now = time();
                    while (false !== ($entry = $dir_handle->read())) {
                        $full_filename = $backup_download_dir . '/' . $entry;
                        if ($entry != '.' && $entry != '..' && is_file($full_filename)) {
                            // delete files older than 3 days
                            if ($now - filemtime($full_filename) >= 60 * 60 * 24 * 3) {
                                $app->log('Backup file ' . $full_filename . ' is too old, deleting this file from disk', LOGLEVEL_DEBUG);
                                @unlink($full_filename);
                            }
                        }
                    }
                    $dir_handle->close();
                }
            }
        }
    }

    /**
     * Gets list of files in directory
     * @param string $directory
     * @param string[]|null $prefix_list filter files that have one of the prefixes. Use null for default filtering.
     * @param string[]|null $endings_list filter files that have one of the endings. Use null for default filtering.
     * @return string[]
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function get_files($directory, $prefix_list = null, $endings_list = null)
    {
        $default_prefix_list = array(
            'web',
            'manual-web',
            'db_',
            'manual-db_',
        );
        $default_endings_list = array(
            '.gz',
            '.7z',
            '.rar',
            '.zip',
            '.xz',
            '.bz2',
        );
        if (is_null($prefix_list))
            $prefix_list = $default_prefix_list;
        if (is_null($endings_list))
            $endings_list = $default_endings_list;

        $dir_handle = dir($directory);
        $files = array();
        while (false !== ($entry = $dir_handle->read())) {
            $full_filename = $directory . '/' . $entry;
            if ($entry != '.' && $entry != '..' && is_file($full_filename)) {
                if (!empty($prefix_list)) {
                    $add = false;
                    foreach ($prefix_list as $prefix) {
                        if (substr($entry, 0, strlen($prefix)) == $prefix) {
                            $add = true;
                            break;
                        }
                    }
                } else
                    $add = true;
                if ($add && !empty($endings_list)) {
                    $add = false;
                    foreach ($endings_list as $ending) {
                        if (substr($entry, -strlen($ending)) == $ending) {
                            $add = true;
                            break;
                        }
                    }
                }
                if ($add)
                    array_push($files, $entry);
            }
        }
        $dir_handle->close();

        return $files;
    }

    /**
     * Generates excludes list for compressors
     * @param string[] $backup_excludes
     * @param string $arg
     * @return string
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function generateExcludeList($backup_excludes, $arg)
    {
        $excludes = implode(" " . $arg, $backup_excludes);
        if (!empty($excludes)) {
            $excludes = $arg . $excludes;
        }
        return $excludes;
    }

    /**
     * Runs a web compression routine
     * @param string $format
     * @param string[] $backup_excludes
     * @param string $backup_mode
     * @param string $web_path
     * @param string $web_backup_dir
     * @param string $web_backup_file
     * @param string $web_user
     * @param string $web_group
     * @param string $http_server_user
     * @param string $backup_tmp
     * @param string|null $password
     * @return bool true if success
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function runWebCompression($format, $backup_excludes, $backup_mode, $web_path, $web_backup_dir, $web_backup_file, $web_user, $web_group, $http_server_user, $backup_tmp, $password)
    {
        global $app;

        $find_user_files = 'cd ? && sudo -u ? find . -group ? -or -user ? -print 2> /dev/null';
        $excludes = self::generateExcludeList($backup_excludes, '--exclude=');
        $tar_dir = 'tar pcf - ' . $excludes . ' --directory ? .';
        $tar_input = 'tar pcf --null -T -';

        $app->log('Performing web files backup of ' . $web_path . ' in format ' . $format . ', mode ' . $backup_mode, LOGLEVEL_DEBUG);
        switch ($format) {
            case 'tar_gzip':
                if ($app->system->is_installed('pigz')) {
                    //use pigz
                    if ($backup_mode == 'user_zip') {
                        $app->system->exec_safe($find_user_files . ' | ' . $tar_input . ' | pigz > ?', $web_path, $web_user, $web_group, $http_server_user, $web_path, $web_backup_dir . '/' . $web_backup_file);
                    } else {
                        //Standard casual behaviour of ISPConfig
                        $app->system->exec_safe($tar_dir . ' | pigz > ?', $web_path, $web_backup_dir . '/' . $web_backup_file);
                    }
                    $exit_code = $app->system->last_exec_retcode();
                    return $exit_code == 0;
                } else {
                    //use gzip
                    if ($backup_mode == 'user_zip') {
                        $app->system->exec_safe($find_user_files . ' | tar pczf ? --null -T -', $web_path, $web_user, $web_group, $http_server_user, $web_backup_dir . '/' . $web_backup_file);
                    } else {
                        //Standard casual behaviour of ISPConfig
                        $app->system->exec_safe('tar pczf ? ' . $excludes . ' --directory ? .', $web_backup_dir . '/' . $web_backup_file, $web_path);
                    }
                    $exit_code = $app->system->last_exec_retcode();
                    // tar can return 1 and still create valid backups
                    return ($exit_code == 0 || $exit_code == 1);
                }
            case 'zip':
            case 'zip_bzip2':
                $zip_options = ($format === 'zip_bzip2') ? ' -Z bzip2 ' : '';
                if (!empty($password)) {
                    $zip_options .= ' --password ' . escapeshellarg($password);
                }
                if ($backup_mode == 'user_zip') {
                    //Standard casual behaviour of ISPConfig
                    $app->system->exec_safe($find_user_files . ' | zip ' . $zip_options . ' -b ? ' . $excludes . ' --symlinks ? -@', $web_path, $web_user, $web_group, $http_server_user, $backup_tmp, $web_backup_dir . '/' . $web_backup_file);
                } else {
                    //Use cd to have a correct directory structure inside the archive, extra options to zip hidden (dot) files
                    $app->system->exec_safe('cd ? && zip ' . $zip_options . ' -b ? ' . $excludes . ' --symlinks -r ? * .* -x "../*"', $web_path, $backup_tmp, $web_backup_dir . '/' . $web_backup_file);
                }
                $exit_code = $app->system->last_exec_retcode();
                // zip can return 12(due to harmless warnings) and still create valid backups
                return ($exit_code == 0 || $exit_code == 12);
            case 'tar_bzip2':
                if ($backup_mode == 'user_zip') {
                    $app->system->exec_safe($find_user_files . ' | tar pcjf ? --null -T -', $web_path, $web_user, $web_group, $http_server_user, $web_backup_dir . '/' . $web_backup_file);
                } else {
                    $app->system->exec_safe('tar pcjf ? ' . $excludes . ' --directory ? .', $web_backup_dir . '/' . $web_backup_file, $web_path);
                }
                $exit_code = $app->system->last_exec_retcode();
                // tar can return 1 and still create valid backups
                return ($exit_code == 0 || $exit_code == 1);
            case 'tar_xz':
                if ($backup_mode == 'user_zip') {
                    $app->system->exec_safe($find_user_files . ' | tar pcJf ? --null -T -', $web_path, $web_user, $web_group, $http_server_user, $web_backup_dir . '/' . $web_backup_file);
                } else {
                    $app->system->exec_safe('tar pcJf ? ' . $excludes . ' --directory ? .', $web_backup_dir . '/' . $web_backup_file, $web_path);
                }
                $exit_code = $app->system->last_exec_retcode();
                // tar can return 1 and still create valid backups
                return ($exit_code == 0 || $exit_code == 1);
            case 'rar':
                $options = self::getRarOptions($backup_tmp,$password);
                if ($backup_mode != 'user_zip') {
                    //Recurse subfolders, otherwise we will pass a list of files to compress
                    $options .= ' -r';
                }
                $excludes = self::generateExcludeList($backup_excludes, '-x');
                $zip_command = 'rar a ' . $options . ' '.$excludes.' ?';
                if ($backup_mode == 'user_zip') {
                    $app->system->exec_safe($find_user_files . ' | ' . $zip_command . ' ? @', $web_path, $web_user, $web_group, $http_server_user, $web_path, $web_backup_dir . '/' . $web_backup_file);
                } else {
                    $app->system->exec_safe('cd ? && ' . $zip_command . ' .', $web_path, $web_backup_dir . '/' . $web_backup_file);
                }
                $exit_code = $app->system->last_exec_retcode();
                return ($exit_code == 0 || $exit_code == 1);
        }
        if (strpos($format, "tar_7z_") === 0) {
            $options = self::get7zCompressOptions($format, $password);
            $zip_command = '7z a ' . $options . ' -si ?';
            if ($backup_mode == 'user_zip') {
                $app->system->exec_safe($find_user_files . ' | ' . $tar_input . ' | '. $zip_command, $web_path, $web_user, $web_group, $http_server_user, $web_path, $web_backup_dir . '/' . $web_backup_file);
            } else {
                $app->system->exec_safe($tar_dir . ' | ' . $zip_command, $web_path, $web_backup_dir . '/' . $web_backup_file);
            }
            $exit_code = $app->system->last_exec_retcode();
            return $exit_code == 0;
        }
        return false;
    }

    /**
     * Runs a database compression routine
     * @param string $format
     * @param string $db_backup_dir
     * @param string $db_backup_file
     * @param string $compressed_backup_file
     * @param string $backup_tmp
     * @param string|null $password
     * @return bool true if success
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function runDatabaseCompression($format, $db_backup_dir, $db_backup_file, $compressed_backup_file, $backup_tmp, $password)
    {
        global $app;

        $app->log('Performing database backup to file ' . $compressed_backup_file . ' in format ' . $format, LOGLEVEL_DEBUG);
        switch ($format) {
            case 'gzip':
                if ($app->system->is_installed('pigz')) {
                    //use pigz
                    $zip_cmd = 'pigz';
                } else {
                    //use gzip
                    $zip_cmd = 'gzip';
                }
                $app->system->exec_safe($zip_cmd . " -c ? > ?", $db_backup_dir . '/' . $db_backup_file, $db_backup_dir . '/' . $compressed_backup_file);
                $exit_code = $app->system->last_exec_retcode();
                return $exit_code == 0;
            case 'zip':
            case 'zip_bzip2':
                $zip_options = ($format === 'zip_bzip2') ? ' -Z bzip2 ' : '';
                if (!empty($password)) {
                    $zip_options .= ' --password ' . escapeshellarg($password);
                }
                $app->system->exec_safe('zip ' . $zip_options . ' -j -b ? ? ?', $backup_tmp, $db_backup_dir . '/' . $compressed_backup_file, $db_backup_dir . '/' . $db_backup_file);
                $exit_code = $app->system->last_exec_retcode();
                // zip can return 12(due to harmless warnings) and still create valid backups
                return ($exit_code == 0 || $exit_code == 12);
            case 'bzip2':
                $app->system->exec_safe("bzip2 -q -c ? > ?", $db_backup_dir . '/' . $db_backup_file, $db_backup_dir . '/' . $compressed_backup_file);
                $exit_code = $app->system->last_exec_retcode();
                return $exit_code == 0;
            case 'xz':
                $app->system->exec_safe("xz -q -q -c ? > ?", $db_backup_dir . '/' . $db_backup_file, $db_backup_dir . '/' . $compressed_backup_file);
                $exit_code = $app->system->last_exec_retcode();
                return $exit_code == 0;
            case 'rar':
                $options = self::getRarOptions($backup_tmp, $password);
                $zip_command = 'rar a ' . $options . ' ? ?';
                $app->system->exec_safe($zip_command, $db_backup_dir . '/' . $compressed_backup_file, $db_backup_dir . '/' . $db_backup_file);
                $exit_code = $app->system->last_exec_retcode();
                return ($exit_code == 0 || $exit_code == 1);
        }
        if (strpos($format, "7z_") === 0) {
            $options = self::get7zCompressOptions($format, $password);
            $zip_command = '7z a ' . $options . ' ? ?';
            $app->system->exec_safe($zip_command, $db_backup_dir . '/' . $compressed_backup_file, $db_backup_dir . '/' . $db_backup_file);
            $exit_code = $app->system->last_exec_retcode();
            return $exit_code == 0;
        }
        return false;
    }

    /**
     * Mounts the backup directory if required
     * @param int $server_id
     * @return bool true if success
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     * @see backup_plugin::unmount_backup_dir()
     */
    public static function mount_backup_dir($server_id)
    {
        global $app;

        $server_config = $app->getconf->get_server_config($server_id, 'server');
        if ($server_config['backup_dir_is_mount'] == 'y')
            return $app->system->mount_backup_dir($server_config['backup_dir']);
        return true;
    }

    /**
     * Unmounts the backup directory if required
     * @param int $server_id
     * @return bool true if success
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     * @see backup_plugin::mount_backup_dir()
     */
    public static function unmount_backup_dir($server_id)
    {
        global $app;

        $server_config = $app->getconf->get_server_config($server_id, 'server');
        if ($server_config['backup_dir_is_mount'] == 'y')
            return $app->system->umount_backup_dir($server_config['backup_dir']);
        return true;
    }

    /**
     * Makes backup of database.
     * The backup directory must be mounted before calling this method.
     * This method is for private use only, don't call this method unless you know what you're doing.
     * @param array $web_domain
     * @param string $backup_job type of backup job: manual or auto
     * @return bool true if success
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     * @see backup_plugin::run_backup() recommeneded to use if you need to make backups
     */
    protected static function make_database_backup($web_domain, $backup_job)
    {
        global $app;

        $server_id = intval($web_domain['server_id']);
        $domain_id = intval($web_domain['domain_id']);
        $server_config = $app->getconf->get_server_config($server_id, 'server');
        $backup_dir = trim($server_config['backup_dir']);
        $backup_tmp = trim($server_config['backup_tmp']);
        $db_backup_dir = $backup_dir . '/web' . $domain_id;
        $success = false;

        if (empty($backup_job))
            $backup_job = "auto";

        $records = $app->db->queryAllRecords("SELECT * FROM web_database WHERE server_id = ? AND parent_domain_id = ?", $server_id, $domain_id);
        if (empty($records)){
            $app->log('Skipping database backup for domain ' . $web_domain['domain_id'] . ', because no related databases found.', LOGLEVEL_ERROR);
            return true;
        }

        self::prepare_backup_dir($server_id, $web_domain);

        include '/usr/local/ispconfig/server/lib/mysql_clientdb.conf';

        //* Check mysqldump capabilities
        exec('mysqldump --help', $tmp);
        $mysqldump_routines = (strpos(implode($tmp), '--routines') !== false) ? '--routines' : '';
        unset($tmp);

        foreach ($records as $rec) {
            $password = ($web_domain['backup_encrypt'] == 'y') ? trim($web_domain['backup_password']) : '';
            $backup_format_db = $web_domain['backup_format_db'];
            if (empty($backup_format_db)) {
                $backup_format_db = 'gzip';
            }
            $backup_extension_db = self::getBackupDbExtension($backup_format_db);

            if (!empty($backup_extension_db)) {
                //* Do the mysql database backup with mysqldump
                $db_name = $rec['database_name'];
                $db_file_prefix = 'db_' . $db_name . '_' . date('Y-m-d_H-i');
                $db_backup_file = $db_file_prefix . '.sql';
                $db_compressed_file = ($backup_job == 'manual' ? 'manual-' : '') . $db_file_prefix . $backup_extension_db;
                $command = "mysqldump -h ? -u ? -p? -c --add-drop-table --create-options --quick --max_allowed_packet=512M " . $mysqldump_routines . " --result-file=? ?";
                /** @var string $clientdb_host */
                /** @var string $clientdb_user */
                /** @var string $clientdb_password */
                $app->system->exec_safe($command, $clientdb_host, $clientdb_user, $clientdb_password, $db_backup_dir . '/' . $db_backup_file, $db_name);
                $exit_code = $app->system->last_exec_retcode();

                //* Compress the backup
                if ($exit_code == 0) {
                    $exit_code = self::runDatabaseCompression($backup_format_db, $db_backup_dir, $db_backup_file, $db_compressed_file, $backup_tmp, $password) ? 0 : 1;
                    if ($exit_code !== 0)
                        $app->log('Failed to make backup of database ' . $rec['database_name'], LOGLEVEL_ERROR);
                } else {
                    $app->log('Failed to make backup of database ' . $rec['database_name'] . ', because mysqldump failed', LOGLEVEL_ERROR);
                }

                if ($exit_code == 0) {
                    if (is_file($db_backup_dir . '/' . $db_compressed_file)) {
                        chmod($db_backup_dir . '/' . $db_compressed_file, 0750);
                        chown($db_backup_dir . '/' . $db_compressed_file, fileowner($db_backup_dir));
                        chgrp($db_backup_dir . '/' . $db_compressed_file, filegroup($db_backup_dir));

                        //* Insert web backup record in database
                        $file_size = filesize($db_backup_dir . '/' . $db_compressed_file);
                        $sql = "INSERT INTO web_backup (server_id, parent_domain_id, backup_type, backup_mode, backup_format, tstamp, filename, filesize, backup_password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        //Making compatible with previous versions of ISPConfig:
                        $sql_mode = ($backup_format_db == 'gzip') ? 'sqlgz' : ('sql' . $backup_format_db);
                        $app->db->query($sql, $server_id, $domain_id, 'mysql', $sql_mode, $backup_format_db, time(), $db_compressed_file, $file_size, $password);
                        if ($app->db->dbHost != $app->dbmaster->dbHost)
                            $app->dbmaster->query($sql, $server_id, $domain_id, 'mysql', $sql_mode, $backup_format_db, time(), $db_compressed_file, $file_size, $password);
                        $success = true;
                    }
                } else {
                    if (is_file($db_backup_dir . '/' . $db_compressed_file)) unlink($db_backup_dir . '/' . $db_compressed_file);
                }
                //* Remove the uncompressed file
                if (is_file($db_backup_dir . '/' . $db_backup_file)) unlink($db_backup_dir . '/' . $db_backup_file);

                //* Remove old backups
                self::backups_garbage_collection($server_id, 'mysql', $domain_id);
                self::clearBackups($server_id, $domain_id, intval($rec['backup_copies']), $db_backup_dir);
            } else {
                $app->log('Failed to process mysql backup format ' . $backup_format_db . ' for database ' . $rec['database_name'], LOGLEVEL_ERROR);
            }
        }

        unset($clientdb_host);
        unset($clientdb_user);
        unset($clientdb_password);

        return $success;
    }

    /**
     * Makes backup of web files.
     * The backup directory must be mounted before calling this method.
     * This method is for private use only, don't call this method unless you know what you're doing
     * @param array $web_domain info about domain to backup, SQL record of table 'web_domain'
     * @param string $backup_job type of backup job: manual or auto
     * @return bool true if success
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     * @see backup_plugin::mount_backup_dir()
     * @see backup_plugin::run_backup() recommeneded to use if you need to make backups
     */
    protected static function make_web_backup($web_domain, $backup_job)
    {
        global $app;

        $server_id = intval($web_domain['server_id']);
        $domain_id = intval($web_domain['domain_id']);
        $server_config = $app->getconf->get_server_config($server_id, 'server');
        $global_config = $app->getconf->get_global_config('sites');
        $backup_dir = trim($server_config['backup_dir']);
        $backup_mode = $server_config['backup_mode'];
        $backup_tmp = trim($server_config['backup_tmp']);
        if (empty($backup_mode))
            $backup_mode = 'userzip';

        $web_config = $app->getconf->get_server_config($server_id, 'web');
        $http_server_user = $web_config['user'];

        if (empty($backup_dir)) {
            $app->log('Failed to make backup of web files for domain id ' . $domain_id . ' on server id ' . $server_id . ', because backup directory is not defined', LOGLEVEL_ERROR);
            return false;
        }
        if (empty($backup_job))
            $backup_job = "auto";

        $backup_format_web = $web_domain['backup_format_web'];
        //Check if we're working with data saved in old version of ISPConfig
        if (empty($backup_format_web)) {
            $backup_format_web = 'default';
        }
        if ($backup_format_web == 'default') {
            $backup_format_web = self::getDefaultBackupFormat($backup_mode, 'web');
        }
        $password = ($web_domain['backup_encrypt'] == 'y') ? trim($web_domain['backup_password']) : '';
        $backup_extension_web = self::getBackupWebExtension($backup_format_web);
        if (empty($backup_extension_web)) {
            $app->log('Failed to make backup of web files, because of unknown backup format ' . $backup_format_web . ' for website ' . $web_domain['domain'], LOGLEVEL_ERROR);
            return false;
        }

        $web_path = $web_domain['document_root'];
        $web_user = $web_domain['system_user'];
        $web_group = $web_domain['system_group'];
        $web_id = $web_domain['domain_id'];

        self::prepare_backup_dir($server_id, $web_domain);
        $web_backup_dir = $backup_dir . '/web' . $web_id;

        $backup_excludes = array(escapeshellarg('./backup\*'));
        $b_excludes = explode(',', trim($web_domain['backup_excludes']));
        if (is_array($b_excludes) && !empty($b_excludes)) {
            foreach ($b_excludes as $b_exclude) {
                $b_exclude = trim($b_exclude);
                if ($b_exclude != '') {
                    array_push($backup_excludes, escapeshellarg($b_exclude));
                }
            }
        }

        $web_backup_file = ($backup_job == 'manual' ? 'manual-' : '') . 'web' . $web_id . '_' . date('Y-m-d_H-i') . $backup_extension_web;
        $full_filename = $web_backup_dir . '/' . $web_backup_file;
        if (self::runWebCompression($backup_format_web, $backup_excludes, $backup_mode, $web_path, $web_backup_dir, $web_backup_file, $web_user, $web_group, $http_server_user, $backup_tmp, $password)) {
            if (is_file($full_filename)) {
                $backup_username = ($global_config['backups_include_into_web_quota'] == 'y') ? $web_user : 'root';
                $backup_group = ($global_config['backups_include_into_web_quota'] == 'y') ? $web_group : 'root';
                chown($full_filename, $backup_username);
                chgrp($full_filename, $backup_group);
                chmod($full_filename, 0750);

                //Insert web backup record in database
                $file_size = filesize($full_filename);
                $sql = "INSERT INTO web_backup (server_id, parent_domain_id, backup_type, backup_mode, backup_format, tstamp, filename, filesize, backup_password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $app->db->query($sql, $server_id, $web_id, 'web', $backup_mode, $backup_format_web, time(), $web_backup_file, $file_size, $password);
                if ($app->db->dbHost != $app->dbmaster->dbHost)
                    $app->dbmaster->query($sql, $server_id, $web_id, 'web', $backup_mode, $backup_format_web, time(), $web_backup_file, $file_size, $password);
                unset($file_size);
                $app->log('Backup of web files for domain ' . $web_domain['domain'] . ' completed successfully to file ' . $full_filename, LOGLEVEL_DEBUG);
            } else {
                $app->log('Backup of web files for domain ' . $web_domain['domain'] . ' reported success, but the resulting file ' . $full_filename . ' not found.', LOGLEVEL_ERROR);
            }

        } else {
            if (is_file($full_filename))
                unlink($full_filename);
            $app->log('Backup of web files for domain ' . $web_domain['domain'] . ' failed using path ' . $web_path . ' failed.', LOGLEVEL_ERROR);
        }

        self::clearBackups($server_id, $web_id, intval($web_domain['backup_copies']), $web_backup_dir);
        return true;
    }

    /**
     * Creates and prepares a backup dir
     * @param int $server_id
     * @param array $domain_data
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    protected static function prepare_backup_dir($server_id, $domain_data)
    {
        global $app;

        $server_config = $app->getconf->get_server_config($server_id, 'server');
        $global_config = $app->getconf->get_global_config('sites');

        if (isset($server_config['backup_dir_ftpread']) && $server_config['backup_dir_ftpread'] == 'y') {
            $backup_dir_permissions = 0755;
        } else {
            $backup_dir_permissions = 0750;
        }

        $backup_dir = $server_config['backup_dir'];

        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, $backup_dir_permissions, true);
        } else {
            chmod($backup_dir, $backup_dir_permissions);
        }

        $web_backup_dir = $backup_dir . '/web' . $domain_data['domain_id'];
        if (!is_dir($web_backup_dir))
            mkdir($web_backup_dir, 0750);
        chmod($web_backup_dir, 0750);

        $backup_username = 'root';
        $backup_group = 'root';

        if ($global_config['backups_include_into_web_quota'] == 'y') {
            $backup_username = $domain_data['system_user'];
            $backup_group = $domain_data['system_group'];
        }
        chown($web_backup_dir, $backup_username);
        chgrp($web_backup_dir, $backup_group);
    }

    /**
     * Makes a backup of website files or database.
     * @param string|int $domain_id
     * @param string $type backup type: web or mysql
     * @param string $backup_job how the backup is initiated: manual or auto
     * @param bool $mount if true, then the backup dir will be mounted and unmounted automatically
     * @return bool returns true if success
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    public static function run_backup($domain_id, $type, $backup_job, $mount = true)
    {
        global $app;

        $domain_id = intval($domain_id);

        $sql = "SELECT * FROM web_domain WHERE (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias') AND domain_id = ?";
        $rec = $app->db->queryOneRecord($sql, $domain_id);
        if (empty($rec)) {
            $app->log('Failed to make backup of type ' . $type . ', because no information present about requested domain id ' . $domain_id, LOGLEVEL_ERROR);
            return false;
        }
        $server_id = intval($rec['server_id']);

        if ($mount && !self::mount_backup_dir($server_id)) {
            $app->log('Failed to make backup of type ' . $type . ' for domain id ' . $domain_id . ', because failed to mount backup directory', LOGLEVEL_ERROR);
            return false;
        }
        $ok = false;

        switch ($type) {
            case 'web':
                $ok = self::make_web_backup($rec, $backup_job);
                break;
            case 'mysql':
                $ok = self::make_database_backup($rec, $backup_job);
                break;
            default:
                $app->log('Failed to make backup, because backup type is unknown: ' . $type, LOGLEVEL_ERROR);
                break;
        }
        if ($mount)
            self::unmount_backup_dir($server_id);
        return $ok;
    }

    /**
     * Runs backups of all websites that have backups enabled with respect to their backup interval settings
     * @param int $server_id
     * @param string $backup_job backup tupe: auto or manual
     * @author Ramil Valitov <ramilvalitov@gmail.com>
     */
    public static function run_all_backups($server_id, $backup_job = "auto")
    {
        global $app;

        $server_id = intval($server_id);

        $sql = "SELECT * FROM web_domain WHERE server_id = ? AND (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias') AND active = 'y' AND backup_interval != 'none' AND backup_interval != ''";
        $domains = $app->db->queryAllRecords($sql, $server_id);

        if (!self::mount_backup_dir($server_id)) {
            $app->log('Failed to run regular backups routine because failed to mount backup directory', LOGLEVEL_ERROR);
            return;
        }
        self::backups_garbage_collection($server_id);

        $date_of_week = date('w');
        $date_of_month = date('d');
        foreach ($domains as $domain) {
            if (($domain['backup_interval'] == 'daily' or ($domain['backup_interval'] == 'weekly' && $date_of_week == 0) or ($domain['backup_interval'] == 'monthly' && $date_of_month == '01'))) {
                self::run_backup($domain['domain_id'], 'web', $backup_job, false);
                self::run_backup($domain['domain_id'], 'mysql', $backup_job, false);
            }
        }
        self::unmount_backup_dir($server_id);
    }
}

?>

