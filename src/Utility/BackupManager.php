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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility
 */
namespace DatabaseBackup\Utility;

use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Email;
use Cake\ORM\Entity;
use DatabaseBackup\BackupTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Utility to manage database backups
 */
class BackupManager
{
    use BackupTrait;

    /**
     * Deletes a backup file
     * @param string $filename Filename of the backup that you want to delete.
     *  The path can be relative to the backup directory
     * @return bool
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#delete
     * @throws RuntimeException
     */
    public function delete($filename)
    {
        $filename = $this->getAbsolutePath($filename);

        if (!is_writable($filename)) {
            throw new RuntimeException(__d('database_backup', 'File or directory `{0}` not writable', $filename));
        }

        return safe_unlink($filename);
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
     */
    public function index()
    {
        $target = $this->getTarget();

        return collection((new Folder($target))->find('.+\.sql(\.(gz|bz2))?'))
            ->map(function ($filename) use ($target) {
                return new Entity([
                    'filename' => $filename,
                    'extension' => $this->getExtension($filename),
                    'compression' => $this->getCompression($filename),
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
     * @throws InvalidArgumentException
     * @uses delete()
     * @uses index()
     */
    public function rotate($rotate)
    {
        if (!is_positive($rotate)) {
            throw new InvalidArgumentException(__d('database_backup', 'Invalid rotate value'));
        }

        $backupsToBeDeleted = array_slice($this->index(), $rotate);

        //Deletes
        foreach ($backupsToBeDeleted as $backup) {
            $this->delete($backup->filename);
        }

        return $backupsToBeDeleted;
    }

    /**
     * Internal method to get an email instance with all options to send a
     *  backup file via email
     * @param string $backup Backup you want to send
     * @param string $recipient Recipient's email address
     * @return \Cake\Mailer\Email
     * @since 1.1.0
     */
    protected function getEmailInstance($backup, $recipient)
    {
        $file = $this->getAbsolutePath($backup);

        if (!is_readable($file)) {
            throw new RuntimeException(__d('database_backup', 'File or directory `{0}` not readable', $file));
        }

        $basename = basename($file);
        $mimetype = mime_content_type($file);

        return (new Email)
            ->setFrom(Configure::readOrFail(DATABASE_BACKUP . '.mailSender'))
            ->setTo($recipient)
            ->setSubject(__d('database_backup', 'Database backup {0} from {1}', $basename, env('SERVER_NAME', 'localhost')))
            ->setAttachments([$basename => compact('file', 'mimetype')]);
    }

    /**
     * Sends a backup file via email
     * @param string $filename Filename of the backup that you want to send via
     *  email. The path can be relative to the backup directory
     * @param string $recipient Recipient's email address
     * @return array
     * @since 1.1.0
     * @uses getEmailInstance()
     */
    public function send($filename, $recipient)
    {
        return $this->getEmailInstance($filename, $recipient)->send();
    }
}
