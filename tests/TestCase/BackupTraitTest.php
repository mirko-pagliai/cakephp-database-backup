<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * cakephp-database-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-database-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-database-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace DatabaseBackup\Test\TestCase;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
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
             ->will($this->returnCallback(function () {
                return new \stdClass();
             }));

        $this->Trait->getDriver($connection);
    }

    /**
     * Test for `getTables()` method
     * @test
     */
    public function testGetTables()
    {
        $this->loadAllFixtures();

        $this->assertEquals(['articles', 'comments'], $this->Trait->getTables());
    }

    /**
     * Test for `getTarget()` method
     * @test
     */
    public function testGetTarget()
    {
        $this->assertEquals(Configure::read(DATABASE_BACKUP . '.target'), $this->Trait->getTarget());
    }

    /**
     * Test for `truncateTables()` method
     * @test
     */
    public function testTruncateTables()
    {
        $articles = TableRegistry::get('Articles');
        $comments = TableRegistry::get('Comments');

        $this->loadAllFixtures();

        $this->assertGreaterThan(0, $articles->find()->count());
        $this->assertGreaterThan(0, $comments->find()->count());

        $this->Trait->truncateTables();

        $this->assertEquals(0, $articles->find()->count());
        $this->assertEquals(0, $comments->find()->count());
    }
}
