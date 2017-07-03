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

/**
 * Represents a driver containing all methods to export/import database backups
 *  according to the database engine
 */
abstract class Driver
{
    /**
     * A connection object
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * Database configuration
     * @var array
     */
    protected $config;

    /**
     * Construct
     * @param \Cake\Datasource\ConnectionInterface $connection A connection object
     * @uses $config
     * @uses $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->config = $connection->config();
    }

    /**
     * Gets the executable command to export the database
     * @return string
     */
    abstract protected function _exportExecutable();

    /**
     * Gets the executable command to import the database
     * @return string
     */
    abstract protected function _importExecutable();

    /**
     * Called after export
     * @return void
     */
    public function afterExport()
    {
    }

    /**
     * Called after import
     * @return void
     */
    public function afterImport()
    {
    }

    /**
     * Called before export
     * @return bool Returns `false` to stop the export
     */
    public function beforeExport()
    {
        return true;
    }

    /**
     * Called before import
     * @return bool Returns `false` to stop the import
     */
    public function beforeImport()
    {
        return true;
    }

    /**
     * Gets the executable command to export the database, with compression
     * @param string $filename Filename where you want to export the database
     * @return string
     * @uses _exportExecutable()
     */
    protected function _exportExecutableWithCompression($filename)
    {
        $executable = $this->_exportExecutable();
        $compression = $this->getCompression($filename);

        if (in_array($compression, array_filter(VALID_COMPRESSIONS))) {
            $executable .= ' | ' . $this->getBinary($compression);
        }

        return $executable . ' > ' . $filename . ' 2>/dev/null';
    }

    /**
     * Gets the executable command to import the database, with compression
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @uses _importExecutable()
     */
    protected function _importExecutableWithCompression($filename)
    {
        $executable = $this->_importExecutable();
        $compression = $this->getCompression($filename);

        if (in_array($compression, array_filter(VALID_COMPRESSIONS))) {
            $executable = sprintf('%s -dc %s | ', $this->getBinary($compression), $filename) . $executable;
        } else {
            $executable .= ' < ' . $filename;
        }

        return $executable . ' 2>/dev/null';
    }

    /**
     * Exports the database
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     * @throws InternalErrorException
     * @uses _exportExecutableWithCompression()
     * @uses afterExport()
     * @uses beforeExport()
     */
    final public function export($filename)
    {
        if (!$this->beforeExport()) {
            return false;
        }

        exec($this->_exportExecutableWithCompression($filename), $output, $returnVar);

        $this->afterExport();

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('database_backup', 'Failed with exit code `{0}`', $returnVar));
        }

        return file_exists($filename);
    }

    /**
     * Imports the database
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @throws InternalErrorException
     * @uses _importExecutableWithCompression()
     * @uses afterImport()
     * @uses beforeImport()
     */
    final public function import($filename)
    {
        if (!$this->beforeImport()) {
            return false;
        }

        exec($this->_importExecutableWithCompression($filename), $output, $returnVar);

        $this->afterImport();

        if ($returnVar !== 0) {
            throw new InternalErrorException(__d('database_backup', 'Failed with exit code `{0}`', $returnVar));
        }

        return true;
    }
}
