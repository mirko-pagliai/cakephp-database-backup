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
use Cake\Database\Connection;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use DatabaseBackup\BackupTrait;
use Symfony\Component\Process\Process;
use Tools\Exceptionist;

/**
 * Represents a driver containing all methods to export/import database backups according to the connection
 * @method \Cake\Event\EventManager getEventManager()
 */
abstract class Driver implements EventListenerInterface
{
    use BackupTrait;
    use EventDispatcherTrait;

    /**
     * @var \Cake\Database\Connection
     */
    protected Connection $connection;

    /**
     * Constructor
     * @param \Cake\Database\Connection $connection A `Connection` instance
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        //Attaches the object to the event manager
        $this->getEventManager()->on($this);
    }

    /**
     * List of events this object is implementing. When the class is registered in an event manager, each individual
     *  method will be associated with the respective event
     * @return array<string, string> Associative array or event key names pointing to the function that should be called
     *  in the object when the respective event is fired
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
     * Internal method to run and get a `Process` instance as a command-line to be run in a shell wrapper.
     * @param string $command The command line to pass to the shell of the OS
     * @return \Symfony\Component\Process\Process
     * @since 2.8.7
     */
    protected function _exec(string $command): Process
    {
        $Process = Process::fromShellCommandline($command);
        $Process->run();

        return $Process;
    }

    /**
     * Gets and parses executable commands from the configuration, according to the type of requested operation
     *  (`export` or `import`) and the connection driver.
     *
     * These executables are not yet final, use instead `_getExportExecutable()` and `_getImportExecutable()` methods to
     *  have the final executables, including compression.
     * @param string $type Type or the request operation (`export` or `import`)
     * @return string
     * @throws \Tools\Exception\NotInArrayException
     * @throws \ReflectionException
     * @throws \ErrorException
     */
    protected function _getExecutable(string $type): string
    {
        Exceptionist::inArray($type, ['export', 'import']);
        $driver = strtolower(self::getDriverName());
        $replacements = [
            '{{BINARY}}' => escapeshellarg($this->getBinary(DATABASE_BACKUP_EXECUTABLES[$driver][$type])),
            '{{AUTH_FILE}}' => method_exists($this, 'getAuthFile') && $this->getAuthFile() ? escapeshellarg($this->getAuthFile()) : '',
            '{{DB_USER}}' => $this->getConfig('username'),
            '{{DB_PASSWORD}}' => $this->getConfig('password') ? ':' . $this->getConfig('password') : '',
            '{{DB_HOST}}' => $this->getConfig('host'),
            '{{DB_NAME}}' => $this->getConfig('database'),
        ];
        $exec = Configure::readOrFail('DatabaseBackup.' . $driver . '.' . $type);

        return str_replace(array_keys($replacements), $replacements, $exec);
    }

    /**
     * Gets the executable command to export the database, with compression if requested
     * @param string $filename Filename where you want to export the database
     * @return string
     * @throws \Tools\Exception\NotInArrayException
     * @throws \ReflectionException
     * @throws \ErrorException
     */
    protected function _getExportExecutable(string $filename): string
    {
        $exec = $this->_getExecutable('export');
        $compression = self::getCompression($filename);
        if ($compression) {
            $exec .= ' | ' . escapeshellarg($this->getBinary($compression));
        }

        return $exec . ' > ' . escapeshellarg($filename);
    }

    /**
     * Gets the executable command to import the database, with compression if requested
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @throws \Tools\Exception\NotInArrayException
     * @throws \ReflectionException
     * @throws \ErrorException
     */
    protected function _getImportExecutable(string $filename): string
    {
        $exec = $this->_getExecutable('import');
        $compression = self::getCompression($filename);
        if ($compression) {
            return sprintf('%s -dc %s | ', escapeshellarg($this->getBinary($compression)), escapeshellarg($filename)) . $exec;
        }

        return $exec . ' < ' . escapeshellarg($filename);
    }

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
    public function beforeExport(): bool
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
     * Gets a binary path
     * @param string $name Binary name
     * @return string
     * @throws \ErrorException
     */
    final public function getBinary(string $name): string
    {
        return Exceptionist::isTrue(Configure::read('DatabaseBackup.binaries.' . $name), 'Binary for `' . $name . '` could not be found. You have to set its path manually');
    }

    /**
     * Gets a config value or the whole configuration of the connection
     * @param string|null $key Config key or `null` to get all config values
     * @return mixed Config value, `null` if the key doesn't exist or all config values if no key was specified
     * @since 2.3.0
     */
    final public function getConfig(?string $key = null)
    {
        $config = $this->connection->config();

        return $key ? $config[$key] ?? null : $config;
    }

    /**
     * Exports the database.
     *
     * When exporting, this method will trigger these events:
     *  - `Backup.beforeExport`: will be triggered before export;
     *  - `Backup.afterExport`: will be triggered after export.
     * @param string $filename Filename where you want to export the database
     * @return bool `true` on success
     * @throws \Exception
     */
    final public function export(string $filename): bool
    {
        $beforeExport = $this->dispatchEvent('Backup.beforeExport');
        if ($beforeExport->isStopped()) {
            return false;
        }

        $process = $this->_exec($this->_getExportExecutable($filename));
        Exceptionist::isTrue($process->isSuccessful(), __d('database_backup', 'Export failed with error message: `{0}`', rtrim($process->getErrorOutput())));

        $this->dispatchEvent('Backup.afterExport');

        return file_exists($filename);
    }

    /**
     * Imports the database.
     *
     * When importing, this method will trigger these events:
     *  - `Backup.beforeImport`: will be triggered before import;
     *  - `Backup.afterImport`: will be triggered after import.
     * @param string $filename Filename from which you want to import the database
     * @return bool true on success
     * @throws \Tools\Exception\NotInArrayException
     * @throws \ReflectionException
     * @throws \ErrorException
     */
    final public function import(string $filename): bool
    {
        $beforeImport = $this->dispatchEvent('Backup.beforeImport');
        if ($beforeImport->isStopped()) {
            return false;
        }

        $process = $this->_exec($this->_getImportExecutable($filename));
        Exceptionist::isTrue($process->isSuccessful(), __d('database_backup', 'Import failed with error message: `{0}`', rtrim($process->getErrorOutput())));

        $this->dispatchEvent('Backup.afterImport');

        return true;
    }
}
