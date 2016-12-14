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
use MysqlBackup\Utility\BackupExport;
use Reflection\ReflectionTrait;

/**
 * BackupExportTest class
 */
class BackupExportTest extends TestCase
{
    use ReflectionTrait;

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
        $instance = new BackupExport();

        $connection = $this->getProperty($instance, 'connection');
        $this->assertEquals($connection['scheme'], 'mysql');
        $this->assertEquals($connection['database'], 'test');
        $this->assertEquals($connection['driver'], 'Cake\Database\Driver\Mysql');

        $this->assertNull($this->getProperty($instance, 'compression'));
        $this->assertEquals('sql', $this->getProperty($instance, 'extension'));
        $this->assertNull($this->getProperty($instance, 'filename'));
        $this->assertNull($this->getProperty($instance, 'rotate'));
    }

    /**
     * Test for `compression()` method. This also tests for `$extension`
     *  property
     * @test
     */
    public function testCompression()
    {
        $instance = new BackupExport();

        $instance->compression('bzip2');
        $this->assertEquals('bzip2', $this->getProperty($instance, 'compression'));
        $this->assertEquals('sql.bz2', $this->getProperty($instance, 'extension'));

        $instance->compression('gzip');
        $this->assertEquals('gzip', $this->getProperty($instance, 'compression'));
        $this->assertEquals('sql.gz', $this->getProperty($instance, 'extension'));

        $instance->compression(false);
        $this->assertEquals(false, $this->getProperty($instance, 'compression'));
        $this->assertEquals('sql', $this->getProperty($instance, 'extension'));
    }

    /**
     * Test for `compression()` method, with an invalid stringvalue
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidString()
    {
        (new BackupExport())->compression('invalidValue');
    }

    /**
     * Test for `compression()` method, with an invalid boolean value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidBool()
    {
        (new BackupExport())->compression(true);
    }

    /**
     * Test for `filename()` method. This also tests for `$compression`
     * @test
     */
    public function testFilename()
    {
        $instance = new BackupExport();

        $instance->filename('backup.sql');
        $this->assertEquals('/tmp/backups/backup.sql', $this->getProperty($instance, 'filename'));
        $this->assertFalse($this->getProperty($instance, 'compression'));

        $instance->filename('backup.sql.gz');
        $this->assertEquals('/tmp/backups/backup.sql.gz', $this->getProperty($instance, 'filename'));
        $this->assertEquals('gzip', $this->getProperty($instance, 'compression'));

        $instance->filename('backup.sql.bz2');
        $this->assertEquals('/tmp/backups/backup.sql.bz2', $this->getProperty($instance, 'filename'));
        $this->assertEquals('bzip2', $this->getProperty($instance, 'compression'));

        //Absolute path
        $instance->filename('/tmp/backups/other.sql');
        $this->assertEquals('/tmp/backups/other.sql', $this->getProperty($instance, 'filename'));
        $this->assertFalse($this->getProperty($instance, 'compression'));
    }

    /**
     * Test for `filename()` method. This checks that the `filename()` method
     *  overwrites the `compression()` method
     * @test
     */
    public function testFilenameRewritesCompression()
    {
        $instance = new BackupExport();

        $instance->compression('gzip')->filename('backup.sql.bz2');
        $this->assertEquals('backup.sql.bz2', basename($this->getProperty($instance, 'filename')));
        $this->assertEquals('bzip2', $this->getProperty($instance, 'compression'));

        $instance->compression('bzip2')->filename('backup.sql');
        $this->assertEquals('backup.sql', basename($this->getProperty($instance, 'filename')));
        $this->assertFalse($this->getProperty($instance, 'compression'));
    }

    /**
     * Test for `filename()` method, with a file that already exists
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File `/tmp/backups/backup.sql` already exists
     */
    public function testFilenameAlreadyExists()
    {
        (new BackupExport())->filename('backup.sql')->export();

        //Again, same filename
        (new BackupExport())->filename('backup.sql')->export();
    }

    /**
     * Test for `filename()` method, with patterns
     * @test
     */
    public function testFilenameWithPatterns()
    {
        $instance = new BackupExport();

        $instance->filename('{$DATABASE}.sql');
        $this->assertEquals('test.sql', basename($this->getProperty($instance, 'filename')));

        $instance->filename('{$DATETIME}.sql');
        $this->assertRegExp('/^[0-9]{14}\.sql$/', basename($this->getProperty($instance, 'filename')));

        $instance->filename('{$HOSTNAME}.sql');
        $this->assertEquals('localhost.sql', basename($this->getProperty($instance, 'filename')));

        $instance->filename('{$TIMESTAMP}.sql');
        $this->assertRegExp('/^[0-9]{10}\.sql$/', basename($this->getProperty($instance, 'filename')));
    }

    /**
     * Test for `filename()` method, with invalid directory
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File or directory `/tmp/backups/noExistingDir` not writable
     * @test
     */
    public function testFilenameWithInvalidDirectory()
    {
        (new BackupExport())->filename('noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with invalid extension
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid file extension
     * @test
     */
    public function testFilenameWithInvalidExtension()
    {
        (new BackupExport())->filename('backup.txt');
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        $instance = new BackupExport();

        $instance->rotate(10);
        $this->assertEquals(10, $this->getProperty($instance, 'rotate'));
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid rotate value
     * @test
     */
    public function testRotateWithInvalidValue()
    {
        (new BackupExport())->rotate(-1)->export();
    }

    /**
     * Test for `_storeAuth()` method
     * @test
     */
    public function testStoreAuth()
    {
        $instance = new BackupExport();

        $auth = $this->invokeMethod($instance, '_storeAuth');

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
    public function testExecutable()
    {
        $mysqldump = Configure::read('MysqlBackup.bin.mysqldump');
        $bzip2 = Configure::read('MysqlBackup.bin.bzip2');
        $gzip = Configure::read('MysqlBackup.bin.gzip');

        $instance = new BackupExport();

        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s | ' . $bzip2 . ' > %s',
            $this->invokeMethod($instance, '_getExecutable', ['bzip2'])
        );
        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s | ' . $gzip . ' > %s',
            $this->invokeMethod($instance, '_getExecutable', ['gzip'])
        );
        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s > %s',
            $this->invokeMethod($instance, '_getExecutable', [false])
        );
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

        $instance = new BackupExport();

        $this->invokeMethod($instance, '_getExecutable', ['bzip2']);
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

        $instance = new BackupExport();

        $this->invokeMethod($instance, '_getExecutable', ['gzip']);
    }

    /**
     * Test for `export()` method, without compression
     * @test
     */
    public function testExport()
    {
        $instance = new BackupExport();

        $filename = $instance->compression(false)->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql$/', basename($filename));

        $filename = $instance->filename('backup.sql')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql', basename($filename));
    }

    /**
     * Test for `export()` method, with `bzip2` compression
     * @test
     */
    public function testExportBzip2()
    {
        $instance = new BackupExport();

        $filename = $instance->compression('bzip2')->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.bz2$/', basename($filename));

        $filename = $instance->filename('backup.sql.bz2')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql.bz2', basename($filename));
    }

    /**
     * Test for `export()` method, with `gzip2` compression
     * @test
     */
    public function testExportGzip()
    {
        $instance = new BackupExport();

        $filename = $instance->compression('gzip')->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.gz$/', basename($filename));

        $filename = $instance->filename('backup.sql.gz')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql.gz', basename($filename));
    }

    /**
     * Test for `export()` method, with different chmod values
     * @test
     */
    public function testExportWithChmod()
    {
        $filename = (new BackupExport())->filename('backup1.sql')->export();
        $this->assertEquals('0664', substr(sprintf('%o', fileperms($filename)), -4));

        //Changes chmod
        Configure::write('MysqlBackup.chmod', 0777);

        $filename = (new BackupExport())->filename('backup2.sql')->export();
        $this->assertEquals('0777', substr(sprintf('%o', fileperms($filename)), -4));
    }
}
