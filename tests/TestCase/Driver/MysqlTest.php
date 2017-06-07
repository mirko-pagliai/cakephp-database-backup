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
namespace MysqlBackup\Test\TestCase\Driver;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use MysqlBackup\Driver\Mysql;
use Reflection\ReflectionTrait;

/**
 * MysqlTest class
 */
class MysqlTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \MysqlBackup\Driver\Mysql
     */
    protected $Mysql;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Mysql = new Mysql;
    }
    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->Mysql);
    }

    /**
     * Test for `_storeAuth()` method
     * @test
     */
    public function testStoreAuth()
    {
        $auth = $this->invokeMethod($this->Mysql, '_storeAuth');

        $this->assertFileExists($auth);

        $result = file_get_contents($auth);
        $expected = '[mysqldump]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $this->assertEquals($expected, $result);

        unlink($auth);
    }

    /**
     * Test for `_getExecutable()` method
     * @test
     */
    public function testGetExecutable()
    {
        $mysqldump = Configure::read(MYSQL_BACKUP . '.bin.mysqldump');
        $bzip2 = Configure::read(MYSQL_BACKUP . '.bin.bzip2');
        $gzip = Configure::read(MYSQL_BACKUP . '.bin.gzip');

        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s | ' . $bzip2 . ' > %s',
            $this->invokeMethod($this->Mysql, '_getExecutable', ['bzip2'])
        );
        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s | ' . $gzip . ' > %s',
            $this->invokeMethod($this->Mysql, '_getExecutable', ['gzip'])
        );
        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s > %s',
            $this->invokeMethod($this->Mysql, '_getExecutable', [false])
        );
    }

    /**
     * Test for `_getExecutable()` method, with the `bzip2` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `bzip2` executable not available
     * @test
     */
    public function testGetExecutableWithBzip2NotAvailable()
    {
        Configure::write(MYSQL_BACKUP . '.bin.bzip2', false);

        $this->invokeMethod($this->Mysql, '_getExecutable', ['bzip2']);
    }

    /**
     * Test for `_getExecutable()` method, with the `gzip` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `gzip` executable not available
     * @test
     */
    public function testGetExecutableWithGzipNotAvailable()
    {
        Configure::write(MYSQL_BACKUP . '.bin.gzip', false);

        $this->invokeMethod($this->Mysql, '_getExecutable', ['gzip']);
    }
}
