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
namespace DatabaseBackup\Test\TestCase;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\TestSuite\TestCase;

/**
 * BackupTraitTest class
 */
class BackupTraitTest extends TestCase
{
    use BackupTrait;

    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'core.Articles',
        'core.Comments',
    ];

    /**
     * Test for `getAbsolutePath()` method
     * @test
     */
    public function testGetAbsolutePath()
    {
        $result = $this->getAbsolutePath('/file.txt');
        $this->assertEquals('/file.txt', $result);

        $result = $this->getAbsolutePath('file.txt');
        $this->assertEquals(Configure::read(DATABASE_BACKUP . '.target') . DS . 'file.txt', $result);

        $result = $this->getAbsolutePath(Configure::read(DATABASE_BACKUP . '.target') . DS . 'file.txt');
        $this->assertEquals(Configure::read(DATABASE_BACKUP . '.target') . DS . 'file.txt', $result);
    }

    /**
     * Test for `getBinary()` method
     * @test
     */
    public function testGetBinary()
    {
        $this->assertEquals(which('mysql'), $this->getBinary('mysql'));
    }

    /**
     * Test for `getBinary()` method, with a binary not available
     * @expectedException RuntimeException
     * @expectedExceptionMessage Expected configuration key "DatabaseBackup.binaries.noExisting" not found.
     * @test
     */
    public function testGetBinaryNotAvailable()
    {
        $this->getBinary('noExisting');
    }

    /**
     * Test for `getCompression()` method
     * @test
     */
    public function testGetCompression()
    {
        $compressions = [
            'backup.sql' => false,
            'backup.sql.bz2' => 'bzip2',
            'backup.sql.gz' => 'gzip',
            'text.txt' => null,
        ];

        foreach ($compressions as $filename => $expectedCompression) {
            $this->assertEquals($expectedCompression, $this->getCompression($filename));
        }
    }

    /**
     * Test for `getConnection()` method
     * @test
     */
    public function testGetConnection()
    {
        ConnectionManager::setConfig('fake', ['url' => 'mysql://root:password@localhost/my_database']);

        foreach ([
            null,
            Configure::read(DATABASE_BACKUP . '.connection'),
            'fake',
        ] as $name) {
            $connection = $this->getConnection($name);
            $this->assertInstanceof('Cake\Database\Connection', $connection);
            $this->assertInstanceof('Cake\Database\Driver\Mysql', $connection->getDriver());
        }
    }

    /**
     * Test for `getConnection()` method, with an invalid connection
     * @expectedException \Cake\Datasource\Exception\MissingDatasourceConfigException
     * @expectedExceptionMessage The datasource configuration "noExisting" was not found.
     * @test
     */
    public function testGetConnectionInvalidConnection()
    {
        $this->getConnection('noExisting');
    }

    /**
     * Test for `getDriver()` method
     * @test
     */
    public function testGetDriver()
    {
        $driver = $this->getDriver(ConnectionManager::get('test'));
        $this->assertInstanceof(DATABASE_BACKUP . '\Driver\Mysql', $driver);

        $driver = $this->getDriver();
        $this->assertInstanceof(DATABASE_BACKUP . '\Driver\Mysql', $driver);
    }

    /**
     * Test for `getExtension()` method
     * @test
     */
    public function testGetExtension()
    {
        $extensions = [
            'backup.sql' => 'sql',
            'backup.sql.bz2' => 'sql.bz2',
            'backup.sql.gz' => 'sql.gz',
            'backup.SQL' => 'sql',
            'backup.SQL.BZ2' => 'sql.bz2',
            'backup.SQL.GZ' => 'sql.gz',
            'text.txt' => null,
            'text' => null,
            '.txt' => null,
        ];

        foreach ($extensions as $filename => $expectedExtension) {
            $this->assertEquals($expectedExtension, $this->getExtension($filename));
        }
    }

    /**
     * Test for `getDriver()` method, with a no existing driver
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The `stdClass` driver does not exist
     * @test
     */
    public function testGetDriverNoExistingDriver()
    {
        $connection = $this->getMockBuilder(get_class($this->getConnection()))
            ->setMethods(['getDriver'])
            ->setConstructorArgs([$this->getConnection()->config()])
            ->getMock();

        $connection->method('getDriver')
             ->will($this->returnValue(new \stdClass()));

        $this->getDriver($connection);
    }

    /**
     * Test for `getTarget()` method
     * @test
     */
    public function testGetTarget()
    {
        $this->assertEquals(Configure::read(DATABASE_BACKUP . '.target'), $this->getTarget());
    }

    /**
     * Test for `getValidCompressions()` method
     * @test
     */
    public function testGetValidCompressions()
    {
        $this->assertNotEmpty($this->getValidCompressions());
    }

    /**
     * Test for `getValidExtensions()` method
     * @test
     */
    public function testGetValidExtensions()
    {
        $this->assertNotEmpty($this->getValidExtensions());
    }
}
