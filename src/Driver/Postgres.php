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

use Cake\Core\Configure;
use DatabaseBackup\Driver\Driver;

/**
 * Postgres driver to export/import database backups
 */
class Postgres extends Driver
{
    /**
     * Gets the executable command to export the database
     * @return string
     */
    protected function _exportExecutable(): string
    {
        return str_replace([
            '{{BINARY}}',
            '{{DB_USER}}',
            '{{DB_PASSWORD}}',
            '{{DB_HOST}}',
            '{{DB_NAME}}',
        ], [
            escapeshellarg($this->getBinary('pg_dump')),
            $this->getConfig('username'),
            $this->getConfig('password') ? ':' . $this->getConfig('password') : '',
            $this->getConfig('host'),
            $this->getConfig('database'),
        ], Configure::read('DatabaseBackup.postgres.export'));
    }

    /**
     * Gets the executable command to import the database
     * @return string
     */
    protected function _importExecutable(): string
    {
        return str_replace([
            '{{BINARY}}',
            '{{DB_USER}}',
            '{{DB_PASSWORD}}',
            '{{DB_HOST}}',
            '{{DB_NAME}}',
        ], [
            escapeshellarg($this->getBinary('pg_restore')),
            $this->getConfig('username'),
            $this->getConfig('password') ? ':' . $this->getConfig('password') : '',
            $this->getConfig('host'),
            $this->getConfig('database'),
        ], Configure::read('DatabaseBackup.postgres.import'));
    }
}
