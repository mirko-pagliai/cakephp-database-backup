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
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Mailer;
use DatabaseBackup\BackupTrait;
use LogicException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Utility to manage database backups
 */
class BackupManager
{
    use BackupTrait;

    /**
     * Deletes a backup file
     * @param string $filename Backup filename you want to delete. The path can be relative to the backup directory
     * @return string Deleted backup file
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#delete
     * @throws \LogicException
     */
    public static function delete(string $filename): string
    {
        $filename = self::getAbsolutePath($filename);
        if (!is_writable($filename)) {
            throw new LogicException(__d('database_backup', 'File or directory `' . $filename . '` is not writable'));
        }
        (new Filesystem())->remove($filename);

        return $filename;
    }

    /**
     * Deletes all backup files
     * @return string[] List of deleted backup files
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#deleteAll
     * @since 1.0.1
     */
    public static function deleteAll(): array
    {
        return array_map([__CLASS__, 'delete'], self::index()->extract('filename')->toList());
    }

    /**
     * Returns a list of database backups
     * @return \Cake\Collection\CollectionInterface Array of backups
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#index
     */
    public static function index(): CollectionInterface
    {
        $Finder = new Finder();
        $Finder->files()
            ->in(Configure::readOrFail('DatabaseBackup.target'))
            ->name('/\.sql(\.(gz|bz2))?$/')
            //Sorts in descending order by last modified date
            ->sort(fn (SplFileInfo $a, SplFileInfo $b): bool => $a->getMTime() < $b->getMTime());

        return (new Collection($Finder))
            ->map(fn (SplFileInfo $File): array => [
                'filename' => $File->getFilename(),
                'extension' => self::getExtension($File->getFilename()),
                'compression' => self::getCompression($File->getFilename()),
                'size' => $File->getSize(),
                'datetime' => FrozenTime::createFromTimestamp($File->getMTime()),
            ])
            ->compile(false);
    }

    /**
     * Rotates backups.
     *
     * You must indicate the number of backups you want to keep. So, it will delete all backups that are older.
     * @param int $rotate Number of backups that you want to keep
     * @return array<\Cake\ORM\Entity> Array of deleted files
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#rotate
     * @throws \LogicException
     */
    public static function rotate(int $rotate): array
    {
        if (!($rotate >= 1)) {
            throw new LogicException(__d('database_backup', 'Invalid rotate value'));
        }
        $backupsToBeDeleted = self::index()->skip($rotate);
        array_map([__CLASS__, 'delete'], $backupsToBeDeleted->extract('filename')->toList());

        return $backupsToBeDeleted->toList();
    }

    /**
     * Internal method to get an email instance with all options to send a backup file via email
     * @param string $backup Backup you want to send
     * @param string $recipient Recipient's email address
     * @return \Cake\Mailer\Mailer
     * @since 1.1.0
     * @throws \LogicException
     */
    protected static function getEmailInstance(string $backup, string $recipient): Mailer
    {
        $filename = self::getAbsolutePath($backup);
        if (!is_readable($filename)) {
            throw new LogicException(__d('database_backup', 'File or directory `' . $filename . '` is not readable'));
        }
        $server = env('SERVER_NAME', 'localhost');

        return (new Mailer())
            ->setFrom(Configure::readOrFail('DatabaseBackup.mailSender'))
            ->setTo($recipient)
            ->setSubject(__d('database_backup', 'Database backup {0} from {1}', basename($filename), $server))
            ->setAttachments([basename($filename) => ['file' => $filename, 'mimetype' => mime_content_type($filename)]]);
    }

    /**
     * Sends a backup file via email
     * @param string $filename Backup filename you want to send via email. The path can be relative to the backup directory
     * @param string $recipient Recipient's email address
     * @return array{headers: string, message: string}
     * @throws \LogicException
     * @since 1.1.0
     */
    public static function send(string $filename, string $recipient): array
    {
        return self::getEmailInstance($filename, $recipient)->send();
    }
}
