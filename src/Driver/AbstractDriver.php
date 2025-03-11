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
use DatabaseBackup\Compression;
use DatabaseBackup\OperationType;
use InvalidArgumentException;

/**
 * Represents a driver containing all methods to export/import database backups according to the connection.
 *
 * @method \Cake\Event\EventManager getEventManager()
 */
abstract class AbstractDriver implements EventListenerInterface
{
    use BackupTrait;
    /**
     * @use \Cake\Event\EventDispatcherTrait<\DatabaseBackup\Driver\AbstractDriver>
     */
    use EventDispatcherTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        //Attaches the object to the event manager
        $this->getEventManager()->on($this);
    }

    /**
     * List of events this object is implementing. When the class is registered in an event manager, each individual
     *  method will be associated with the respective event.
     *
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
     * These executables are not yet final, use instead `getExportExecutable()` and `getImportExecutable()` methods to
     *  have the final executables, including compression.
     *
     * @param \DatabaseBackup\OperationType $OperationType
     * @return string
     */
    private function getExecutable(OperationType $OperationType): string
    {
        $driverName = lcfirst(substr(strrchr($this::class, "\\"), 1));

        $replacements = [
            '{{BINARY}}' => escapeshellarg($this->getBinary(DATABASE_BACKUP_EXECUTABLES[$driverName][$OperationType->value])),
            '{{AUTH_FILE}}' => method_exists($this, 'getAuthFilePath') && $this->getAuthFilePath() ? escapeshellarg($this->getAuthFilePath()) : '',
            '{{DB_USER}}' => $this->getConfig('username'),
            '{{DB_PASSWORD}}' => $this->getConfig('password') ? ':' . $this->getConfig('password') : '',
            '{{DB_HOST}}' => $this->getConfig('host'),
            '{{DB_NAME}}' => $this->getConfig('database'),
        ];
        /** @var string $exec */
        $exec = Configure::readOrFail('DatabaseBackup.' . $driverName . '.' . $OperationType->value);

        return str_replace(array_keys($replacements), $replacements, $exec);
    }

    /**
     * Gets the executable command to export the database, with compression if requested.
     *
     * @param string $filename Filename where you want to export the database
     * @return string
     * @throws \LogicException
     * @throws \ValueError With a filename that does not match any supported compression.
     */
    public function getExportExecutable(string $filename): string
    {
        $exec = $this->getExecutable(OperationType::Export);

        $Compression = Compression::fromFilename($filename);
        if ($Compression !== Compression::None) {
            $exec .= ' | ' . escapeshellarg($this->getBinary($Compression));
        }

        return $exec . ' > ' . escapeshellarg($filename);
    }

    /**
     * Gets the executable command to import the database, with compression if requested.
     *
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @throws \LogicException
     */
    public function getImportExecutable(string $filename): string
    {
        $exec = $this->getExecutable(OperationType::Import);

        $Compression = Compression::fromFilename($filename);
        if ($Compression !== Compression::None) {
            return sprintf(
                '%s -dc %s | ',
                escapeshellarg($this->getBinary($Compression)),
                escapeshellarg($filename)
            ) . $exec;
        }

        return $exec . ' < ' . escapeshellarg($filename);
    }

    /**
     * Called after export.
     *
     * @return void
     * @since 2.1.0
     */
    public function afterExport(): void
    {
    }

    /**
     * Called after import.
     *
     * @return void
     * @since 2.1.0
     */
    public function afterImport(): void
    {
    }

    /**
     * Called before export.
     *
     * @return bool Returns `false` to stop the export
     * @since 2.1.0
     */
    public function beforeExport(): bool
    {
        return true;
    }

    /**
     * Called before import.
     *
     * @return bool Returns `false` to stop the import
     * @since 2.1.0
     */
    public function beforeImport(): bool
    {
        return true;
    }

    /**
     * Gets a binary path.
     *
     * @param \DatabaseBackup\Compression|string $binaryName Binary name
     * @return string
     * @throws \LogicException
     */
    public function getBinary(Compression|string $binaryName): string
    {
        if ($binaryName instanceof Compression) {
            $binaryName = lcfirst($binaryName->name);
        }

        $binary = Configure::read('DatabaseBackup.binaries.' . $binaryName);
        if (!$binary) {
            throw new InvalidArgumentException(__d(
                'database_backup',
                'Binary for `{0}` could not be found. You have to set its path manually',
                $binaryName
            ));
        }

        return $binary;
    }

    /**
     * Gets a config value of the connection.
     *
     * @param string $key Config key
     * @return mixed Config value or `null` if the key doesn't exist
     * @since 2.3.0
     */
    protected function getConfig(string $key): mixed
    {
        return $this->getConnection()->config()[$key] ?? null;
    }
}
