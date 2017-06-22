<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 * @see         https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupExport-utility
 */
namespace MysqlBackup\Utility;

use Cake\Core\Configure;
use Cake\Network\Exception\InternalErrorException;
use MysqlBackup\BackupTrait;

/**
 * Utility to export databases
 */
class BackupExport
{
    use BackupTrait;

    /**
     * @var \MysqlBackup\Utility\BackupManager
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
        $this->config = $this->getConnection()->config();
        $this->driver = $this->getDriver($this->getConnection());
    }

    /**
     * Sets the compression
     * @param bool|string $compression Compression type. Supported values are
     *  `bzip2`, `gzip` and `false` (if you don't want to use compression)
     * @return \MysqlBackup\Utility\BackupExport
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupExport-utility#compression
     * @throws InternalErrorException
     * @uses $compression
     * @uses $driver
     * @uses $extension
     */
    public function compression($compression)
    {
        $this->extension = array_search($compression, $this->driver->getValidCompressions(), true);

        if (!$this->extension) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid compression type'));
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
     * @return \MysqlBackup\Utility\BackupExport
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupExport-utility#filename
     * @throws InternalErrorException
     * @uses compression()
     * @uses $config
     * @uses $driver
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
            throw new InternalErrorException(__d('mysql_backup', 'File or directory `{0}` not writable', dirname($filename)));
        }

        if (file_exists($filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'File `{0}` already exists', $filename));
        }

        //Checks for extension
        if (!$this->driver->getExtension($filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid file extension'));
        }

        //Sets the compression
        $this->compression($this->driver->getCompression($filename));

        $this->filename = $filename;

        return $this;
    }

    /**
     * Sets the number of backups you want to keep. So, it will delete all
     * backups that are older
     * @param int $rotate Number of backups you want to keep
     * @return \MysqlBackup\Utility\BackupExport
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupExport-utility#rotate
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
     * @return \MysqlBackup\Utility\BackupExport
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
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupExport-utility#export
     * @uses filename()
     * @uses $BackupManager;
     * @uses $driver
     * @uses $emailRecipient
     * @uses $filename
     * @uses $extension
     * @uses $rotate
     */
    public function export()
    {
        if (empty($this->filename)) {
            if (empty($this->extension)) {
                $this->extension = $this->driver->getDefaultExtension();
            }

            $this->filename(sprintf('backup_{$DATABASE}_{$DATETIME}.%s', $this->extension));
        }

        //This allows the filename to be set again with a next call of this
        //  method
        $filename = $this->filename;
        unset($this->filename);

        $this->driver->export($filename);

        chmod($filename, Configure::read(MYSQL_BACKUP . '.chmod'));

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
