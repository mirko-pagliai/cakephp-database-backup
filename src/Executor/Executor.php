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
 */

namespace DatabaseBackup\Executor;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use DatabaseBackup\Compression;
use DatabaseBackup\OperationType;
use InvalidArgumentException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use function Cake\I18n\__d;

/**
 * Represents an "Executor" class containing all methods to export/import database backups, according to the connection.
 *
 * @since 2.0.0
 */
abstract class Executor implements EventListenerInterface
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\DatabaseBackup\Executor\Executor>
     */
    use EventDispatcherTrait;

    public Connection $Connection;

    /**
     * @param \DatabaseBackup\OperationType $OperationType
     */
    public function __construct(public OperationType $OperationType)
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
     * Returns the binary names to export/import, related to the respective driver.
     *
     * @return string
     */
    abstract public function getBinaryName(): string;

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
     * To use `findBinary()` in conjunction with `getBinaryName()`:
     * ```
     * $this->findBinary(...(array)$this->getBinaryName())
     * ```
     *
     * @param \DatabaseBackup\Compression|string ...$name
     * @return string
     * @since 3.0.0
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

        $ExecutableFinder = new ExecutableFinder();

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
     * Gets the properly formatted "raw" command, based on the type of operation and the compression required.
     *
     * @param \DatabaseBackup\Compression $Compression
     * @return string
     * @since 3.0.0
     */
    public function getCommand(Compression $Compression): string
    {
        $isExport = $this->OperationType == OperationType::Export;

        /**
         * For example, for `Cake\Database\Driver\Mysql` the name will be `MySql`.
         */
        $name = substr(strrchr($this->Connection->config()['driver'], '\\') ?: '', 1);

        /**
         * This is the base command.
         * It still needs to be properly articulated.
         */
        $command = Configure::readOrFail('DatabaseBackup.' . $name . '.' . $this->OperationType->value);

        if (!$Compression->isValid()) {
            return $command . ' ' . ($isExport ? '>' : '<') . ' "${:FILENAME}"';
        }

        if ($isExport) {
            return $command . ' | "${:COMPRESSION_BINARY}" > "${:FILENAME}"';
        }

        return '"${:COMPRESSION_BINARY}" -dc "${:FILENAME}" | ' . $command;
    }

    /**
     * Gets and runs a `Process` instance, based on the given file.
     *
     * @param string $filename The name of the file to be processed
     * @return \Symfony\Component\Process\Process The executed process instance.
     * @since 3.0.0
     */
    public function runProcess(string $filename, int $timeout = 60): Process
    {
        $Compression = Compression::fromFilename($filename);

        $config = $this->Connection->config();

        /**
         * @see https://symfony.com/doc/current/components/process.html
         */
        $Process = Process::fromShellCommandline(
            command: $this->getCommand(Compression: $Compression),
            timeout: $timeout,
        );

        $Process->run(env: [
            'AUTH_FILE' => method_exists($this, 'getAuthFilePath') && $this->getAuthFilePath() ? $this->getAuthFilePath() : '',
            'BINARY' => $this->findBinary(...(array)$this->getBinaryName()),
            'COMPRESSION_BINARY' => $Compression->isValid() ? $this->findBinary($Compression) : null,
            'DB_HOST' => $config['host'],
            'DB_NAME' => $config['database'],
            'DB_PASSWORD' => $config['password'],
            'DB_USER' => $config['username'],
            'FILENAME' => $filename,
        ]);

        return $Process;
    }

    /**
     * Called after export.
     *
     * @return void
     * @since 2.1.0
     * @codeCoverageIgnore
     */
    public function afterExport(): void
    {
    }

    /**
     * Called after import.
     *
     * @return void
     * @since 2.1.0
     * @codeCoverageIgnore
     */
    public function afterImport(): void
    {
    }

    /**
     * Called before export.
     *
     * @param \Cake\Event\EventInterface<object> $Event
     * @return void
     * @since 2.1.0
     * @codeCoverageIgnore
     */
    public function beforeExport(EventInterface $Event): void
    {
    }

    /**
     * Called before import.
     *
     * @param \Cake\Event\EventInterface<object> $Event
     * @return void
     * @since 2.1.0
     * @codeCoverageIgnore
     */
    public function beforeImport(EventInterface $Event): void
    {
    }
}
