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
use Cake\I18n\DateTime;
use Cake\Mailer\Mailer;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Compression;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Utility to manage database backups.
 */
class BackupManager
{
    use BackupTrait;

    /**
     * Deletes a backup file.
     *
     * @param string $filename Backup filename you want to delete. The path can be relative to the backup directory
     * @return string Deleted backup file
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#delete
     * @throws \LogicException
     */
    public static function delete(string $filename): string
    {
        $filename = self::getAbsolutePath($filename);
        if (!is_writable($filename)) {
            throw new LogicException(__d('database_backup', 'File or directory `{0}` is not writable', $filename));
        }
        unlink($filename);

        return $filename;
    }

    /**
     * Deletes all backup files.
     *
     * @return array<string> List of deleted backup files
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#deleteAll
     * @since 1.0.1
     */
    public static function deleteAll(): array
    {
        return self::index()
            ->extract('path')
            ->each(fn (string $path) => unlink($path))
            ->toList();
    }

    /**
     * Returns a list of database backups.
     *
     * @return \Cake\Collection\CollectionInterface
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

        $DateTimeZone = DateTime::now()->getTimezone();

        return (new Collection($Finder))
            ->map(fn (SplFileInfo $File): array => [
                /** @todo remove `filename` in version 2.14.0 */
                'filename' => $File->getFilename(),
                'basename' => $File->getBasename(),
                'path' => $File->getPathname(),
                'compression' => Compression::fromFilename($File->getFilename()),
                'size' => $File->getSize(),
                'datetime' => DateTime::createFromTimestamp($File->getMTime(), $DateTimeZone),
            ])
            ->compile(false);
    }

    /**
     * Rotates backups.
     *
     * You must indicate the number of backups you want to keep. So, it will delete all backups that are older.
     *
     * @param int $keep Number of backups that you want to keep
     * @return array<array{filename: string, basename: string, path: string, compression: \DatabaseBackup\Compression, size: int|false, datetime: \Cake\I18n\DateTime}>
     * @throws \InvalidArgumentException With an Invalid rotate value.
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupManager-utility#rotate
     */
    public static function rotate(int $keep): array
    {
        if ($keep < 1) {
            throw new InvalidArgumentException(__d('database_backup', 'Invalid `$keep` value'));
        }

        return self::index()
            ->skip($keep)
            ->each(fn (array $file) => unlink($file['path']))
            ->toList();
    }

    /**
     * Internal method to get an email instance with all options to send a backup file via email.
     *
     * @param string $backup Backup you want to send
     * @param string $recipient Recipient's email address
     * @return \Cake\Mailer\Mailer
     * @since 1.1.0
     * @throws \LogicException
     * @deprecated 2.13.4: `BackupManager::getEmailInstance()` method is deprecated. Will be removed in a future release
     */
    protected static function getEmailInstance(string $backup, string $recipient): Mailer
    {
        $filename = self::getAbsolutePath($backup);
        if (!is_readable($filename)) {
            throw new LogicException(__d('database_backup', 'File or directory `{0}` is not readable', $filename));
        }
        $server = env('SERVER_NAME', 'localhost');

        return (new Mailer())
            ->setFrom(Configure::readOrFail('DatabaseBackup.mailSender'))
            ->setTo($recipient)
            ->setSubject(__d('database_backup', 'Database backup {0} from {1}', basename($filename), $server))
            ->setAttachments([
                basename($filename) => ['file' => $filename, 'mimetype' => mime_content_type($filename)],
            ]);
    }

    /**
     * Sends a backup file via email.
     *
     * @param string $filename Backup filename you want to send via email. The path can be relative to the backup directory
     * @param string $recipient Recipient's email address
     * @return array{headers: string, message: string}
     * @throws \LogicException
     * @since 1.1.0
     * @deprecated 2.13.4: the `BackupManager::send()` method is deprecated. Will be removed in a future release
     */
    public static function send(string $filename, string $recipient): array
    {
        deprecationWarning('2.13.4', 'The `BackupManager::send()` method is deprecated. Will be removed in a future release');

        return self::getEmailInstance($filename, $recipient)->send();
    }
}
