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
        $instance = new BackupImport();

        $connection = $instance->getConnection();
        $this->assertEquals($connection['scheme'], 'mysql');
        $this->assertEquals($connection['database'], 'test');
        $this->assertEquals($connection['driver'], 'Cake\Database\Driver\Mysql');
    }

    /**
     * Test for `filename()` method. This tests also `$compression` property
     * @test
     */
    public function testFilename()
    {
        $instance = new BackupImport();

        //Creates a `sql` backup
        $backup = (new BackupExport())->filename('backup.sql')->export();

        $instance->filename($backup);
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql', $instance->getFilename());
        $this->assertFalse($instance->getCompression());

        //Creates a `sql.bz2` backup
        $backup = (new BackupExport())->filename('backup.sql.bz2')->export();

        $instance->filename($backup);
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql.bz2', $instance->getFilename());
        $this->assertEquals('bzip2', $instance->getCompression());

        //Creates a `sql.gz` backup
        $backup = (new BackupExport())->filename('backup.sql.gz')->export();

        $instance->filename($backup);
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql.gz', $instance->getFilename());
        $this->assertEquals('gzip', $instance->getCompression());

        //Relative path
        $instance->filename(basename($backup));
        $this->assertEquals(Configure::read('MysqlBackup.target') . DS . 'backup.sql.gz', $instance->getFilename());
        $this->assertEquals('gzip', $instance->getCompression());
    }

    /**
     * Test for `filename()` method, with invalid directory
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File or directory `/tmp/backups/noExistingDir/backup.sql` not readable
     * @test
     */
    public function testFilenameWithInvalidDirectory()
    {
        (new BackupImport())->filename('noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with invalid extension
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testFilenameWithInvalidExtension()
    {
        file_put_contents(Configure::read('MysqlBackup.target') . DS . 'backup.txt', null);
        (new BackupImport())->filename('backup.txt');
    }

    /**
     * Test for `_storeAuth()` method
     * @test
     */
    public function testStoreAuth()
    {
        $auth = (new BackupImport())->getAuth();

        $this->assertFileExists($auth);

        $result = file_get_contents($auth);
        $expected = '[client]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $this->assertEquals($expected, $result);

        unlink($auth);
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

        $this->assertEquals($bzip2 . ' -dc %s | ' . $mysql . ' --defaults-extra-file=%s %s', (new BackupImport())->getExecutable('bzip2'));
        $this->assertEquals($gzip . ' -dc %s | ' . $mysql . ' --defaults-extra-file=%s %s', (new BackupImport())->getExecutable('gzip'));
        $this->assertEquals('cat %s | ' . $mysql . ' --defaults-extra-file=%s %s', (new BackupImport())->getExecutable(false));
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

        (new BackupImport())->getExecutable('bzip2');
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

        (new BackupImport())->getExecutable('gzip');
    }

    /**
     * Test for `import()` method, without compression
     * @test
     */
    public function testImport()
    {
        //Exports and imports a `sql` backup
        $backup = (new BackupExport())->compression(false)->export();
        $filename = (new BackupImport())->filename($backup)->import();

        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql$/', basename($filename));
    }

    /**
     * Test for `import()` method, with `bzip2` compression
     * @test
     */
    public function testImportBzip2()
    {
        //Exports and imports a `sql` backup
        $backup = (new BackupExport())->compression('bzip2')->export();
        $filename = (new BackupImport())->filename($backup)->import();

        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.bz2$/', basename($filename));
    }

    /**
     * Test for `import()` method, with `gzip` compression
     * @test
     */
    public function testImportGzip()
    {
        //Exports and imports a `sql` backup
        $backup = (new BackupExport())->compression('gzip')->export();
        $filename = (new BackupImport())->filename($backup)->import();

        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.gz$/', basename($filename));
    }

    /**
     * Test for `import()` method, without a filename
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Before you import a database, you have to set the filename
     * @test
     */
    public function testImportWithoutFilename()
    {
        (new BackupImport())->import();
    }
}
