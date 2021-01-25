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

use Cake\Database\Connection;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\TestCase;
use ErrorException;

/**
 * DriverTest class
 */
class DriverTest extends TestCase
{
    /**
     * `Driver` instance
     * @var \DatabaseBackup\Driver\Driver
     */
    protected $Driver;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Driver = $this->Driver ?? new Mysql($this->getConnection('test'));
    }

    /**
     * Test for `__construct()` method
     * @return void
     * @test
     */
    public function testConstruct()
    {
        $this->assertInstanceof(Connection::class, $this->getProperty($this->Driver, 'connection'));
    }

    /**
     * Test for `export()` method on failure
     * @return void
     * @since 2.6.2
     * @test
     */
    public function testExportOnFailure()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageMatches('/^Failed with exit code `\d`$/');
        $config = ['database' => 'noExisting'] + $this->Driver->getConfig();
        $this->setProperty($this->Driver, 'connection', new Connection($config));
        $this->Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `export()` method. Export is stopped because the
     *  `beforeExport()` method returns `false`
     * @return void
     * @test
     */
    public function testExportStoppedByBeforeExport()
    {
        $backup = $this->getAbsolutePath('example.sql');
        $Driver = $this->getMockForDriver(Mysql::class, ['beforeExport']);
        $Driver->method('beforeExport')->will($this->returnValue(false));
        $this->assertFalse($Driver->export($backup));
        $this->assertFileDoesNotExist($backup);
    }

    /**
     * Test for `getBinary()` method
     * @test
     */
    public function testGetBinary()
    {
        $this->assertEquals(which('mysql'), $this->invokeMethod($this->Driver, 'getBinary', ['mysql']));

        //With a binary not available
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Binary for `noExisting` could not be found. You have to set its path manually');
        $this->invokeMethod($this->Driver, 'getBinary', ['noExisting']);
    }

    /**
     * Test for `getConfig()` method
     * @return void
     * @test
     */
    public function testGetConfig()
    {
        $this->assertIsArrayNotEmpty($this->Driver->getConfig());
        $this->assertNotEmpty($this->Driver->getConfig('name'));
        $this->assertNull($this->Driver->getConfig('noExistingKey'));
    }

    /**
     * Test for `import()` method on failure
     * @return void
     * @since 2.6.2
     * @test
     */
    public function testImportOnFailure()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageMatches('/^Failed with exit code `\d`$/');
        $backup = $this->getAbsolutePath('example.sql');
        $this->Driver->export($backup);
        $config = ['database' => 'noExisting'] + $this->Driver->getConfig();
        $this->setProperty($this->Driver, 'connection', new Connection($config));
        $this->Driver->import($backup);
    }

    /**
     * Test for `import()` method. Import is stopped because the
     *  `beforeImport()` method returns `false`
     * @return void
     * @test
     */
    public function testImportStoppedByBeforeExport()
    {
        $backup = $this->getAbsolutePath('example.sql');
        $Driver = $this->getMockForDriver(Mysql::class, ['beforeImport']);
        $Driver->method('beforeImport')->will($this->returnValue(false));
        $this->assertTrue($Driver->export($backup));
        $this->assertFalse($Driver->import($backup));
    }
}
