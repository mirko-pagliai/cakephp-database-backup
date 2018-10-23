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
 * Sqlite driver to export/import database backups
 */
class Sqlite extends Driver
{
    use BackupTrait;

    /**
     * Gets the executable command to export the database
     * @return string
     * @uses getConfig()
     */
    protected function _exportExecutable()
    {
        return sprintf('%s %s .dump', $this->getBinary('sqlite3'), $this->getConfig('database'));
    }

    /**
     * Gets the executable command to import the database
     * @return string
     * @uses getConfig()
     */
    protected function _importExecutable()
    {
        return sprintf('%s %s', $this->getBinary('sqlite3'), $this->getConfig('database'));
    }

    /**
     * Called before import
     * @return bool
     * @since 2.1.0
     * @uses $connection
     */
    public function beforeImport()
    {
        $schemaCollection = $this->connection->getSchemaCollection();

        //Drops each table
        foreach ($schemaCollection->listTables() as $table) {
            array_map([$this->connection, 'execute'], $schemaCollection->describe($table)->dropSql($this->connection));
        }

        //Needs disconnect and re-connect because the database schema has changed
        $this->connection->disconnect();
        $this->connection->connect();

        return true;
    }
}
