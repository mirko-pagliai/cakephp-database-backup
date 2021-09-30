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

namespace DatabaseBackup\Test\TestCase\Driver;

use DatabaseBackup\Driver\Driver;
use DatabaseBackup\TestSuite\TestCase;
use ErrorException;

/**
 * DriverTest class.
 *
 * Performs tests that are valid for each driver class, thus covering the
 *  methods of the abstract `Driver` class.
 * @covers \DatabaseBackup\Driver\Driver
 */
class DriverTest extends TestCase
{
    /**
     * `Driver` instance
     * @var \DatabaseBackup\Driver\Driver
     */
    protected $Driver;

    /**
     * Internal method to get a mock for `Driver` abstract class
     * @param array $mockedMethods Mocked methods
     * @return \DatabaseBackup\Driver\Driver
     */
    protected function getMockForAbstractDriver(array $mockedMethods = []): Driver
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = $this->getConnection('test');

        return $this->getMockForAbstractClass(Driver::class, [$connection], '', true, true, true, $mockedMethods);
    }

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Driver = $this->Driver ?: $this->getMockForAbstractDriver();
    }

    /**
     * Test for `export()` method on failure
     * @return void
     * @test
     */
    public function testExportOnFailure(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageMatches('/^Failed with exit code `\d`$/');
        $Driver = $this->getMockForAbstractDriver(['_exec']);
        $Driver->method('_exec')->will($this->returnValue(1));
        $Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `export()` method. Export is stopped because the
     *  `beforeExport()` method returns `false`
     * @return void
     * @test
     */
    public function testExportStoppedByBeforeExport(): void
    {
        $Driver = $this->getMockForAbstractDriver(['beforeExport']);
        $Driver->method('beforeExport')->will($this->returnValue(false));
        $this->assertFalse($Driver->export($this->getAbsolutePath('example.sql')));
    }

    /**
     * Test for `getBinary()` method
     * @test
     */
    public function testGetBinary(): void
    {
        $this->assertEquals(which('mysql'), $this->Driver->getBinary('mysql'));

        //With a binary not available
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Binary for `noExisting` could not be found. You have to set its path manually');
        $this->Driver->getBinary('noExisting');
    }

    /**
     * Test for `getConfig()` method
     * @return void
     * @test
     */
    public function testGetConfig(): void
    {
        $this->assertIsArrayNotEmpty($this->Driver->getConfig());
        $this->assertNotEmpty($this->Driver->getConfig('name'));
        $this->assertNull($this->Driver->getConfig('noExistingKey'));
    }

    /**
     * Test for `import()` method on failure
     * @return void
     * @test
     */
    public function testImportOnFailure(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageMatches('/^Failed with exit code `\d`$/');
        $Driver = $this->getMockForAbstractDriver(['_exec']);
        $Driver->method('_exec')->will($this->returnValue(1));
        $Driver->import($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method. Import is stopped because the
     *  `beforeImport()` method returns `false`
     * @return void
     * @test
     */
    public function testImportStoppedByBeforeExport(): void
    {
        $Driver = $this->getMockForAbstractDriver(['beforeImport']);
        $Driver->method('beforeImport')->will($this->returnValue(false));
        $this->assertFalse($Driver->import($this->getAbsolutePath('example.sql')));
    }
}
