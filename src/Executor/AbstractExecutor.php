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

namespace DatabaseBackup\Executor;

use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use DatabaseBackup\Compression;
use DatabaseBackup\OperationType;
use InvalidArgumentException;
use Override;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Represents an "Executor" class containing all methods to export/import database backups, according to the connection.
 *
 * "Executor" classes that extend this class must implement the `EXPORT_BYNARY` and `IMPORT_BINARY` constants, which can
 *  be strings or arrays of strings.
 *
 * @method \Cake\Event\EventManager getEventManager()
 */
abstract class AbstractExecutor implements EventListenerInterface
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\DatabaseBackup\Executor\AbstractExecutor>
     */
    use EventDispatcherTrait;

    /**
     * Constructor.
     *
     * @param \Cake\Datasource\ConnectionInterface $Connection
     */
    public function __construct(protected ConnectionInterface $Connection)
    {
        //Attaches the object to the event manager
        $this->getEventManager()->on($this);
    }

    /**
     * Magic `__call()` method.
     *
     * @param string $name
     * @param array $arguments
     * @return string
     * @todo to be removed in a future release
     * @phpstan-ignore missingType.iterableValue
     */
    public function __call(string $name, array $arguments): string
    {
        $replacements = [
            'getExportExecutable' => 'getExportCommand',
            'getImportExecutable' => 'getImportCommand',
        ];

        $replacement = $replacements[$name] ?? null;
        if ($replacement) {
            deprecationWarning('2.14.1', sprintf(
                'The `AbstractExecutor::%s()` method is deprecated and will be removed in a future release. Use instead `%s()`',
                $name,
                $replacement
            ));

            return $this->{$replacement}(...$arguments);
        }

        throw new BadMethodCallException('Method `' . $this::class . '::' . $name . '()` does not exist.');
    }

    /**
     * List of events this object is implementing. When the class is registered in an event manager, each individual
     *  method will be associated with the respective event.
     *
     * @return array<string, string> Associative array or event key names pointing to the function that should be called
     *  in the object when the respective event is fired
     * @since 2.1.1
     */
    #[Override]
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
     * Gets and parses commands from the configuration, according to the type of requested `OperationType` and the
     *  connection driver.
     *
     * These commands are not yet final: use instead `getExportCommand()` and `getImportCommand()` methods to
     *  have the final commands.
     *
     * @param \DatabaseBackup\OperationType $OperationType
     * @return string
     */
    protected function getCommand(OperationType $OperationType): string
    {
        /**
         * `DatabaseBackup\Executor\MysqlExecutor` has to become `mysql`
         */
        $driverName = lcfirst(preg_replace('/^DatabaseBackup\\\\Executor\\\\(\w+)Executor$/', '$1', $this::class) ?: '');

        $replacements = [
            '{{BINARY}}' => escapeshellarg($this->findBinary(DATABASE_BACKUP_EXECUTABLES[$driverName][$OperationType->value])),
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
     * Gets the command to export the database, with compression if requested.
     *
     * @param string $filename Filename where you want to export the database
     * @return string
     * @throws \LogicException
     * @throws \ValueError With a filename that does not match any supported compression.
     */
    public function getExportCommand(string $filename): string
    {
        $exec = $this->getCommand(OperationType::Export);

        $Compression = Compression::fromFilename($filename);
        if ($Compression !== Compression::None) {
            $exec .= ' | ' . escapeshellarg($this->findBinary($Compression));
        }

        return $exec . ' > ' . escapeshellarg($filename);
    }

    /**
     * Gets the command to import the database, with compression if requested.
     *
     * @param string $filename Filename from which you want to import the database
     * @return string
     * @throws \LogicException
     */
    public function getImportCommand(string $filename): string
    {
        $exec = $this->getCommand(OperationType::Import);

        $Compression = Compression::fromFilename($filename);
        if ($Compression !== Compression::None) {
            return sprintf(
                '%s -dc %s | ',
                escapeshellarg($this->findBinary($Compression)),
                escapeshellarg($filename)
            ) . $exec;
        }

        return $exec . ' < ' . escapeshellarg($filename);
    }

    /**
     * Called after export.
     *
     * @codeCoverageIgnore
     * @return void
     * @since 2.1.0
     */
    public function afterExport(): void
    {
    }

    /**
     * Called after import.
     *
     * @codeCoverageIgnore
     * @return void
     * @since 2.1.0
     */
    public function afterImport(): void
    {
    }

    /**
     * Called before export.
     *
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     * @return bool Returns `false` to stop the import
     * @since 2.1.0
     */
    public function beforeImport(): bool
    {
        return true;
    }

    /**
     * Finds and returns an executable binary by name.
     *
     * For example, with `mariadb` it should return `/usr/bin/mariadb`.
     *
     * It first checks and returns any value set by the configuration. If not present, it uses `ExecutableFinder::find)`.
     * If the binary cannot be found, an exception is thrown.
     *
     * You can specify more than one name (for example, if there are possible aliases or fallbacks). In this case, the
     *  first one found is returned.
     *
     * @param \DatabaseBackup\Compression|string ...$name
     * @return string
     * @since 2.15.0
     * @throws \InvalidArgumentException
     */
    public function findBinary(Compression|string ...$name): string
    {
        $name = array_map(
            callback: fn (Compression|string $name): string => $name instanceof Compression ? lcfirst($name->name) : $name,
            array: $name
        );

        foreach ($name as $sName) {
            $binary = Configure::read('DatabaseBackup.binaries.' . $sName);
            if ($binary) {
                return $binary;
            }

            $ExecutableFinder = new ExecutableFinder();
            $binary = $ExecutableFinder->find(name: $sName);
            if ($binary) {
                return $binary;
            }
        }

        throw new InvalidArgumentException(__d(
            'database_backup',
            'Binary for `{0}` could not be found. You have to set its path manually',
            $name
        ));
    }

    /**
     * Gets a config value of the connection.
     *
     * @param string $key Config key
     * @return mixed Config value or `null` if the key doesn't exist
     * @since 2.3.0
     */
    public function getConfig(string $key): mixed
    {
        return $this->Connection->config()[$key] ?? null;
    }
}
