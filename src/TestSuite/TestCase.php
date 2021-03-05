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

use DatabaseBackup\BackupTrait;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use MeTools\TestSuite\TestCase as BaseTestCase;
use Tools\TestSuite\BackwardCompatibilityTrait;

/**
 * TestCase class
 */
abstract class TestCase extends BaseTestCase
{
    use BackwardCompatibilityTrait;
    use BackupTrait;

    /**
     * `BackupManager` instance
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupExport = $this->BackupExport ?? new BackupExport();
    }

    /**
     * Called after every test method
     * @return void
     */
    public function tearDown(): void
    {
        //Deletes all backup files
        BackupManager::deleteAll();

        parent::tearDown();
    }

    /**
     * Internal method to create a backup file
     * @param string $filename Filename
     * @return string
     */
    protected function createBackup(string $filename = 'backup.sql'): string
    {
        return $this->BackupExport->filename($filename)->export();
    }

    /**
     * Internal method to creates some backup files
     * @return array
     * @uses createBackup()
     */
    protected function createSomeBackups(): array
    {
        $timestamp = time();

        foreach (['sql.gz', 'sql.bz2', 'sql'] as $extension) {
            $file = $this->createBackup('backup_test_' . (string)$timestamp . '.' . $extension);
            touch($file, $timestamp--);
            $files[] = $file;
        }

        return array_reverse($files);
    }

    /**
     * Internal method to mock a driver
     * @param class-string<\DatabaseBackup\Driver\Driver> $className Driver class name
     * @param array|null $methods The list of methods to mock
     * @return \DatabaseBackup\Driver\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockForDriver(string $className, ?array $methods = []): object
    {
        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getConnection('test')])
            ->getMock();
    }
}
