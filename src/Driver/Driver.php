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
use Cake\Datasource\ConnectionManager;
use MysqlBackup\BackupTrait;

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
     * Default extension for export
     * @var string
     */
    protected $defaultExtension;

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
     * Deletes all the records of all the tables.
     *
     * Some drivers (eg. Sqlite) are not able to drop tables before import a
     *  backup file. For this reason, it may be necessary to delete all records
     *  first.
     * @return void
     * @uses getTables()
     */
    public function deleteAllRecords()
    {
        $connection = ConnectionManager::get(Configure::read(MYSQL_BACKUP . '.connection'));

        foreach ($this->getTables() as $table) {
            $connection->delete($table);
        }
    }

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     */
    abstract public function export($filename);

    /**
     * Returns the compression type from a filename
     * @param string $filename Filename
     * @return string|bool|null Compression type as string, `null` on failure,
     *  `false` for no compression
     * @uses getExtension()
     * @uses getValidCompressions()
     */
    public function getCompression($filename)
    {
        $compressions = $this->getValidCompressions();

        //Gets the extension from the filename
        $extension = $this->getExtension($filename);

        if (!array_key_exists($extension, $compressions)) {
            return null;
        }

        return $compressions[$extension];
    }

    /**
     * Returns the default extension for export
     * @return string
     */
    public function getDefaultExtension()
    {
        return $this->defaultExtension;
    }

    /**
     * Gets the executable command to export the database
     * @param string $filename Filename where you want to export the database
     * @return string
     */
    abstract protected function getExportExecutable($filename);

    /**
     * Returns the extension of a filename
     * @param string $filename Filename
     * @return string|null Extension or `null` on failure
     * @uses getValidExtensions()
     */
    public function getExtension($filename)
    {
        $regex = sprintf('/\.(%s)$/', implode('|', array_map('preg_quote', $this->getValidExtensions())));

        if (preg_match($regex, $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

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
    public function getTables()
    {
        $connection = ConnectionManager::get(Configure::read(MYSQL_BACKUP . '.connection'));

        return $connection->getSchemaCollection()->listTables();
    }

    /**
     * Returns valid compression types for this driver
     * @return array
     */
    public function getValidCompressions()
    {
        return VALID_COMPRESSIONS[$this->getClassShortName(get_called_class())];
    }

    /**
     * Returns valid extensions for this driver
     * @return array
     */
    public function getValidExtensions()
    {
        return VALID_EXTENSIONS[$this->getClassShortName(get_called_class())];
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     */
    abstract public function import($filename);
}
