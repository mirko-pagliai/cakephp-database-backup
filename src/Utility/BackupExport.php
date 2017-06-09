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
     * Database connection
     * @var array
     */
    protected $connection;

    /**
     * Recipient of the email, if you want to send the backup via mail
     * @var bool|string
     */
    protected $emailRecipient = false;

    /**
     * Executable command
     * @var string
     */
    protected $executable;

    /**
     * Filename extension
     * @var string
     */
    protected $extension = 'sql';

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
    protected $rotate;

    /**
     * Construct
     * @uses $connection
     */
    public function __construct()
    {
        $this->connection = $this->getConnection();
        $this->BackupManager = new BackupManager;
    }

    /**
     * Gets the executable command
     * @param bool|string $compression Compression. Supported values are
     *  `bzip2`, `gzip` and `false` (if you don't want to use compression)
     * @return string
     * @throws InternalErrorException
     */
    protected function _getExecutable($compression)
    {
        $mysqldump = Configure::read(MYSQL_BACKUP . '.bin.mysqldump');

        if (in_array($compression, ['bzip2', 'gzip'])) {
            $executable = Configure::read(sprintf(MYSQL_BACKUP . '.bin.%s', $compression));

            if (!$executable) {
                throw new InternalErrorException(__d('mysql_backup', '`{0}` executable not available', $compression));
            }

            return sprintf('%s --defaults-file=%%s %%s | %s > %%s', $mysqldump, $executable);
        }

        //No compression
        return sprintf('%s --defaults-file=%%s %%s > %%s', $mysqldump);
    }

    /**
     * Stores the authentication data in a temporary file.
     *
     * For security reasons, it's recommended to specify the password in
     *  a configuration file and not in the command (a user can execute
     *  a `ps aux | grep mysqldump` and see the password).
     *  So it creates a temporary file to store the configuration options
     * @uses $connection
     * @return string File path
     */
    private function _storeAuth()
    {
        $auth = tempnam(sys_get_temp_dir(), 'auth');

        file_put_contents($auth, sprintf(
            "[mysqldump]\nuser=%s\npassword=\"%s\"\nhost=%s",
            $this->connection['username'],
            empty($this->connection['password']) ? null : $this->connection['password'],
            $this->connection['host']
        ));

        return $auth;
    }

    /**
     * Sets the compression
     * @param bool|string $compression Compression type. Supported values are
     *  `bzip2`, `gzip` and `false` (if you don't want to use compression)
     * @return \MysqlBackup\Utility\BackupExport
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupExport-utility#compression
     * @throws InternalErrorException
     * @uses $compression
     * @uses $extension
     * @uses $filename
     */
    public function compression($compression)
    {
        if (!in_array($compression, $this->getValidCompressions(), true)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid compression type'));
        }

        $this->compression = $compression;
        $this->extension = $this->getExtension($compression);

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
     * @uses $connection
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
            $this->connection['database'],
            date('YmdHis'),
            $this->connection['host'],
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
        if (!$this->getExtension($filename)) {
            throw new InternalErrorException(__d('mysql_backup', 'Invalid file extension'));
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
     * @uses MysqlBackup\Utility\BackupManager::rotate()
     * @uses MysqlBackup\Utility\BackupManager::send()
     * @uses _getExecutable()
     * @uses _storeAuth()
     * @uses filename()
     * @uses $BackupManager;
     * @uses $compression
     * @uses $connection
     * @uses $extension
     * @uses $filename
     * @uses $rotate
     */
    public function export()
    {
        if (empty($this->filename)) {
            $this->filename(sprintf('backup_{$DATABASE}_{$DATETIME}.%s', $this->extension));
        }

        //This allows the filename to be set again with a next call of this
        //  method
        $filename = $this->filename;
        unset($this->filename);

        //Stores the authentication data in a temporary file
        $auth = $this->_storeAuth();

        //Executes
        exec(sprintf($this->_getExecutable($this->compression), $auth, $this->connection['database'], $filename));

        //Deletes the temporary file
        unlink($auth);

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
