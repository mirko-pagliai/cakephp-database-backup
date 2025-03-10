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
 * @since       2.0.0
 */

namespace DatabaseBackup\TestSuite;

use Cake\TestSuite\TestCase as CakeTestCase;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Compression;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;

/**
 * TestCase class.
 */
abstract class TestCase extends CakeTestCase
{
    use BackupTrait;

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        BackupManager::deleteAll();
    }

    /**
     * Creates a backup file for tests.
     *
     * @param string $filename
     * @param bool $fakeBackup With `true`, it will create a fake file (i.e. with empty content).
     * @return string
     */
    public function createBackup(string $filename = 'backup.sql', bool $fakeBackup = false): string
    {
        if ($fakeBackup) {
            $filename = TMP . 'backups' . DS . $filename;
            file_put_contents($filename, '');

            return $filename;
        }

        return (new BackupExport())->filename($filename)->export() ?: '';
    }

    /**
     * Creates some backup files for tests.
     *
     * @return array<string>
     */
    public function createSomeBackups(?int $timestamp = null): array
    {
        $files = [];
        $timestamp = $timestamp ?: time();

        foreach (array_reverse(Compression::cases()) as $Compression) {
            $timestamp--;
            $file = $this->createBackup('backup_test_' . $timestamp . '.' . $Compression->value);
            touch($file, $timestamp);
            $files[] = $file;
        }

        return array_reverse($files);
    }
}
