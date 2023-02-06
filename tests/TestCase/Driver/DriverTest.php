<?php
/** @noinspection PhpUnhandledExceptionInspection */
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

namespace DatabaseBackup\Test\TestCase\Driver;

use DatabaseBackup\Driver\Driver;
use DatabaseBackup\TestSuite\TestCase;
use Symfony\Component\Process\Process;

/**
 * DriverTest class.
 *
 * Performs test that are valid for each driver class, thus covering the methods of the abstract `Driver` class.
 * @covers \DatabaseBackup\Driver\Driver
 */
class DriverTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Driver\Driver
     */
    protected Driver $Driver;

    /**
     * Internal method to get a mock for `Driver` abstract class
     * @param array $mockedMethods Mocked methods
     * @return \DatabaseBackup\Driver\Driver&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockForAbstractDriver(array $mockedMethods = []): Driver
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = $this->getConnection('test');

        return @$this->getMockForAbstractClass(Driver::class, [$connection], '', true, true, true, $mockedMethods);
    }

    /**
     * Internal method to get a mock for `Driver` abstract class, with the `_exec()` method that returns a `Process`
     *  instance with a failure and a custom error message
     * @param string $errorMessage The error message
     * @return \DatabaseBackup\Driver\Driver&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockForAbstractDriverWithErrorProcess(string $errorMessage): Driver
    {
        $Process = $this->createPartialMock(Process::class, ['getErrorOutput', 'isSuccessful']);
        $Process->method('getErrorOutput')->willReturn($errorMessage . PHP_EOL);
        $Process->method('isSuccessful')->willReturn(false);

        $Driver = $this->getMockForAbstractDriver(['_exec']);
        $Driver->method('_exec')->willReturn($Process);

        return $Driver;
    }

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (empty($this->Driver)) {
            $this->Driver = $this->getMockForAbstractDriver();
        }
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Driver::getBinary()
     */
    public function testGetBinary(): void
    {
        $this->assertStringEndsWith('mysql', $this->Driver->getBinary('mysql'));

        //With a binary not available
        $this->expectExceptionMessage('Binary for `noExisting` could not be found. You have to set its path manually');
        $this->Driver->getBinary('noExisting');
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Driver::getConfig()
     */
    public function testGetConfig(): void
    {
        $this->assertIsArrayNotEmpty($this->Driver->getConfig());
        $this->assertNotEmpty($this->Driver->getConfig('name'));
        $this->assertNull($this->Driver->getConfig('noExistingKey'));
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Driver::export()
     */
    public function testExportOnFailure(): void
    {
        $expectedError = 'mysqldump: Got error: 1044: "Access denied for user \'root\'@\'localhost\' to database \'noExisting\'" when selecting the database';

        $this->expectExceptionMessage('Export failed with error message: `' . $expectedError . '`');
        $Driver = $this->getMockForAbstractDriverWithErrorProcess($expectedError);
        $Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `export()` method. Export is stopped because the `beforeExport()` method returns `false`
     * @test
     * @uses \DatabaseBackup\Driver\Driver::export()
     */
    public function testExportStoppedByBeforeExport(): void
    {
        $Driver = $this->getMockForAbstractDriver(['beforeExport']);
        $Driver->method('beforeExport')->willReturn(false);
        $this->assertFalse($Driver->export($this->getAbsolutePath('example.sql')));
    }

    /**
     * Test for `import()` method on failure
     * @test
     * @uses \DatabaseBackup\Driver\Driver::import()
     */
    public function testImportOnFailure(): void
    {
        $expectedError = 'ERROR 1044 (42000): Access denied for user \'root\'@\'localhost\' to database \'noExisting\'';

        $this->expectExceptionMessage('Import failed with error message: `' . $expectedError . '`');
        $Driver = $this->getMockForAbstractDriverWithErrorProcess($expectedError);
        $Driver->import($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method. Import is stopped because the `beforeImport()` method returns `false`
     * @test
     * @uses \DatabaseBackup\Driver\Driver::import()
     */
    public function testImportStoppedByBeforeExport(): void
    {
        $Driver = $this->getMockForAbstractDriver(['beforeImport']);
        $Driver->method('beforeImport')->will($this->returnValue(false));
        $this->assertFalse($Driver->import($this->getAbsolutePath('example.sql')));
    }
}
