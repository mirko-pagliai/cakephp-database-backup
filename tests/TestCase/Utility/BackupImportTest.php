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
namespace MysqlBackup\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use MysqlBackup\Test\TestCase\Utility\BackupImport;
use MysqlBackup\Utility\BackupExport;

/**
 * BackupImportTest class
 */
class BackupImportTest extends TestCase
{
    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('MysqlBackup.bin.bzip2', which('bzip2'));
        Configure::write('MysqlBackup.bin.gzip', which('gzip'));
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        //Deletes all backups
        foreach (glob(Configure::read('MysqlBackup.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        //Creates a backup
        $backup = (new BackupExport())->export();

        $instance = new BackupImport($backup);

        $connection = $instance->getConnection();
        $this->assertEquals($connection['scheme'], 'mysql');
        $this->assertEquals($connection['database'], 'test');
        $this->assertEquals($connection['driver'], 'Cake\Database\Driver\Mysql');
    }

    /**
     * Test for `construct()` method. This tests `$compression` and `$filename`
     *  properties
     * @test
     */
    public function testConstructCompressionAndFilename()
    {
        //Creates a `sql` backup
        $backup = (new BackupExport())->filename('backup.sql')->export();

        $instance = new BackupImport($backup);

        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql', $instance->getFilename());
        $this->assertFalse($instance->getCompression());

        //Creates a `sql.bz2` backup
        $backup = (new BackupExport())->filename('backup.sql.bz2')->export();

        $instance = new BackupImport($backup);

        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql.bz2', $instance->getFilename());
        $this->assertEquals('bzip2', $instance->getCompression());

        //Creates a `sql.gz` backup
        $backup = (new BackupExport())->filename('backup.sql.gz')->export();

        $instance = new BackupImport($backup);

        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql.gz', $instance->getFilename());
        $this->assertEquals('gzip', $instance->getCompression());
    }

    /**
     * Test for `_getExecutable()` method
     * @test
     */
    public function testExecutable()
    {
        $mysql = Configure::read('MysqlBackup.bin.mysql');
        $bzip2 = Configure::read('MysqlBackup.bin.bzip2');
        $gzip = Configure::read('MysqlBackup.bin.gzip');

        //Creates a backup
        $backup = (new BackupExport())->export();

        $instance = new BackupImport($backup);

        $this->assertEquals($mysql . ' -dc %s | ' . $bzip2 . ' --defaults-extra-file=%s %s', $instance->getExecutable('bzip2'));
        $this->assertEquals($mysql . ' -dc %s | ' . $gzip . ' --defaults-extra-file=%s %s', $instance->getExecutable('gzip'));
        $this->assertEquals('cat %s | ' . $mysql . ' --defaults-extra-file=%s %s', $instance->getExecutable(false));
    }

    /**
     * Test for `_getExecutable()` method, with the `bzip2` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `bzip2` executable not available
     * @test
     */
    public function testExecutableWithBzip2NotAvailable()
    {
        Configure::write('MysqlBackup.bin.bzip2', false);

        //Creates a backup
        $backup = (new BackupExport())->export();

        (new BackupImport($backup))->getExecutable('bzip2');
    }

    /**
     * Test for `_getExecutable()` method, with the `gzip` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `gzip` executable not available
     * @test
     */
    public function testExecutableWithGzipNotAvailable()
    {
        Configure::write('MysqlBackup.bin.gzip', false);

        //Creates a backup
        $backup = (new BackupExport())->export();

        (new BackupImport($backup))->getExecutable('gzip');
    }
}
