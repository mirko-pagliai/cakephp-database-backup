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
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Driver;
use Tools\Exceptionist;
use Tools\Filesystem;

/**
 * Utility to export databases
 */
class BackupExport
{
    use BackupTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupManager
     */
    public BackupManager $BackupManager;

    /**
     * Driver containing all methods to export/import database backups according to the connection
     * @since 2.0.0
     * @var \DatabaseBackup\Driver\Driver
     */
    public Driver $Driver;

    /**
     * Compression type
     * @var string|null
     */
    protected ?string $compression = null;

    /**
     * Database configuration
     * @var array
     */
    protected array $config;

    /**
     * Default extension
     * @var string
     */
    protected string $defaultExtension = 'sql';

    /**
     * Recipient of the email, if you want to send the backup via mail
     * @var string|null
     */
    protected ?string $emailRecipient = null;

    /**
     * Filename extension
     * @var string
     */
    protected string $extension;

    /**
     * Filename where to export the database
     * @var string
     */
    protected string $filename;

    /**
     * Rotate limit. This is the number of backups you want to keep. So, it will delete all backups that are older
     * @var int
     */
    protected int $rotate = 0;

    /**
     * Construct
     * @throws \ErrorException|\ReflectionException
     */
    public function __construct()
    {
        $connection = $this->getConnection();
        $this->BackupManager = new BackupManager();
        $this->Driver = $this->getDriver($connection);
        $this->config = $connection->config();
    }

    /**
     * Sets the compression.
     *
     * Compression supported values are:
     *  - `bzip2`;
     *  - `gzip`;
     *  - `null` for no compression.
     * @param string|null $compression Compression type name
     * @return $this
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#compression
     * @throws \ErrorException
     */
    public function compression(?string $compression)
    {
        $this->extension = $this->defaultExtension;

        if ($compression) {
            $this->extension = (string)array_search($compression, $this->getValidCompressions());
            Exceptionist::isTrue($this->extension, __d('database_backup', 'Invalid compression type'));
        }
        $this->compression = $compression;

        return $this;
    }

    /**
     * Sets the filename.
     *
     * The compression type will be automatically set by the filename.
     * @param string $filename Filename. It can be an absolute path and may contain patterns
     * @return $this
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#filename
     * @throws \ErrorException
     * @throws \Tools\Exception\NotWritableException
     */
    public function filename(string $filename)
    {
        //Replaces patterns
        $filename = str_replace(['{$DATABASE}', '{$DATETIME}', '{$HOSTNAME}', '{$TIMESTAMP}'], [
            pathinfo($this->config['database'], PATHINFO_FILENAME),
            date('YmdHis'),
            str_replace(['127.0.0.1', '::1'], 'localhost', $this->config['host'] ?? 'localhost'),
            time(),
        ], $filename);

        $filename = $this->getAbsolutePath($filename);
        Exceptionist::isWritable(dirname($filename));
        Exceptionist::isTrue(!file_exists($filename), __d('database_backup', 'File `{0}` already exists', $filename));

        //Checks for extension
        Exceptionist::isTrue($this->getExtension($filename), __d('database_backup', 'Invalid `{0}` file extension', pathinfo($filename, PATHINFO_EXTENSION)));

        //Sets the compression
        $this->compression($this->getCompression($filename));

        $this->filename = $filename;

        return $this;
    }

    /**
     * Sets the number of backups you want to keep. So, it will delete all backups that are older
     * @param int $rotate Number of backups you want to keep
     * @return $this
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#rotate
     */
    public function rotate(int $rotate)
    {
        $this->rotate = $rotate;

        return $this;
    }

    /**
     * Sets the recipient's email address to send the backup file via mail
     * @param string|null $recipient Recipient's email address or `null` to disable
     * @return $this
     * @since 1.1.0
     */
    public function send(?string $recipient = null)
    {
        $this->emailRecipient = $recipient;

        return $this;
    }

    /**
     * Exports the database
     * @return string Filename path
     * @throws \Exception
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#export
     */
    public function export(): string
    {
        if (empty($this->filename)) {
            $this->extension ??= $this->defaultExtension;
            $this->filename(sprintf('backup_{$DATABASE}_{$DATETIME}.%s', $this->extension));
        }

        //This allows the filename to be set again with a next call of this method
        $filename = $this->filename;
        unset($this->filename);

        $this->Driver->export($filename);
        Filesystem::instance()->chmod($filename, Configure::read('DatabaseBackup.chmod'));

        if ($this->emailRecipient) {
            $this->BackupManager->send($filename, $this->emailRecipient);
        }
        if ($this->rotate) {
            $this->BackupManager->rotate($this->rotate);
        }

        return $filename;
    }
}
