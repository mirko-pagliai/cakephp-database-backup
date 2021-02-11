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
 * @since       2.0.0
 */
namespace DatabaseBackup\Driver;

use Cake\Core\Configure;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use DatabaseBackup\BackupTrait;
use Tools\Exceptionist;

/**
 * Represents a driver containing all methods to export/import database backups
 *  according to the database engine
 */
abstract class Driver implements EventListenerInterface
{
    use BackupTrait;
    use EventDispatcherTrait;

    /**
     * A connection object
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * Construct
     * @param \Cake\Datasource\ConnectionInterface $connection A connection object
     * @uses $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;

        //Attachs the object to the event manager
        $this->getEventManager()->on($this);
    }

    /**
     * List of events this object is implementing. When the class is registered
     *  in an event manager, each individual method will be associated with the
     *  respective event
     * @return array Associative array or event key names pointing to the
     *  function that should be called in the object when the respective event
     *  is fired
     * @since 2.1.1
     */
    final public function implementedEvents(): array
    {
        return [
            'Backup.afterExport' => 'afterExport',
            'Backup.afterImport' => 'afterImport',
            'Backup.beforeExport' => 'beforeExport',
            'Backup.beforeImport' => 'beforeImport',
        ];
    }

    /**
     * Gets the executable command to export the database
     * @return string
     */
    abstract protected function _exportExecutable(): string;

    /**
     * Gets the executable command to import the database
     * @return string
     */
    abstract protected function _importExecutable(): string;

    /**
     * Called after export
     * @return void
     * @since 2.1.0
     */
    public function afterExport(): void
    {
    }

    /**
     * Called after import
     * @return void
     * @since 2.1.0
     */
    public function afterImport(): void
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
    public function beforeImport(): bool
    {
        return true;
    }

    /**
     * Gets the executable command to export the database, with compression
     * @param string $filename Filename where you want to export the database
     * @return string
     * @uses _exportExecutable()
     * @uses getBinary()
     */
    protected function _exportExecutableWithCompression(string $filename): string
    {
        $executable = $this->_exportExecutable();
        $compression = $this->getCompression($filename);

        if ($compression) {
            $executable .= ' | ' . $this->getBinary($compression);
        }
        $executable .= ' > ' . escapeshellarg($filename);

        if (Configure::read('DatabaseBackup.redirectStderrToDevNull')) {
            $executable .= REDIRECT_TO_DEV_NULL;
        }

        return $executable;
    }

    /**
     * Gets the executable command to import the database, with compression
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @uses _importExecutable()
     * @uses getBinary()
     */
    protected function _importExecutableWithCompression(string $filename): string
    {
        $compression = $this->getCompression($filename);
        $filename = escapeshellarg($filename);

        $executable = $this->_importExecutable() . ' < ' . $filename;
        if ($compression) {
            $executable = sprintf('%s -dc %s | ', $this->getBinary($compression), $filename) . $this->_importExecutable();
        }

        if (Configure::read('DatabaseBackup.redirectStderrToDevNull')) {
            $executable .= REDIRECT_TO_DEV_NULL;
        }

        return $executable;
    }

    /**
     * Exports the database.
     *
     * When exporting, this method will trigger these events:
     *
     * - Backup.beforeExport: will be triggered before export
     * - Backup.afterExport: will be triggered after export
     * @param string $filename Filename where you want to export the database
     * @return bool true on success
     * @throws \Exception
     * @uses _exportExecutableWithCompression()
     */
    final public function export(string $filename): bool
    {
        $beforeExport = $this->dispatchEvent('Backup.beforeExport');
        if ($beforeExport->isStopped()) {
            return false;
        }

        exec($this->_exportExecutableWithCompression($filename), $output, $returnVar);
        Exceptionist::isTrue($returnVar === 0, __d('database_backup', 'Failed with exit code `{0}`', $returnVar));

        $this->dispatchEvent('Backup.afterExport');

        return file_exists($filename);
    }

    /**
     * Gets a binary path
     * @param string $name Binary name
     * @return string
     * @throws \ErrorException
     */
    public function getBinary($name)
    {
        $binary = Configure::read('DatabaseBackup.binaries.' . $name);

        return Exceptionist::isTrue($binary, sprintf('Binary for `%s` could not be found. You have to set its path manually', $name));
    }

    /**
     * Gets a config value or the whole configuration
     * @param string|null $key Config key or `null` to get all config values
     * @return mixed Config value, `null` if the key doesn't exist
     *  or all config values if no key was specified
     * @since 2.3.0
     * @uses $connection
     */
    final public function getConfig(?string $key = null)
    {
        $config = $this->connection->config();

        return $key ? (array_key_exists($key, $config) ? $config[$key] : null) : $config;
    }

    /**
     * Imports the database.
     *
     * When importing, this method will trigger these events:
     *
     * - Backup.beforeImport: will be triggered before import
     * - Backup.afterImport: will be triggered after import
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @throws \Exception
     * @uses _importExecutableWithCompression()
     */
    final public function import(string $filename): bool
    {
        $beforeImport = $this->dispatchEvent('Backup.beforeImport');
        if ($beforeImport->isStopped()) {
            return false;
        }

        exec($this->_importExecutableWithCompression($filename), $output, $returnVar);
        Exceptionist::isTrue($returnVar === 0, __d('database_backup', 'Failed with exit code `{0}`', $returnVar));

        $this->dispatchEvent('Backup.afterImport');

        return true;
    }
}
