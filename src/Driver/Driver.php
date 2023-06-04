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
    protected function getExecutable(string $type): string
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
    public function getExportExecutable(string $filename): string
    {
        $exec = $this->getExecutable('export');
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
    public function getImportExecutable(string $filename): string
    {
        $exec = $this->getExecutable('import');
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
}
