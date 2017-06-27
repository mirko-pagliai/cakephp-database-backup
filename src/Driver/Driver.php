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

/**
 * Represents a driver containing all methods to export/import database backups
 *  according to the database engine
 */
abstract class Driver
{
    use BackupTrait;

    /**
     * Database configuration
     * @var array
     */
    protected $config;

    /**
     * Construct
     * @param \Cake\Datasource\ConnectionInterface $connection A connection object
     * @uses $config
     */
    public function __construct($connection)
    {
        $this->config = $connection->config();
    }

    /**
     * Drops tables.
     *
     * Some drivers (eg. Sqlite) are not able to drop tables before import a
     *  backup file. For this reason, it may be necessary to run it manually.
     * @return void
     * @uses getTables()
     */
    final public function dropTables()
    {
        foreach ($this->getTables() as $table) {
            $this->getConnection()->execute(sprintf('DROP TABLE %s;', $table));
        }
    }

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     */
    abstract public function export($filename);

    /**
     * Gets the executable command to export the database
     * @param string $filename Filename where you want to export the database
     * @return string
     */
    abstract protected function getExportExecutable($filename);

    /**
     * Gets the executable command to import the database
     * @param string $filename Filename from which you want to import the database
     * @return string
     */
    abstract protected function getImportExecutable($filename);

    /**
     * Gets all tables of the current database
     * @return array
     */
    final public function getTables()
    {
        return $this->getConnection()->getSchemaCollection()->listTables();
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     */
    abstract public function import($filename);
}
