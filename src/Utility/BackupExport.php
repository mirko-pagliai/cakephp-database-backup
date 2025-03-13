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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility
 */

namespace DatabaseBackup\Utility;

use Cake\Core\Configure;
use DatabaseBackup\Compression;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Utility to export databases.
 *
 * @method \DatabaseBackup\Compression getCompression()
 * @method int getRotate()
 */
class BackupExport extends AbstractBackupUtility
{
    /**
     * @var \DatabaseBackup\Compression
     */
    protected Compression $compression = Compression::None;

    /**
     * @var int
     */
    protected int $rotate = 0;

    /**
     * Internal method to get a `Filesystem` instance.
     *
     * @return \Symfony\Component\Filesystem\Filesystem
     * @since 2.14.0
     */
    protected function getFilesystem(): Filesystem
    {
        return new Filesystem();
    }

    /**
     * Sets the compression.
     *
     * @param \DatabaseBackup\Compression $Compression Compression type
     * @return self
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#compression
     */
    public function compression(Compression $Compression): self
    {
        $this->compression = $Compression;

        return $this;
    }

    /**
     * Sets the filename.
     *
     * The compression type will be automatically set by the filename.
     *
     * @param string $filename Filename. It can be an absolute path and may contain patterns
     * @return self
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#filename
     * @throws \Symfony\Component\Filesystem\Exception\IOException If the target (directory) is not writable or if the filename already exists.
     * @throws \ValueError With a filename that does not match any supported compression.
     */
    public function filename(string $filename): self
    {
        $Executor = $this->getExecutor();

        //Replaces patterns
        $filename = str_replace(['{$DATABASE}', '{$DATETIME}', '{$HOSTNAME}', '{$TIMESTAMP}'], [
            pathinfo($Executor->getConfig('database'), PATHINFO_FILENAME),
            date('YmdHis'),
            str_replace(['127.0.0.1', '::1'], 'localhost', $Executor->getConfig('host') ?? 'localhost'),
            time(),
        ], $filename);

        $filename = $this->makeAbsoluteFilename($filename);
        if (!is_writable(dirname($filename))) {
            throw new IOException(
                __d('database_backup', 'File or directory `{0}` is not writable', dirname($filename))
            );
        }
        if (file_exists($filename)) {
            throw new IOException(
                __d('database_backup', 'File `{0}` already exists', $filename)
            );
        }

        //Sets the compression
        $this->compression = Compression::fromFilename($filename);

        $this->filename = $filename;

        return $this;
    }

    /**
     * Sets the number of backups you want to keep. So, it will delete all backups that are older.
     *
     * @param int $keep Number of backups you want to keep
     * @return self
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#rotate
     */
    public function rotate(int $keep): self
    {
        $this->rotate = $keep;

        return $this;
    }

    /**
     * Exports the database.
     *
     * When exporting, this method will trigger these events (implemented by the `Executor` class):
     *  - `Backup.beforeExport`: will be triggered before export;
     *  - `Backup.afterExport`: will be triggered after export.
     *
     * @return string|false Filename path on success or `false` if the `Backup.beforeExport` event is stopped
     * @throws \RuntimeException When export fails
     * @see \DatabaseBackup\Executor\AbstractExecutor::afterExport()
     * @see \DatabaseBackup\Executor\AbstractExecutor::beforeExport()
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#export
     */
    public function export(): string|false
    {
        if (empty($this->filename)) {
            $this->filename('backup_{$DATABASE}_{$DATETIME}.' . $this->getCompression()->value);
        }

        //This allows the filename to be set again with a next call of this method
        $filename = $this->getFilename();
        unset($this->filename);

        $Executor = $this->getExecutor();

        //Dispatches the `Backup.beforeExport` event implemented by the `Executor` class
        $BeforeExport = $Executor->dispatchEvent('Backup.beforeExport');
        if ($BeforeExport->isStopped()) {
            return false;
        }

        //Exports
        $Process = $this->getProcess($Executor->getExportExecutable($filename));
        if (!$Process->isSuccessful()) {
            throw new RuntimeException(
                __d('database_backup', 'Export failed with error message: `{0}`', rtrim($Process->getErrorOutput()))
            );
        }

        $this->getFilesystem()->chmod($filename, Configure::read('DatabaseBackup.chmod'));

        //Dispatches the `Backup.afterExport` event implemented by the `Executor` class
        $Executor->dispatchEvent('Backup.afterExport');

        if ($this->getRotate()) {
            BackupManager::rotate($this->getRotate());
        }

        return $filename;
    }
}
