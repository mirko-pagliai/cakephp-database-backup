<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * cakephp-database-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-database-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-database-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility
 */
namespace DatabaseBackup\Utility;

use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Email;
use Cake\Network\Exception\InternalErrorException;
use Cake\ORM\Entity;
use DatabaseBackup\BackupTrait;

/**
 * Utility to manage database backups
 */
class BackupManager
{
    use BackupTrait;

    /**
     * Driver containing all methods to export/import database backups
     *  according to the database engine
     * @since 2.0.0
     * @var object
     */
    protected $driver;

    /**
     * Construct
     * @uses $connection
     * @uses $driver
     */
    public function __construct()
    {
        $this->driver = $this->getDriver();
    }

    /**
     * Deletes a backup file
     * @param string $filename Filename of the backup that you want to delete.
     *  The path can be relative to the backup directory
     * @return bool
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#delete
     * @throws InternalErrorException
     */
    public function delete($filename)
    {
        $filename = $this->getAbsolutePath($filename);

        if (!is_writable($filename)) {
            throw new InternalErrorException(__d('database_backup', 'File or directory `{0}` not writable', $filename));
        }

        return unlink($filename);
    }

    /**
     * Deletes all backup files
     * @return array List of deleted backup files
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#deleteAll
     * @since 1.0.1
     * @uses delete()
     * @uses index()
     */
    public function deleteAll()
    {
        $deleted = [];

        foreach ($this->index() as $file) {
            if ($this->delete($file->filename)) {
                $deleted[] = $file->filename;
            }
        }

        return $deleted;
    }

    /**
     * Returns a list of database backups
     * @return array Backups as entities
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#index
     * @uses $driver
     */
    public function index()
    {
        $target = $this->getTarget();

        return collection((new Folder($target))->find('.+\.sql(\.(gz|bz2))?'))
            ->map(function ($filename) use ($target) {
                return new Entity([
                    'filename' => $filename,
                    'extension' => $this->driver->getExtension($filename),
                    'compression' => $this->driver->getCompression($filename),
                    'size' => filesize($target . DS . $filename),
                    'datetime' => new FrozenTime(date('Y-m-d H:i:s', filemtime($target . DS . $filename))),
                ]);
            })
            ->sortBy('datetime')
            ->toList();
    }

    /**
     * Rotates backups.
     *
     * You must indicate the number of backups you want to keep. So, it will
     *  delete all backups that are older.
     * @param int $rotate Number of backups that you want to keep
     * @return array Array of deleted files
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#rotate
     * @throws InternalErrorException
     * @uses delete()
     * @uses index()
     */
    public function rotate($rotate)
    {
        if (!isPositive($rotate)) {
            throw new InternalErrorException(__d('database_backup', 'Invalid rotate value'));
        }

        $backupsToBeDeleted = array_slice($this->index(), $rotate);

        //Deletes
        foreach ($backupsToBeDeleted as $backup) {
            $this->delete($backup->filename);
        }

        return $backupsToBeDeleted;
    }

    /**
     * Internal method to send a backup file via email
     * @param string $filename Filename
     * @param string $recipient Recipient's email address
     * @return \Cake\Mailer\Email
     * @since 1.1.0
     * @throws InternalErrorException
     */
    protected function _send($filename, $recipient)
    {
        $sender = Configure::read(DATABASE_BACKUP . '.mailSender');

        if (!$sender) {
            throw new InternalErrorException(__d('database_backup', 'You must first set the mail sender'));
        }

        $filename = $this->getAbsolutePath($filename);

        return (new Email)
            ->setFrom($sender)
            ->setTo($recipient)
            ->setSubject(__d('database_backup', 'Database backup {0} from {1}', basename($filename), env('SERVER_NAME', 'localhost')))
            ->setAttachments($filename);
    }

    /**
     * Sends a backup file via email
     * @param string $filename Filename of the backup that you want to send via
     *  email. The path can be relative to the backup directory
     * @param string $recipient Recipient's email address
     * @return array
     * @since 1.1.0
     * @uses _send()
     */
    public function send($filename, $recipient)
    {
        return $this->_send($filename, $recipient)->send();
    }
}
