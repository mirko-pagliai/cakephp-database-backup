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
use LogicException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Utility to export databases.
 *
 * @property ?string $compression
 * @property ?string $emailRecipient
 * @property string $extension
 * @property int $rotate
 */
class BackupExport extends AbstractBackupUtility
{
    /**
     * @var string|null
     */
    protected ?string $compression = null;

    /**
     * @var string
     */
    private string $defaultExtension = 'sql';

    /**
     * @var string|null
     */
    protected ?string $emailRecipient = null;

    /**
     * @var string
     */
    protected string $extension;

    /**
     * @var int
     */
    protected int $rotate = 0;

    /**
     * Sets the compression.
     *
     * Compression supported values are:
     *  - `bzip2`;
     *  - `gzip`;
     *  - `null` for no compression.
     *
     * @param string|null $compression Compression type name
     * @return self
     * @throws \LogicException
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#compression
     */
    public function compression(?string $compression): self
    {
        $this->extension = $this->defaultExtension;

        if ($compression) {
            $this->extension = (string)array_search($compression, $this->getValidCompressions());
            if (!$this->extension) {
                throw new LogicException(__d('database_backup', 'Invalid compression type'));
            }
        }
        $this->compression = $compression;

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
        if (!$this->getExtension($filename)) {
            throw new LogicException(
                __d('database_backup', 'Invalid `{0}` file extension', pathinfo($filename, PATHINFO_EXTENSION))
            );
        }

        //Sets the compression
        $this->compression($this->getCompression($filename));

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
     * Sets the recipient's email address to send the backup file via mail.
     *
     * @param string|null $recipient Recipient's email address or `null` to disable
     * @return self
     * @since 1.1.0
     * @deprecated 2.13.4 the `BackupExport::send()` method is deprecated. Will be removed in a future release
     */
    public function send(?string $recipient = null): self
    {
        deprecationWarning(
            '2.13.4',
            'The `BackupExport::send()` method is deprecated. Will be removed in a future release'
        );

        $this->emailRecipient = $recipient;

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
            $this->extension ??= $this->defaultExtension;
            $this->filename('backup_{$DATABASE}_{$DATETIME}.' . $this->extension);
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

        if ($this->emailRecipient) {
            BackupManager::send($filename, $this->emailRecipient);
        }
        if ($this->rotate) {
            BackupManager::rotate($this->rotate);
        }

        return $filename;
    }
}
