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

use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Driver;

/**
 * Mysql driver to export/import database backups
 */
class Mysql extends Driver
{
    use BackupTrait;

    /**
     * Temporary file with the database authentication data
     * @var string
     */
    protected $auth;

    /**
     * Gets the executable command to export the database
     * @return string
     * @uses $auth
     * @uses $config
     */
    protected function _exportExecutable()
    {
        return sprintf('%s --defaults-file=%s %s', $this->getBinary('mysqldump'), $this->auth, $this->config['database']);
    }

    /**
     * Gets the executable command to import the database
     * @return string
     * @uses $auth
     * @uses $config
     */
    protected function _importExecutable()
    {
        return sprintf('%s --defaults-extra-file=%s %s', $this->getBinary('mysql'), $this->auth, $this->config['database']);
    }

    /**
     * Called after export
     * @return void
     * @uses deleteAuthFile()
     */
    public function afterExport()
    {
        $this->deleteAuthFile();
    }

    /**
     * Called after import
     * @return void
     * @uses deleteAuthFile()
     */
    public function afterImport()
    {
        $this->deleteAuthFile();
    }

    /**
     * Called before export.
     *
     * It stores the authentication data, to be used to export the database, in
     *  a temporary file.
     *
     * For security reasons, it's recommended to specify the password in a
     *  configuration file and not in the command (a user can execute a
     *  `ps aux | grep mysqldump` and see the password).
     * So it creates a temporary file to store the configuration options
     * @return void
     * @uses $auth
     * @uses $config
     */
    public function beforeExport()
    {
        $this->auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($this->auth, sprintf(
            "[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->config['username'],
            empty($this->config['password']) ? null : $this->config['password'],
            $this->config['host']
        ));
    }

    /**
     * Called before export.
     *
     * It stores the authentication data, to be used to import the database, in
     *  a temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @return void
     * @uses $auth
     * @uses $config
     */
    public function beforeImport()
    {
        $this->auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($this->auth, sprintf(
            "[client]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->config['username'],
            empty($this->config['password']) ? null : $this->config['password'],
            $this->config['host']
        ));
    }

    /**
     * Deletes the temporary file with the database authentication data
     * @return bool `true` on success
     * @uses $auth
     */
    protected function deleteAuthFile()
    {
        //Deletes the temporary file with the authentication data
        if ($this->auth && file_exists($this->auth)) {
            unlink($this->auth);
            unset($this->auth);

            return true;
        }

        return false;
    }
}
