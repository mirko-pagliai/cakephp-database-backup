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
 * @since       2.1.0
 */
namespace DatabaseBackup\Driver;

use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Driver;

/**
 * Postgres driver to export/import database backups
 */
class Postgres extends Driver
{
    use BackupTrait;

    /**
     * Gets the value for the `--dbname` option for export and import
     *  executables as string. It contains the connection string with username,
     *  password and hostname.
     *
     * It returns something like:
     * <code>
     * postgresql://postgres@localhost/travis_ci_test
     * </code>
     * @return string
     * @uses getConfig()
     */
    protected function getDbnameAsString()
    {
        $password = $this->getConfig('password');

        if ($password) {
            $password = ':' . $password;
        }

        return sprintf(
            'postgresql://%s%s@%s/%s',
            $this->getConfig('username'),
            $password,
            $this->getConfig('host'),
            $this->getConfig('database')
        );
    }

    /**
     * Gets the executable command to export the database
     * @return string
     * @uses getDbnameAsString()
     */
    protected function _exportExecutable()
    {
        return sprintf('%s --format=c -b --dbname=%s', $this->getBinary('pg_dump'), $this->getDbnameAsString());
    }

    /**
     * Gets the executable command to import the database
     * @return string
     * @uses getDbnameAsString()
     */
    protected function _importExecutable()
    {
        return sprintf('%s --format=c -c -e --dbname=%s', $this->getBinary('pg_restore'), $this->getDbnameAsString());
    }
}
