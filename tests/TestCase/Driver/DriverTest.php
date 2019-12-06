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

        $this->Driver = new Mysql($this->getConnection('test'));
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
        $this->expectExceptionMessageRegExp('/^Failed with exit code `\d`$/');
        //Sets a no existing database
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
        $this->assertFileNotExists($backup);
    }

    /**
     * Test for `getConfig()` method
     * @return void
     * @test
     */
    public function testGetConfig()
    {
        $this->assertNotEmpty($this->Driver->getConfig());
        $this->assertIsArray($this->Driver->getConfig());
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
        $backup = $this->getAbsolutePath('example.sql');

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessageRegExp('/^Failed with exit code `\d`$/');
        $this->Driver->export($backup);

        //Sets a no existing database
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
