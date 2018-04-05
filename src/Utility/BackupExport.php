<?php
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
use DatabaseBackup\BackupTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Utility to export databases
 */
class BackupExport
{
    use BackupTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupManager
     */
    public $BackupManager;

    /**
     * Compression type
     * @var bool|string|null
     */
    protected $compression = null;

    /**
     * Database configuration
     * @var array
     */
    protected $config;

    /**
     * Default extension
     * @var string
     */
    protected $defaultExtension = 'sql';

    /**
     * Driver containing all methods to export/import database backups
     *  according to the database engine
     * @since 2.0.0
     * @var object
     */
    public $driver;

    /**
     * Recipient of the email, if you want to send the backup via mail
     * @var bool|string
     */
    protected $emailRecipient = false;

    /**
     * Filename extension
     * @var string
     */
    protected $extension;

    /**
     * Filename where to export the database
     * @var string
     */
    protected $filename;

    /**
     * Rotate limit. This is the number of backups you want to keep. So, it
     *  will delete all backups that are older.
     * @var int
     */
    protected $rotate = 0;

    /**
     * Construct
     * @uses $BackupManager
     * @uses $config
     * @uses $driver
     */
    public function __construct()
    {
        $this->BackupManager = new BackupManager;

        $connection = $this->getConnection();

        $this->config = $connection->config();
        $this->driver = $this->getDriver($connection);
    }

    /**
     * Sets the compression
     * @param bool|string $compression Compression type as string. Supported
     *  values are `bzip2` and `gzip`. Use `false` for no compression
     * @return \DatabaseBackup\Utility\BackupExport
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#compression
     * @throws InvalidArgumentException
     * @uses getValidCompressions()
     * @uses $compression
     * @uses $defaultExtension
     * @uses $extension
     */
    public function compression($compression)
    {
        if ($compression) {
            $this->extension = array_search($compression, $this->getValidCompressions());

            if (!$this->extension) {
                throw new InvalidArgumentException(__d('database_backup', 'Invalid compression type'));
            }
        } else {
            $this->extension = $this->defaultExtension;
        }

        $this->compression = $compression;

        return $this;
    }

    /**
     * Sets the filename.
     *
     * The compression type will be automatically setted by the filename.
     * @param string $filename Filename. It can be an absolute path and may
     *  contain patterns
     * @return \DatabaseBackup\Utility\BackupExport
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#filename
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @uses compression()
     * @uses $config
     * @uses $filename
     */
    public function filename($filename)
    {
        //Replaces patterns
        $filename = str_replace([
            '{$DATABASE}',
            '{$DATETIME}',
            '{$HOSTNAME}',
            '{$TIMESTAMP}',
        ], [
            pathinfo($this->config['database'], PATHINFO_FILENAME),
            date('YmdHis'),
            empty($this->config['host']) ? 'localhost' : $this->config['host'],
            time(),
        ], $filename);

        $filename = $this->getAbsolutePath($filename);

        if (!is_writable(dirname($filename))) {
            throw new RuntimeException(__d('database_backup', 'File or directory `{0}` not writable', dirname($filename)));
        }

        if (file_exists($filename)) {
            throw new RuntimeException(__d('database_backup', 'File `{0}` already exists', $filename));
        }

        //Checks for extension
        if (!$this->getExtension($filename)) {
            throw new InvalidArgumentException(__d('database_backup', 'Invalid file extension'));
        }

        //Sets the compression
        $this->compression($this->getCompression($filename));

        $this->filename = $filename;

        return $this;
    }

    /**
     * Sets the number of backups you want to keep. So, it will delete all
     * backups that are older
     * @param int $rotate Number of backups you want to keep
     * @return \DatabaseBackup\Utility\BackupExport
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#rotate
     * @uses $rotate
     */
    public function rotate($rotate)
    {
        $this->rotate = $rotate;

        return $this;
    }

    /**
     * Sets the recipient's email address to send the backup file via mail
     * @param bool|string $recipient Recipient's email address or `false` to disable
     * @return \DatabaseBackup\Utility\BackupExport
     * @since 1.1.0
     * @uses $emailRecipient
     */
    public function send($recipient = false)
    {
        $this->emailRecipient = $recipient;

        return $this;
    }

    /**
     * Exports the database
     * @return string Filename path
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#export
     * @uses filename()
     * @uses $BackupManager;
     * @uses $defaultExtension
     * @uses $emailRecipient
     * @uses $filename
     * @uses $extension
     * @uses $rotate
     */
    public function export()
    {
        if (empty($this->filename)) {
            if (empty($this->extension)) {
                $this->extension = $this->defaultExtension;
            }

            $this->filename(sprintf('backup_{$DATABASE}_{$DATETIME}.%s', $this->extension));
        }

        //This allows the filename to be set again with a next call of this
        //  method
        $filename = $this->filename;
        unset($this->filename);

        $this->driver->export($filename);

        if (!is_win()) {
            chmod($filename, Configure::read(DATABASE_BACKUP . '.chmod'));
        }

        if ($this->emailRecipient) {
            $this->BackupManager->send($filename, $this->emailRecipient);
        }

        //Rotates backups
        if ($this->rotate) {
            $this->BackupManager->rotate($this->rotate);
        }

        return $filename;
    }
}
