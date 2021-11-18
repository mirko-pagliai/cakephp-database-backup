<?php
declare(strict_types=1);

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

use DatabaseBackup\Driver\Driver;

/**
 * Postgres driver to export/import database backups
 */
class Postgres extends Driver
{
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
     */
    protected function getDbnameAsString(): string
    {
        return sprintf(
            'postgresql://%s%s@%s/%s',
            $this->getConfig('username'),
            $this->getConfig('password') ? ':' . $this->getConfig('password') : '',
            $this->getConfig('host'),
            $this->getConfig('database')
        );
    }

    /**
     * Gets the executable command to export the database
     * @return string
     */
    protected function _exportExecutable(): string
    {
        return sprintf('%s --format=c -b --dbname=%s', $this->getBinary('pg_dump'), escapeshellarg($this->getDbnameAsString()));
    }

    /**
     * Gets the executable command to import the database
     * @return string
     */
    protected function _importExecutable(): string
    {
        return sprintf('%s --format=c -c -e --dbname=%s', $this->getBinary('pg_restore'), escapeshellarg($this->getDbnameAsString()));
    }
}
