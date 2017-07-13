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
     * @since 2.1.0
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
     * @since 2.1.0
     */
    public function afterExport()
    {
    }

    /**
     * Called after import
     * @return void
     * @since 2.1.0
     */
    public function afterImport()
    {
    }

    /**
     * Called before export
     * @return bool Returns `false` to stop the export
     * @since 2.1.0
     */
    public function beforeExport()
    {
        return true;
    }

    /**
     * Called before import
     * @return bool Returns `false` to stop the import
     * @since 2.1.0
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

        if ($compression) {
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

        if ($compression) {
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
