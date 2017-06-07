<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace MysqlBackup\Test\TestCase;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use MysqlBackup\Utility\BackupManager;

class BackupTraitTest extends TestCase
{
    /**
     * @var \MysqlBackup\Utility\BackupManager
     */
    protected $Trait;

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
        $this->assertEquals(Configure::read(MYSQL_BACKUP . '.target') . DS . 'file.txt', $result);

        $result = $this->Trait->getAbsolutePath(Configure::read(MYSQL_BACKUP . '.target') . DS . 'file.txt');
        $this->assertEquals(Configure::read(MYSQL_BACKUP . '.target') . DS . 'file.txt', $result);
    }

    /**
     * Test for `getCompression()` method
     * @test
     */
    public function testGetCompression()
    {
        $this->assertEquals(false, $this->Trait->getCompression('backup.sql'));
        $this->assertEquals('bzip2', $this->Trait->getCompression('backup.sql.bz2'));
        $this->assertEquals('gzip', $this->Trait->getCompression('backup.sql.gz'));
        $this->assertNull($this->Trait->getCompression('text.txt'));
    }

    /**
     * Test for `getConnection()` method
     * @test
     */
    public function testGetConnection()
    {
        $expected = [
            'scheme' => 'mysql',
            'host' => 'localhost',
            'username' => 'travis',
            'className' => 'Cake\Database\Connection',
            'database' => 'test',
            'driver' => 'Cake\Database\Driver\Mysql',
            'name' => 'test',
        ];

        $this->assertEquals($expected, $this->Trait->getConnection());
        $this->assertEquals($expected, $this->Trait->getConnection(Configure::read(MYSQL_BACKUP . '.connection')));

        ConnectionManager::setConfig('fake', ['url' => 'mysql://root:password@localhost/my_database']);

        $expected = [
            'scheme' => 'mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => 'password',
            'className' => 'Cake\Database\Connection',
            'database' => 'my_database',
            'driver' => 'Cake\Database\Driver\Mysql',
            'name' => 'fake',
        ];

        $this->assertEquals($expected, $this->Trait->getConnection('fake'));
    }

    /**
     * Test for `getDriver()` method
     * @test
     */
    public function testGetDriver()
    {
        $driver = $this->Trait->getDriver([
            'scheme' => 'mysql',
            'host' => 'localhost',
            'username' => 'travis',
            'className' => 'Cake\Database\Connection',
            'database' => 'test',
            'driver' => 'Cake\Database\Driver\Mysql',
            'name' => 'test',
        ]);
        $this->assertInstanceof('MysqlBackup\Driver\Mysql', $driver);

        $driver = $this->Trait->getDriver();
        $this->assertInstanceof('MysqlBackup\Driver\Mysql', $driver);
    }

    /**
     * Test for `getDriver()` method, with an invalid argument
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to detect the driver to use
     */
    public function testGetDriverInvalidArgument()
    {
        $this->Trait->getDriver(['invalid']);
    }

    /**
     * Test for `getExtension()` method
     * @test
     */
    public function testGetExtension()
    {
        //Using compression types
        $this->assertEquals('sql', $this->Trait->getExtension(false));
        $this->assertEquals('sql.bz2', $this->Trait->getExtension('bzip2'));
        $this->assertEquals('sql.gz', $this->Trait->getExtension('gzip'));
        $this->assertNull($this->Trait->getExtension('noExisting'));

        //Using filenames
        $this->assertEquals('sql', $this->Trait->getExtension('backup.sql'));
        $this->assertEquals('sql.bz2', $this->Trait->getExtension('backup.sql.bz2'));
        $this->assertEquals('sql.gz', $this->Trait->getExtension('backup.sql.gz'));
        $this->assertNull($this->Trait->getExtension('text.txt'));
    }

    /**
     * Test for `getValidCompressions()` method
     * @test
     */
    public function testGetValidCompressions()
    {
        $this->assertEquals([
            'sql.bz2' => 'bzip2',
            'sql.gz' => 'gzip',
            'sql' => false,
        ], $this->Trait->getValidCompressions());
    }

    /**
     * Test for `getTarget()` method
     * @test
     */
    public function testGetTarget()
    {
        $this->assertEquals(Configure::read(MYSQL_BACKUP . '.target'), $this->Trait->getTarget());
    }
}
