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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use function Cake\I18n\__d;

/**
 * Utility to export databases.
 *
 * @method \DatabaseBackup\Utility\BackupExport compression(\DatabaseBackup\Compression|string|null $compression)
 * @method \DatabaseBackup\Utility\BackupExport filename(string $filename)
 * @method \DatabaseBackup\Utility\BackupExport timeout(int $timeout)
 */
class BackupExport extends Utility
{
    public Compression $compression = Compression::None {
        set (Compression|string|null $compression) {
            if (!$compression instanceof Compression) {
                $compression = $compression ? Compression::{ucfirst($compression)} : Compression::None;
            }

            $this->compression = $compression;
        }
    }

    public string $filename {
        set(string $filename) {
            $filename = $this->replaceFilenamePatterns($filename);
            $filename = $this->makeAbsolutePath($filename);

            if (new Filesystem()->exists($filename)) {
                throw new IOException(__d('database_backup', 'File `{0}` already exists', $filename));
            }
            $targetDir = dirname($filename);
            if (!is_writable($targetDir)) {
                throw new IOException(__d('database_backup', 'File or directory `{0}` is not writable', $targetDir));
            }

            //Sets the compression
            $this->compression = Compression::fromFilename($filename);

            $this->filename = $filename;
        }
    }

    /**
     * Internal method to replace filename patterns.
     *
     * @param string $filename
     * @return string
     */
    protected function replaceFilenamePatterns(string $filename): string
    {
        return str_replace(
            search: ['{$DATABASE}', '{$DATETIME}', '{$HOSTNAME}', '{$TIMESTAMP}'],
            replace: [
                $this->Connection->config()['database'],
                date('YmdHis'),
                str_replace(['127.0.0.1', '::1'], 'localhost', $this->Connection->config()['host'] ?? 'localhost'),
                (string)time(),
            ],
            subject: $filename,
        );
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(OperationType: OperationType::Import);
    }

    /**
     * Exports the database.
     *
     * When exporting, this method will trigger these events (implemented by the `Executor` class):
     * - `Backup.beforeExport`: will be triggered before export;
     * - `Backup.afterExport`: will be triggered after export.
     *
     * @return string|false Filename path on success or `false` if the `Backup.beforeExport` event is stopped
     * @see \DatabaseBackup\Executor\Executor::beforeExport()
     * @see \DatabaseBackup\Executor\Executor::afterExport()
     */
    public function export(): string|false
    {
        if (empty($this->filename)) {
            $this->filename = 'backup_{$DATABASE}_{$DATETIME}.sql';
        }

        //Dispatches the `Backup.beforeExport` event implemented by the `Executor` class
        $BeforeExport = $this->Executor->dispatchEvent('Backup.beforeExport');
        if ($BeforeExport->isStopped()) {
            return false;
        }

        $this->Executor->runProcess(filename: $this->filename, timeout: $this->timeout);

        //Dispatches the `Backup.afterExport` event implemented by the `Executor` class
        $this->Executor->dispatchEvent('Backup.afterExport');

        return $this->filename;
    }
}
