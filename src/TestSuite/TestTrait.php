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
 */
namespace DatabaseBackup\TestSuite;

use DatabaseBackup\Utility\BackupExport;
use Tools\TestSuite\TestTrait as BaseTestTrait;

/**
 * A trait that provides some assertion methods
 */
trait TestTrait
{
    use BaseTestTrait;

    /**
     * Internal method to create a backup file
     * @param string $filename Filename
     * @return string
     */
    protected function createBackup($filename = 'backup.sql')
    {
        return (new BackupExport())->filename($filename)->export();
    }

    /**
     * Internal method to creates some backup files
     * @return array
     * @uses createBackup()
     */
    protected function createSomeBackups()
    {
        $timestamp = time();

        foreach (['sql.gz', 'sql.bz2', 'sql'] as $extension) {
            $file = $this->createBackup('backup_test_' . (string)$timestamp . '.' . $extension);
            touch($file, $timestamp--);
            $files[] = $file;
        }

        return array_reverse($files);
    }
}
