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
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Utility to export databases.
 *
 * @property \DatabaseBackup\Compression $compression
 * @property int $rotate
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
     * Sets the compression.
     *
     * @param \DatabaseBackup\Compression|string|null $Compression Compression type
     * @return self
     * @throws \InvalidArgumentException With an invalid string argument.
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#compression
     */
    public function compression(Compression|string|null $Compression): self
    {
        /**
         * Backward compatibility if argument is string or `null`.
         *
         * @see https://stackoverflow.com/a/77859717/1480263
         * @todo remove in a future release
         */
        if (!$Compression instanceof Compression) {
            deprecationWarning(
                '2.13.5',
                'Passing `$Compression` argument as a string or `null` is deprecated. Will be removed in a future release'
            );

            $constantName = Compression::class . '::' . ucfirst($Compression ?: 'none');
            if (!defined($constantName)) {
                throw new InvalidArgumentException(sprintf(
                    'No valid `%s` value was found starting from `%s`',
                    Compression::class,
                    $Compression
                ));
            }
            $Compression = constant($constantName);
        }

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
     * @throws \LogicException
     * @throws \ValueError With a filename that does not match any supported compression.
     */
    public function filename(string $filename): self
    {
        $config = $this->getConnection()->config();

        //Replaces patterns
        $filename = str_replace(['{$DATABASE}', '{$DATETIME}', '{$HOSTNAME}', '{$TIMESTAMP}'], [
            pathinfo($config['database'], PATHINFO_FILENAME),
            date('YmdHis'),
            str_replace(['127.0.0.1', '::1'], 'localhost', $config['host'] ?? 'localhost'),
            time(),
        ], $filename);

        $filename = $this->getAbsolutePath($filename);
        if (!is_writable(dirname($filename))) {
            throw new LogicException(
                __d('database_backup', 'File or directory `{0}` is not writable', dirname($filename))
            );
        }
        if (file_exists($filename)) {
            throw new LogicException(
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
     * @param int $rotate Number of backups you want to keep
     * @return self
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#rotate
     */
    public function rotate(int $rotate): self
    {
        $this->rotate = $rotate;

        return $this;
    }

    /**
     * Exports the database.
     *
     * When exporting, this method will trigger these events (implemented by the driver instance):
     *  - `Backup.beforeExport`: will be triggered before export;
     *  - `Backup.afterExport`: will be triggered after export.
     *
     * @return string|false Filename path on success or `false` if the `Backup.beforeExport` event is stopped
     * @throws \LogicException
     * @see \DatabaseBackup\Driver\AbstractDriver::afterExport()
     * @see \DatabaseBackup\Driver\AbstractDriver::beforeExport()
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#export
     */
    public function export(): string|false
    {
        if (empty($this->filename)) {
            $this->filename('backup_{$DATABASE}_{$DATETIME}.' . $this->compression->value);
        }

        //This allows the filename to be set again with a next call of this method
        $filename = $this->filename;
        unset($this->filename);

        //Dispatches the `Backup.beforeExport` event implemented by the driver
        $BeforeExport = $this->getDriver()->dispatchEvent('Backup.beforeExport');
        if ($BeforeExport->isStopped()) {
            return false;
        }

        //Exports
        $Process = $this->getProcess($this->getDriver()->getExportExecutable($filename));
        if (!$Process->isSuccessful()) {
            throw new LogicException(
                __d('database_backup', 'Export failed with error message: `{0}`', rtrim($Process->getErrorOutput()))
            );
        }
        (new Filesystem())->chmod($filename, Configure::read('DatabaseBackup.chmod'));

        //Dispatches the `Backup.afterExport` event implemented by the driver
        $this->getDriver()->dispatchEvent('Backup.afterExport');

        if ($this->rotate) {
            BackupManager::rotate($this->rotate);
        }

        return $filename;
    }
}
