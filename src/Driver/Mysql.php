<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * cakephp-database-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-database-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-database-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 * @since       2.0.0
 */
namespace DatabaseBackup\Driver;

use Cake\Network\Exception\InternalErrorException;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Driver;

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
     * @uses $config
     * @uses getCompression()
     */
    protected function getExportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $executable = sprintf('%s --defaults-file=%%s %s', $this->getBinary('mysqldump'), $this->config['database']);

        if (in_array($compression, array_filter(VALID_COMPRESSIONS))) {
            $executable .= ' | ' . $this->getBinary($compression);
        }

        return $executable . ' > ' . $filename . ' 2>/dev/null';
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
     * @uses $config
     */
    private function getExportStoreAuth()
    {
        $auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($auth, sprintf(
            "[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->config['username'],
            empty($this->config['password']) ? null : $this->config['password'],
            $this->config['host']
        ));

        return $auth;
    }

    /**
     * Gets the executable command to import the database
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @uses $config
     * @uses getCompression()
     */
    protected function getImportExecutable($filename)
    {
        $compression = $this->getCompression($filename);
        $executable = sprintf('%s --defaults-extra-file=%%s %s', $this->getBinary('mysql'), $this->config['database']);

        if (in_array($compression, array_filter(VALID_COMPRESSIONS))) {
            $executable = sprintf('%s -dc %s | ', $this->getBinary($compression), $filename) . $executable;
        } else {
            $executable .= ' < ' . $filename;
        }

        return $executable . ' 2>/dev/null';
    }

    /**
     * Stores the authentication data, to be used to import the database, in a
     *  temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @return string Path of the temporary file
     * @uses $config
     */
    private function getImportStoreAuth()
    {
        $auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($auth, sprintf(
            "[client]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->config['username'],
            empty($this->config['password']) ? null : $this->config['password'],
            $this->config['host']
        ));

        return $auth;
    }

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     * @throws InternalErrorException
     * @uses $connection
     * @uses getExportExecutable()
     * @uses getExportStoreAuth()
     */
    public function export($filename)
    {
        //Stores the authentication data in a temporary file
        $auth = $this->getExportStoreAuth();

        //Executes
        exec(sprintf($this->getExportExecutable($filename), $auth), $output, $returnVar);

        //Deletes the temporary file with the authentication data
        unlink($auth);

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('database_backup', '{0} failed with exit code `{1}`', 'mysqldump', $returnVar));
        }

        return file_exists($filename);
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @throws InternalErrorException
     * @uses $connection
     * @uses getImportExecutable()
     * @uses getImportStoreAuth()
     */
    public function import($filename)
    {
        //Stores the authentication data in a temporary file
        $auth = $this->getImportStoreAuth();

        //Executes
        exec(sprintf($this->getImportExecutable($filename), $auth), $output, $returnVar);

        //Deletes the temporary file with the authentication data
        unlink($auth);

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('database_backup', '{0} failed with exit code `{1}`', 'mysql', $returnVar));
        }

        return true;
    }
}
