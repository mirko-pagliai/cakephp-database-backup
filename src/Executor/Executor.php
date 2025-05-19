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

use Cake\Datasource\ConnectionInterface;
use Cake\Event\EventInterface;

/**
 * Represents an "Executor" class containing all methods to export/import database backups, according to the connection.
 *
 * @since 2.0.0
 */
abstract class Executor
{
    public ConnectionInterface $Connection;

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
