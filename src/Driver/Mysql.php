<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-database-backup
 * @license     https://opensource.org/licenses/mit-license.php MIT License
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
     * @since 2.1.0
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
        return sprintf(
            '%s --defaults-file=%s %s',
            $this->getBinary('mysqldump'),
            escapeshellarg($this->auth),
            $this->config['database']
        );
    }

    /**
     * Gets the executable command to import the database
     * @return string
     * @uses $auth
     * @uses $config
     */
    protected function _importExecutable()
    {
        return sprintf(
            '%s --defaults-extra-file=%s %s',
            $this->getBinary('mysql'),
            escapeshellarg($this->auth),
            $this->config['database']
        );
    }

    /**
     * Called after export
     * @return void
     * @since 2.1.0
     * @uses deleteAuthFile()
     */
    public function afterExport()
    {
        $this->deleteAuthFile();
    }

    /**
     * Called after import
     * @return void
     * @since 2.1.0
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
     * @return bool
     * @since 2.1.0
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

        return true;
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
     * @return bool
     * @since 2.1.0
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

        return true;
    }

    /**
     * Deletes the temporary file with the database authentication data
     * @return bool `true` on success
     * @since 2.1.0
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
