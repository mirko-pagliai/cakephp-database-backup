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
 *
 * @deprecated `BackupManager` has been deprecated and will be removed in a future release
 */
class BackupManager
{
    /**
     * Returns a list of database backups.
     *
     * @return \Cake\Collection\CollectionInterface<int, array{basename: string, path: string, compression: \DatabaseBackup\Compression, size: int|false, datetime: \Cake\I18n\DateTime}>
     * @deprecated `BackupManager::index()` has been deprecated and will be removed in a future release
     */
    public static function index(): CollectionInterface
    {
        deprecationWarning(
            '2.15.0',
            '`BackupManager::index()` has been deprecated and will be removed in a future release',
        );

        $Finder = new Finder();
        $Finder->files()
            ->in(Configure::readOrFail('DatabaseBackup.target'))
            ->name('/\.sql(\.(gz|bz2))?$/')
            //Sorts in descending order by the last-modified date
            ->sort(fn(SplFileInfo $a, SplFileInfo $b): int => $b->getMTime() - $a->getMTime());

        $DateTimeZone = DateTime::now()->getTimezone();

        /** @var \Cake\Collection\CollectionInterface<int, array{basename: string, path: string, compression: \DatabaseBackup\Compression, size: int|false, datetime: \Cake\I18n\DateTime}> $collection */
        $collection = (new Collection($Finder))
            ->map(fn(SplFileInfo $File): array => [
                'basename' => $File->getBasename(),
                'path' => $File->getPathname(),
                'compression' => Compression::fromFilename($File->getFilename()),
                'size' => $File->getSize(),
                'datetime' => DateTime::createFromTimestamp($File->getMTime(), $DateTimeZone),
            ])
            ->compile(false);

        return $collection;
    }

    /**
     * Rotates backups.
     *
     * You must indicate the number of backups you want to keep. So, it will delete all backups that are older.
     *
     * @param int $keep Number of backups that you want to keep
     * @return array<int, array{basename: string, path: string, compression: \DatabaseBackup\Compression, size: int|false, datetime: \Cake\I18n\DateTime}>
     * @throws \InvalidArgumentException With an Invalid rotate value.
     * @deprecated `BackupManager::rotate()` has been deprecated and will be removed in a future release
     */
    public static function rotate(int $keep): array
    {
        deprecationWarning(
            '2.15.0',
            '`BackupManager::rotate()` has been deprecated and will be removed in a future release',
        );

        if ($keep < 1) {
            throw new InvalidArgumentException(__d('database_backup', 'Invalid `$keep` value'));
        }

        return self::index()
            ->skip($keep)
            ->each(fn(array $file) => unlink($file['path']))
            ->toList();
    }
}
