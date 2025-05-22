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

namespace DatabaseBackup\Utility;

use DatabaseBackup\Compression;
use DatabaseBackup\OperationType;
use LogicException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use function Cake\I18n\__d;

/**
 * Utility to import databases.
 *
 * @method \DatabaseBackup\Utility\BackupImport filename(string $filename)
 * @method \DatabaseBackup\Utility\BackupImport timeout(int $timeout)
 */
class BackupImport extends Utility
{
    public string $filename {
        set(string $filename) {
            $filename = $this->makeAbsolutePath(path: $filename);

            if (!new Filesystem()->exists($filename)) {
                throw new IOException(
                    __d('database_backup', 'File `{0}` does not exist', $filename)
                );
            }

            //This is only useful to possibly throw a `ValueError`
            Compression::fromFilename($filename);

            $this->filename = $filename;
        }
        get => $this->filename ?? throw new LogicException(__d('database_backup', 'You must first set the filename'));
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(OperationType: OperationType::Import);
    }

    /**
     * Imports the database.
     *
     * When importing, this method will trigger these events (implemented by the `Executor` class):
     *  - `Backup.beforeImport`: will be triggered before import;
     *  - `Backup.afterImport`: will be triggered after import.
     *
     * @return string|false Filename path on success or `false` if the `Backup.beforeImport` event is stopped
     * @see \DatabaseBackup\Executor\Executor::afterImport()
     * @see \DatabaseBackup\Executor\Executor::beforeImport()
     */
    public function import(): string|false
    {
        //Dispatches the `Backup.beforeImport` event implemented by the `Executor` class
        $BeforeImport = $this->Executor->dispatchEvent('Backup.beforeImport');
        if ($BeforeImport->isStopped()) {
            return false;
        }

        $this->Executor->runProcess(filename: $this->filename, timeout: $this->timeout);

        //Dispatches the `Backup.afterImport` event implemented by the `Executor` class
        $this->Executor->dispatchEvent('Backup.afterImport');

        return $this->filename;
    }
}
