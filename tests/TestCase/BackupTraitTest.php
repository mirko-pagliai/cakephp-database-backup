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
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupManager;

class BackupTraitTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Utility\BackupManager
     */
    protected $Trait;

    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'core.articles',
        'core.comments',
    ];

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Trait = new BackupManager;
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->Trait);
    }

    /**
     * Test for `getAbsolutePath()` method
     * @test
     */
    public function testGetAbsolutePath()
    {
        $result = $this->Trait->getAbsolutePath('/file.txt');
        $this->assertEquals('/file.txt', $result);

        $result = $this->Trait->getAbsolutePath('file.txt');
        $this->assertEquals(Configure::read(DATABASE_BACKUP . '.target') . DS . 'file.txt', $result);

        $result = $this->Trait->getAbsolutePath(Configure::read(DATABASE_BACKUP . '.target') . DS . 'file.txt');
        $this->assertEquals(Configure::read(DATABASE_BACKUP . '.target') . DS . 'file.txt', $result);
    }

    /**
     * Test for `getBinary()` method
     * @test
     */
    public function testGetBinary()
    {
        $this->assertEquals(which('mysql'), $this->Trait->getBinary('mysql'));
    }

    /**
     * Test for `getBinary()` method, with a binary not available
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage `bzip2` executable not available
     * @test
     */
    public function testGetBinaryNotAvailable()
    {
        Configure::write(DATABASE_BACKUP . '.binaries.bzip2', false);

        $this->Trait->getBinary('bzip2');
    }

    /**
     * Test for `getClassShortName()` method
     * @test
     */
    public function testGetClassShortName()
    {
        $this->assertEquals('TestCase', $this->Trait->getClassShortName('\Cake\TestSuite\TestCase'));
        $this->assertEquals('TestCase', $this->Trait->getClassShortName('Cake\TestSuite\TestCase'));
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
            $this->assertEquals($expectedCompression, $this->Trait->getCompression($filename));
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
            $connection = $this->Trait->getConnection($name);
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
        $this->Trait->getConnection('noExisting');
    }

    /**
     * Test for `getDriver()` method
     * @test
     */
    public function testGetDriver()
    {
        $driver = $this->Trait->getDriver(ConnectionManager::get('test'));
        $this->assertInstanceof(DATABASE_BACKUP . '\Driver\Mysql', $driver);

        $driver = $this->Trait->getDriver();
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
            'text.txt' => null,
        ];

        foreach ($extensions as $filename => $expectedExtension) {
            $this->assertEquals($expectedExtension, $this->Trait->getExtension($filename));
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
        $connection = $this->getMockBuilder(get_class($this->Trait->getConnection()))
            ->setMethods(['getDriver'])
            ->setConstructorArgs([$this->Trait->getConnection()->config()])
            ->getMock();

        $connection->method('getDriver')
             ->will($this->returnValue(new \stdClass()));

        $this->Trait->getDriver($connection);
    }

    /**
     * Test for `getTarget()` method
     * @test
     */
    public function testGetTarget()
    {
        $this->assertEquals(Configure::read(DATABASE_BACKUP . '.target'), $this->Trait->getTarget());
    }
}
