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
 * Represents a driver containing all methods to export/import database backups
 *  according to the connection
 * @method \Cake\Event\EventManager getEventManager()
 */
abstract class Driver implements EventListenerInterface
{
    use BackupTrait;
    use EventDispatcherTrait;

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * Construct
     * @param \Cake\Database\Connection $connection Connection instance
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        //Attachs the object to the event manager
        $this->getEventManager()->on($this);
    }

    /**
     * List of events this object is implementing. When the class is registered
     *  in an event manager, each individual method will be associated with the
     *  respective event
     * @return array<string, string> Associative array or event key names pointing
     *  to the function that should be called in the object when the respective
     *  event is fired
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
     * Internal method to execute an external program
     * @param string $command The command that will be executed
     * @return \Symfony\Component\Process\Process
     * @since 2.8.7
     */
    protected function _exec(string $command): Process
    {
        $process = Process::fromShellCommandline($command);
        $process->run();

        return $process;
    }

    /**
     * Parses the executable command, replacing placeholders
     * @param string $executable The executable command, with placeholders
     * @param string $binary The name of the binary to use
     * @return string The executable command, with the placeholders replaced
     */
    protected function _parseExecutable(string $executable, string $binary): string
    {
        $replacement = [
            '{{BINARY}}' => escapeshellarg($this->getBinary($binary)),
            '{{AUTH_FILE}}' => isset($this->auth) ? escapeshellarg($this->auth) : '',
            '{{DB_USER}}' => $this->getConfig('username'),
            '{{DB_PASSWORD}}' => $this->getConfig('password') ? ':' . $this->getConfig('password') : '',
            '{{DB_HOST}}' => $this->getConfig('host'),
            '{{DB_NAME}}' => $this->getConfig('database'),
        ];

        return str_replace(array_keys($replacement), $replacement, $executable);
    }

    /**
     * Gets the executable command to export the database, with compression if requested
     * @param string $filename Filename where you want to export the database
     * @return string
     */
    protected function _exportExecutable(string $filename): string
    {
        $driver = strtolower($this->getDriverName());
        $binary = DATABASE_BACKUP_EXECUTABLES[$driver][0];
        $exec = $this->_parseExecutable(Configure::read('DatabaseBackup.' . $driver . '.export'), $binary);

        $compression = $this->getCompression($filename);
        if ($compression) {
            $exec .= ' | ' . escapeshellarg($this->getBinary($compression));
        }

        return $exec . ' > ' . escapeshellarg($filename);
    }

    /**
     * Gets the executable command to import the database, with compression if requested
     * @param string $filename Filename from which you want to import the database
     * @return string
     */
    protected function _importExecutable(string $filename): string
    {
        $driver = strtolower($this->getDriverName());
        $binary = DATABASE_BACKUP_EXECUTABLES[$driver][1];
        $exec = $this->_parseExecutable(Configure::read('DatabaseBackup.' . $driver . '.import'), $binary);

        $compression = $this->getCompression($filename);
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
     * Exports the database.
     *
     * When exporting, this method will trigger these events:
     *
     * - Backup.beforeExport: will be triggered before export
     * - Backup.afterExport: will be triggered after export
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

        $process = $this->_exec($this->_exportExecutable($filename));
        Exceptionist::isTrue($process->isSuccessful(), __d('database_backup', 'Export failed with error message: `{0}`', rtrim($process->getErrorOutput())));

        $this->dispatchEvent('Backup.afterExport');

        return file_exists($filename);
    }

    /**
     * Gets a binary path
     * @param string $name Binary name
     * @return string
     * @throws \ErrorException
     */
    final public function getBinary(string $name): string
    {
        return Exceptionist::isTrue(Configure::read('DatabaseBackup.binaries.' . $name), sprintf('Binary for `%s` could not be found. You have to set its path manually', $name));
    }

    /**
     * Gets a config value or the whole configuration of the connection
     * @param string|null $key Config key or `null` to get all config values
     * @return mixed Config value, `null` if the key doesn't exist
     *  or all config values if no key was specified
     * @since 2.3.0
     */
    final public function getConfig(?string $key = null)
    {
        $config = $this->connection->config();

        return $key ? $config[$key] ?? null : $config;
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
     */
    final public function import(string $filename): bool
    {
        $beforeImport = $this->dispatchEvent('Backup.beforeImport');
        if ($beforeImport->isStopped()) {
            return false;
        }

        $process = $this->_exec($this->_importExecutable($filename));
        Exceptionist::isTrue($process->isSuccessful(), __d('database_backup', 'Import failed with error message: `{0}`', rtrim($process->getErrorOutput())));

        $this->dispatchEvent('Backup.afterImport');

        return true;
    }
}
