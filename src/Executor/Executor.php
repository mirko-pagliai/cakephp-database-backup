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
use InvalidArgumentException;
use Symfony\Component\Process\ExecutableFinder;
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
