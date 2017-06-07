<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 * @see         https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupImport-utility
 */
namespace MysqlBackup\Utility;

use Cake\Core\Configure;
use Cake\Network\Exception\InternalErrorException;
use MysqlBackup\BackupTrait;

/**
 * Utility to import databases
 */
class BackupImport
{
    use BackupTrait;

    /**
     * Compression type
     * @var bool|string|null
     */
    protected $compression = null;

    /**
     * Database connection
     * @var array
     */
    protected $connection;

    /**
     * Filename where to import the database
     * @var string
     */
    protected $filename;

    /**
     * Construct
     * @uses $connection
     */
    public function __construct()
    {
        $this->connection = $this->getConnection();
    }

    /**
     * Gets the executable command
     * @param bool|string $compression Compression. Supported values are
     *  `bzip2`, `gzip` and `false` (if you don't want to use compression)
     * @return string
     * @throws InternalErrorException
     */
    protected function _getExecutable($compression)
    {
        $mysql = Configure::read(MYSQL_BACKUP . '.bin.mysql');

        if (in_array($compression, ['bzip2', 'gzip'])) {
            $executable = Configure::read(sprintf(MYSQL_BACKUP . '.bin.%s', $compression));

            if (!$executable) {
                throw new InternalErrorException(__d('mysql_backup', '`{0}` executable not available', $compression));
            }

            return sprintf('%s -dc %%s | %s --defaults-extra-file=%%s %%s', $executable, $mysql);
        }

        //No compression
        return sprintf('cat %%s | %s --defaults-extra-file=%%s %%s', $mysql);
    }

    /**
     * Stores the authentication data in a temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @uses $connection
     * @return string File path
     */
    private function _storeAuth()
    {
        $auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($auth, sprintf(
            "[client]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->connection['username'],
            empty($this->connection['password']) ? null : $this->connection['password'],
            $this->connection['host']
        ));

        return $auth;
    }

    /**
     * Sets the filename
     * @param string $filename Filename. It can be an absolute path
     * @return \MysqlBackup\Utility\BackupImport
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupImport-utility#filename
     * @throws InternalErrorException
     * @uses $compression
     * @uses $filename
     */
    public function filename($filename)
    {
        $filename = $this->getAbsolutePath($filename);

        if (!is_readable($filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'File or directory `{0}` not readable', $filename));
        }

        $compression = $this->getCompression($filename);

        if (!in_array($compression, $this->getValidCompressions(), true)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid compression type'));
        }

        $this->compression = $compression;
        $this->filename = $filename;

        return $this;
    }

    /**
     * Imports the database
     * @return string Filename path
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupImport-utility#import
     * @throws InternalErrorException
     * @uses _getExecutable()
     * @uses _storeAuth()
     * @uses $compression
     * @uses $connection
     * @uses $filename
     */
    public function import()
    {
        if (empty($this->filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'Before you import a database, you have to set the filename'));
        }

        //This allows the filename to be set again with a next call of this
        //  method
        $filename = $this->filename;
        unset($this->filename);

        //Stores the authentication data in a temporary file
        $auth = $this->_storeAuth();

        //Executes
        exec(sprintf($this->_getExecutable($this->compression), $filename, $auth, $this->connection['database']));

        //Deletes the temporary file
        unlink($auth);

        return $filename;
    }
}
