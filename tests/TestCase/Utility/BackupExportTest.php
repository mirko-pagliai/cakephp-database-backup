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
     * @var \MysqlBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->BackupExport = new BackupExport;
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->BackupExport);

        //Deletes all backups
        foreach (glob(Configure::read(MYSQL_BACKUP . '.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        $this->assertNull($this->getProperty($this->BackupExport, 'compression'));

        $connection = $this->getProperty($this->BackupExport, 'connection');
        $this->assertEquals($connection['scheme'], 'mysql');
        $this->assertEquals($connection['database'], 'test');
        $this->assertEquals($connection['driver'], 'Cake\Database\Driver\Mysql');

        $this->assertFalse($this->getProperty($this->BackupExport, 'deleteAfterSending'));
        $this->assertNull($this->getProperty($this->BackupExport, 'executable'));
        $this->assertEquals('sql', $this->getProperty($this->BackupExport, 'extension'));
        $this->assertNull($this->getProperty($this->BackupExport, 'filename'));
        $this->assertNull($this->getProperty($this->BackupExport, 'rotate'));
        $this->assertFalse($this->getProperty($this->BackupExport, 'send'));
    }

    /**
     * Test for `compression()` method. This also tests for `$extension`
     *  property
     * @test
     */
    public function testCompression()
    {
        $this->BackupExport->compression('bzip2');
        $this->assertEquals('bzip2', $this->getProperty($this->BackupExport, 'compression'));
        $this->assertEquals('sql.bz2', $this->getProperty($this->BackupExport, 'extension'));

        $this->BackupExport->compression('gzip');
        $this->assertEquals('gzip', $this->getProperty($this->BackupExport, 'compression'));
        $this->assertEquals('sql.gz', $this->getProperty($this->BackupExport, 'extension'));

        $this->BackupExport->compression(false);
        $this->assertEquals(false, $this->getProperty($this->BackupExport, 'compression'));
        $this->assertEquals('sql', $this->getProperty($this->BackupExport, 'extension'));
    }

    /**
     * Test for `compression()` method, with an invalid stringvalue
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidString()
    {
        $this->BackupExport->compression('invalidValue');
    }

    /**
     * Test for `compression()` method, with an invalid boolean value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidBool()
    {
        $this->BackupExport->compression(true);
    }

    /**
     * Test for `filename()` method.
     *
     * This also tests for patterns and for the `$compression` property.
     * @test
     */
    public function testFilename()
    {
        //`sql` filename
        $this->BackupExport->filename('backup.sql');
        $this->assertEquals('/tmp/backups/backup.sql', $this->getProperty($this->BackupExport, 'filename'));
        $this->assertFalse($this->getProperty($this->BackupExport, 'compression'));

        //`sql.bz2` filename
        $this->BackupExport->filename('backup.sql.bz2');
        $this->assertEquals('/tmp/backups/backup.sql.bz2', $this->getProperty($this->BackupExport, 'filename'));
        $this->assertEquals('bzip2', $this->getProperty($this->BackupExport, 'compression'));

        //`sql.gz` filename
        $this->BackupExport->filename('backup.sql.gz');
        $this->assertEquals('/tmp/backups/backup.sql.gz', $this->getProperty($this->BackupExport, 'filename'));
        $this->assertEquals('gzip', $this->getProperty($this->BackupExport, 'compression'));

        //Filename with absolute path
        $this->BackupExport->filename('/tmp/backups/other.sql');
        $this->assertEquals('/tmp/backups/other.sql', $this->getProperty($this->BackupExport, 'filename'));
        $this->assertFalse($this->getProperty($this->BackupExport, 'compression'));

        //Compression is ignored, because a filename has been given
        $this->BackupExport->compression('gzip')->filename('backup.sql.bz2');
        $this->assertEquals('backup.sql.bz2', basename($this->getProperty($this->BackupExport, 'filename')));
        $this->assertEquals('bzip2', $this->getProperty($this->BackupExport, 'compression'));

        //Compression is ignored, because a filename has been given
        $this->BackupExport->compression('bzip2')->filename('backup.sql');
        $this->assertEquals('backup.sql', basename($this->getProperty($this->BackupExport, 'filename')));
        $this->assertFalse($this->getProperty($this->BackupExport, 'compression'));

        //Filename with `{$DATABASE}` pattern
        $this->BackupExport->filename('{$DATABASE}.sql');
        $this->assertEquals('test.sql', basename($this->getProperty($this->BackupExport, 'filename')));

        //Filename with `{$DATETIME}` pattern
        $this->BackupExport->filename('{$DATETIME}.sql');
        $this->assertRegExp('/^[0-9]{14}\.sql$/', basename($this->getProperty($this->BackupExport, 'filename')));

        //Filename with `{$HOSTNAME}` pattern
        $this->BackupExport->filename('{$HOSTNAME}.sql');
        $this->assertEquals('localhost.sql', basename($this->getProperty($this->BackupExport, 'filename')));

        //Filename with `{$TIMESTAMP}` pattern
        $this->BackupExport->filename('{$TIMESTAMP}.sql');
        $this->assertRegExp('/^[0-9]{10}\.sql$/', basename($this->getProperty($this->BackupExport, 'filename')));
    }

    /**
     * Test for `filename()` method, with a file that already exists
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File `/tmp/backups/backup.sql` already exists
     */
    public function testFilenameAlreadyExists()
    {
        $this->BackupExport->filename('backup.sql')->export();

        //Again, same filename
        $this->BackupExport->filename('backup.sql')->export();
    }

    /**
     * Test for `filename()` method, with invalid directory
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File or directory `/tmp/backups/noExistingDir` not writable
     * @test
     */
    public function testFilenameWithInvalidDirectory()
    {
        $this->BackupExport->filename('noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with invalid extension
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid file extension
     * @test
     */
    public function testFilenameWithInvalidExtension()
    {
        $this->BackupExport->filename('backup.txt');
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        $this->BackupExport->rotate(10);
        $this->assertEquals(10, $this->getProperty($this->BackupExport, 'rotate'));
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid rotate value
     * @test
     */
    public function testRotateWithInvalidValue()
    {
        $this->BackupExport->rotate(-1)->export();
    }

    /**
     * Test for `send()` method
     * @test
     */
    public function testSend()
    {
        $this->BackupExport->send();
        $this->assertTrue($this->getProperty($this->BackupExport, 'send'));
        $this->assertFalse($this->getProperty($this->BackupExport, 'deleteAfterSending'));

        $this->BackupExport->send(true);
        $this->assertTrue($this->getProperty($this->BackupExport, 'send'));
        $this->assertFalse($this->getProperty($this->BackupExport, 'deleteAfterSending'));

        $this->BackupExport->send(true, false);
        $this->assertTrue($this->getProperty($this->BackupExport, 'send'));
        $this->assertFalse($this->getProperty($this->BackupExport, 'deleteAfterSending'));

        $this->BackupExport->send(true, true);
        $this->assertTrue($this->getProperty($this->BackupExport, 'send'));
        $this->assertTrue($this->getProperty($this->BackupExport, 'deleteAfterSending'));

        $this->BackupExport->send(false);
        $this->assertFalse($this->getProperty($this->BackupExport, 'send'));
        $this->assertFalse($this->getProperty($this->BackupExport, 'deleteAfterSending'));
    }

    /**
     * Test for `_storeAuth()` method
     * @test
     */
    public function testStoreAuth()
    {
        $auth = $this->invokeMethod($this->BackupExport, '_storeAuth');

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
        $mysqldump = Configure::read(MYSQL_BACKUP . '.bin.mysqldump');
        $bzip2 = Configure::read(MYSQL_BACKUP . '.bin.bzip2');
        $gzip = Configure::read(MYSQL_BACKUP . '.bin.gzip');

        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s | ' . $bzip2 . ' > %s',
            $this->invokeMethod($this->BackupExport, '_getExecutable', ['bzip2'])
        );
        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s | ' . $gzip . ' > %s',
            $this->invokeMethod($this->BackupExport, '_getExecutable', ['gzip'])
        );
        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s > %s',
            $this->invokeMethod($this->BackupExport, '_getExecutable', [false])
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
        Configure::write(MYSQL_BACKUP . '.bin.bzip2', false);

        $this->invokeMethod($this->BackupExport, '_getExecutable', ['bzip2']);
    }

    /**
     * Test for `_getExecutable()` method, with the `gzip` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `gzip` executable not available
     * @test
     */
    public function testExecutableWithGzipNotAvailable()
    {
        Configure::write(MYSQL_BACKUP . '.bin.gzip', false);

        $this->invokeMethod($this->BackupExport, '_getExecutable', ['gzip']);
    }

    /**
     * Test for `export()` method, without compression
     * @test
     */
    public function testExport()
    {
        //Exports with no compression
        $filename = $this->BackupExport->compression(false)->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql$/', basename($filename));

        //Exports with `bzip2` compression
        $filename = $this->BackupExport->compression('bzip2')->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.bz2$/', basename($filename));

        //Exports with `gzip` compression
        $filename = $this->BackupExport->compression('gzip')->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.gz$/', basename($filename));

        //Exports with `sql` filename
        $filename = $this->BackupExport->filename('backup.sql')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql', basename($filename));

        //Exports with `sql.bz2` filename
        $filename = $this->BackupExport->filename('backup.sql.bz2')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql.bz2', basename($filename));

        //Exports with `sql.gz` filename
        $filename = $this->BackupExport->filename('backup.sql.gz')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql.gz', basename($filename));

        //Changes chmod
        Configure::write(MYSQL_BACKUP . '.chmod', 0777);

        //Exports with a different chmod
        $filename = $this->BackupExport->filename('backup2.sql')->export();
        $this->assertEquals('0777', substr(sprintf('%o', fileperms($filename)), -4));
    }
}
