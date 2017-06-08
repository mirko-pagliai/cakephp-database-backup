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
 * @since       2.0.0
 */
namespace MysqlBackup\Driver;

use Cake\Core\Configure;
use Cake\Network\Exception\InternalErrorException;
use MysqlBackup\BackupTrait;
use MysqlBackup\Driver\Driver;

/**
 * Mysql driver to export/import database backups
 */
class Mysql extends Driver
{
    use BackupTrait;

    /**
     * Gets the executable command to export the database
     * @param string $filename Filename where you want to export the database
     * @return string
     * @uses getValidCompressions()
     * @throws InternalErrorException
     */
    protected function getExportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $mysqldump = Configure::read(MYSQL_BACKUP . '.bin.mysqldump');

        if (in_array($compression, array_filter($this->getValidCompressions()))) {
            $executable = Configure::read(sprintf(MYSQL_BACKUP . '.bin.%s', $compression));

            if (!$executable) {
                throw new InternalErrorException(__d('mysql_backup', '`{0}` executable not available', $compression));
            }

            return sprintf('%s --defaults-file=%%s %%s | %s > %%s', $mysqldump, $executable);
        }

        //No compression
        return sprintf('%s --defaults-file=%%s %%s > %%s', $mysqldump);
    }

    /**
     * Stores the authentication data, to be used to export the database, in a
     *  temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @return string Path of the temporary file
     * @uses $connection
     */
    private function getExportStoreAuth()
    {
        $auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($auth, sprintf(
            "[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->connection['username'],
            empty($this->connection['password']) ? null : $this->connection['password'],
            $this->connection['host']
        ));

        return $auth;
    }

    /**
     * Gets the executable command to import the database
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @throws InternalErrorException
     */
    protected function getImportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
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
     * Stores the authentication data, to be used to import the database, in a
     *  temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @uses $connection
     * @return string Path of the temporary file
     */
    private function getImportStoreAuth()
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
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     * @uses $connection
     * @uses getExportExecutable()
     * @uses getExportStoreAuth()
     */
    public function export($filename)
    {
        //Stores the authentication data in a temporary file
        $auth = $this->getExportStoreAuth();

        //Executes
        exec(sprintf($this->getExportExecutable($filename), $auth, $this->connection['database'], $filename));

        //Deletes the temporary file with the authentication data
        unlink($auth);

        return file_exists($filename);
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @uses $connection
     * @uses getImportExecutable()
     * @uses getImportStoreAuth()
     */
    public function import($filename)
    {
        //Stores the authentication data in a temporary file
        $auth = $this->getImportStoreAuth();

        //Executes
        exec(sprintf($this->getImportExecutable($filename), $filename, $auth, $this->connection['database']));

        //Deletes the temporary file with the authentication data
        unlink($auth);

        return true;
    }
}
