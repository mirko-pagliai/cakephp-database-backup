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
use DatabaseBackup\Driver\Driver;
use DatabaseBackup\Utility\BackupManager;
use MeTools\TestSuite\TestCase as BaseTestCase;
use Symfony\Component\Process\Process;

/**
 * TestCase class
 */
abstract class TestCase extends BaseTestCase
{
    use BackupTrait;

    /**
     * Called after every test method
     * @return void
     */
    public function tearDown(): void
    {
        BackupManager::deleteAll();

        parent::tearDown();
    }

    /**
     * Internal method to get a mock for `Driver` abstract class
     * @param array $mockedMethods Mocked methods
     * @return \DatabaseBackup\Driver\Driver&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockForAbstractDriver(array $mockedMethods = []): Driver
    {
        /** @var \Cake\Database\Connection $Connection */
        $Connection = $this->getConnection('test');
        /** @var \DatabaseBackup\Driver\Driver&\PHPUnit\Framework\MockObject\MockObject $Driver */
        $Driver = $this->createPartialMockForAbstractClass(Driver::class, $mockedMethods, [$Connection]);

        return $Driver;
    }

    /**
     * Internal method to get a mock for `Driver` abstract class, with the `_exec()` method that returns a `Process`
     *  instance with a failure and a custom error message
     * @param string $errorMessage The error message
     * @return \DatabaseBackup\Driver\Driver&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockForAbstractDriverWithErrorProcess(string $errorMessage): Driver
    {
        $Process = $this->createConfiguredMock(Process::class, ['getErrorOutput' => $errorMessage . PHP_EOL, 'isSuccessful' => false]);
        $Driver = $this->getMockForAbstractDriver(['_exec']);
        $Driver->method('_exec')->willReturn($Process);

        return $Driver;
    }
}
