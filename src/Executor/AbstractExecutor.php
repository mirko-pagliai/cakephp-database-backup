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

use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use DatabaseBackup\Compression;
use DatabaseBackup\OperationType;
use InvalidArgumentException;
use Override;
use Symfony\Component\Process\ExecutableFinder;
use function Cake\I18n\__d;

/**
 * Represents an "Executor" class containing all methods to export/import database backups, according to the connection.
 *
 * "Executor" classes that extend this class must implement the `getExportBinary()` and `getImportBinary()` methods,
 *  which should return the names of the binaries (as a string or array of strings) related to the respective driver.
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
     * @return \Symfony\Component\Process\ExecutableFinder
     * @codeCoverageIgnore
     */
    protected function getExecutableFinder(): ExecutableFinder
    {
        return new ExecutableFinder();
    }

    /**
     * Constructor.
     *
     * @param \Cake\Datasource\ConnectionInterface $Connection
     * @param string|null $name Driver name. By default, it will be automatically obtained from `$Connection`
     */
    public function __construct(protected ConnectionInterface $Connection, protected ?string $name = null)
    {
        /**
         * For example, for `Cake\Database\Driver\Mysql` the name will be `MySql`.
         */
        $this->name = $name ?: substr(strrchr($Connection->getDriver()::class, '\\') ?: '', 1);

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
     * Returns the names of the binaries to export, (as a string or array of strings) related to the respective driver.
     *
     * @return array<string>|string
     */
    abstract protected function getExportBinary(): string|array;

    /**
     * Returns the names of the binaries to import, (as a string or array of strings) related to the respective driver.
     *
     * @return array<string>|string
     */
    abstract protected function getImportBinary(): string|array;

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
        // Makes sure it doesn't contain `Compression::None`
        if (array_any(array: $name, callback: fn (Compression|string $name): bool => $name instanceof Compression && !$name->isValid())) {
            throw new InvalidArgumentException('Unable to search for binary for "none" Compression');
        }

        $name = array_map(
            callback: fn (Compression|string $name): string => $name instanceof Compression ? lcfirst($name->name) : $name,
            array: $name
        );

        $ExecutableFinder = $this->getExecutableFinder();

        foreach ($name as $sName) {
            $binary = Configure::read(
                var: 'DatabaseBackup.binaries.' . $sName,
                default: $ExecutableFinder->find(name: $sName)
            );
            if ($binary) {
                return $binary;
            }
        }

        throw new InvalidArgumentException(__d(
            'database_backup',
            'Binary for `{0}` could not be found. You have to set its path manually on your bootstrap with: `{1}`',
            $name[0],
            'Configure::write(\'DatabaseBackup.binaries.' . $name[0] . '\', \'/your/full/path/to/' . $name[0] . '\')'
        ));
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
        //Gets the binaries names
        $binaries = (array)$this->{$OperationType == OperationType::Export ? 'getExportBinary' : 'getImportBinary'}();

        $replacements = [
            '{{BINARY}}' => escapeshellarg($this->findBinary(...$binaries)),
            '{{AUTH_FILE}}' => method_exists($this, 'getAuthFilePath') && $this->getAuthFilePath() ? $this->getAuthFilePath() : '',
            '{{DB_USER}}' => $this->getConfig('username'),
            '{{DB_PASSWORD}}' => $this->getConfig('password') ? ':' . $this->getConfig('password') : '',
            '{{DB_HOST}}' => $this->getConfig('host'),
            '{{DB_NAME}}' => $this->getConfig('database'),
        ];

        /**
         * Gets the command to execute.
         *
         * The value read from the configuration will be for example `DatabaseBackup.mysql.export`.
         *
         * @var string $command
         */
        $command = Configure::readOrFail('DatabaseBackup.' . $this->name . '.' . $OperationType->value);

        return str_replace(array_keys($replacements), $replacements, $command);
    }

    public function getNewCommand(OperationType $OperationType, string $filename): string
    {
        $command = $this->getCommand($OperationType);

        $Compression = Compression::fromFilename($filename);

        if ($OperationType == OperationType::Export) {
            if ($Compression->isValid()) {
                $command .= ' | ' . $this->findBinary($Compression);
            }

            return $command . ' > ' . $filename;
        } else {
            if ($Compression->isValid()) {
                return sprintf(
                    '%s -dc %s | ',
                    $this->findBinary($Compression),
                    $filename
                ) . $command;
            }

            return $command . ' < ' . $filename;
        }
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
        if ($Compression->isValid()) {
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
        if ($Compression->isValid()) {
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
