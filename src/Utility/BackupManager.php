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
use DatabaseBackup\Compression;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Utility to manage database backups.
 */
class BackupManager
{
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
}
