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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility
 */
namespace DatabaseBackup\Utility;

use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Email;
use Cake\ORM\Entity;
use DatabaseBackup\BackupTrait;
use InvalidArgumentException;

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
     * @throws \Tools\Exception\NotWritableException
     */
    public function delete(string $filename): bool
    {
        $filename = $this->getAbsolutePath($filename);
        is_writable_or_fail($filename);

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
    public function deleteAll(): array
    {
        return array_filter(array_map(function (Entity $file) {
            return !$this->delete($file->filename) ?: $file->filename;
        }, $this->index()->toList()));
    }

    /**
     * Returns a list of database backups
     * @return \Cake\Collection\Collection Collection of backups. Each backup
     *  is an entity
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#index
     */
    public function index(): Collection
    {
        $target = $this->getTarget();

        return collection((new Folder($target))->find('.+\.sql(\.(gz|bz2))?'))
            ->map(function (string $filename) use ($target) {
                return new Entity([
                    'filename' => $filename,
                    'extension' => $this->getExtension($filename),
                    'compression' => $this->getCompression($filename),
                    'size' => filesize($target . DS . $filename),
                    'datetime' => new FrozenTime(date('Y-m-d H:i:s', filemtime($target . DS . $filename))),
                ]);
            })
            ->sortBy('datetime');
    }

    /**
     * Rotates backups.
     *
     * You must indicate the number of backups you want to keep. So, it will
     *  delete all backups that are older.
     * @param int $rotate Number of backups that you want to keep
     * @return array Array of deleted files
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#rotate
     * @throws \InvalidArgumentException
     * @uses delete()
     * @uses index()
     */
    public function rotate(int $rotate): array
    {
        is_true_or_fail(
            is_positive($rotate),
            __d('database_backup', 'Invalid rotate value'),
            InvalidArgumentException::class
        );
        $backupsToBeDeleted = $this->index()->skip((int)$rotate);

        //Deletes
        foreach ($backupsToBeDeleted as $backup) {
            $this->delete($backup->filename);
        }

        return $backupsToBeDeleted->toArray();
    }

    /**
     * Internal method to get an email instance with all options to send a
     *  backup file via email
     * @param string $backup Backup you want to send
     * @param string $recipient Recipient's email address
     * @return \Cake\Mailer\Email
     * @since 1.1.0
     * @throws \Tools\Exception\NotReadableException
     */
    protected function getEmailInstance(string $backup, string $recipient): Email
    {
        $file = $this->getAbsolutePath($backup);
        is_readable_or_fail($file);
        $basename = basename($file);
        $server = env('SERVER_NAME', 'localhost');

        return (new Email())
            ->setFrom(Configure::readOrFail('DatabaseBackup.mailSender'))
            ->setTo($recipient)
            ->setSubject(__d('database_backup', 'Database backup {0} from {1}', $basename, $server))
            ->setAttachments([$basename => compact('file') + ['mimetype' => mime_content_type($file)]]);
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
    public function send(string $filename, string $recipient): array
    {
        return $this->getEmailInstance($filename, $recipient)->send();
    }
}
