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
namespace DatabaseBackup\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use Reflection\ReflectionTrait;

/**
 * BackupExportTest class
 */
class BackupExportTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupExport
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

        //Mocks the `send()` method of `BackupManager` class, so that it writes
        //  on the debug log instead of sending a real mail
        $this->BackupExport->BackupManager = $this->getMockBuilder(get_class($this->BackupExport->BackupManager))
            ->setMethods(['send'])
            ->getMock();

        $this->BackupExport->BackupManager->method('send')
            ->will($this->returnCallback(function () {
                $args = implode(', ', array_map(function ($arg) {
                    return '`' . $arg . '`';
                }, func_get_args()));

                return Log::write('debug', 'Called `send()` with args: ' . $args);
            }));
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->BackupExport);

        //Deletes debug log
        //@codingStandardsIgnoreLine
        @unlink(LOGS . 'debug.log');

        //Deletes all backups
        foreach (glob(Configure::read(DATABASE_BACKUP . '.target') . DS . '*') as $file) {
            //@codingStandardsIgnoreLine
            @unlink($file);
        }
    }

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        $this->assertInstanceof(DATABASE_BACKUP . '\Utility\BackupManager', $this->BackupExport->BackupManager);
        $this->assertNull($this->getProperty($this->BackupExport, 'compression'));

        $config = $this->getProperty($this->BackupExport, 'config');
        $this->assertEquals($config['scheme'], 'mysql');
        $this->assertEquals($config['database'], 'test');
        $this->assertEquals($config['driver'], 'Cake\Database\Driver\Mysql');

        $this->assertInstanceof(DATABASE_BACKUP . '\Driver\Mysql', $this->getProperty($this->BackupExport, 'driver'));
        $this->assertFalse($this->getProperty($this->BackupExport, 'emailRecipient'));
        $this->assertNull($this->getProperty($this->BackupExport, 'extension'));
        $this->assertNull($this->getProperty($this->BackupExport, 'filename'));
        $this->assertEquals(0, $this->getProperty($this->BackupExport, 'rotate'));
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
    }

    /**
     * Test for `compression()` method, with an invalid type
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testCompressionWithInvalidType()
    {
        $this->BackupExport->compression('invalidType');
    }

    /**
     * Test for `filename()` method.
     *
     * This also tests for patterns and for the `$compression` property.
     * @test
     */
    public function testFilename()
    {
        $this->BackupExport->filename('backup.sql.bz2');
        $this->assertEquals('/tmp/backups/backup.sql.bz2', $this->getProperty($this->BackupExport, 'filename'));
        $this->assertEquals('bzip2', $this->getProperty($this->BackupExport, 'compression'));
        $this->assertEquals('sql.bz2', $this->getProperty($this->BackupExport, 'extension'));

        //Compression is ignored, because there's a filename
        $this->BackupExport->compression('gzip')->filename('backup.sql.bz2');
        $this->assertEquals('backup.sql.bz2', basename($this->getProperty($this->BackupExport, 'filename')));
        $this->assertEquals('bzip2', $this->getProperty($this->BackupExport, 'compression'));
        $this->assertEquals('sql.bz2', $this->getProperty($this->BackupExport, 'extension'));

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
     * Test for `filename()` method, with a no writable directory
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File or directory `/tmp/backups/noExistingDir` not writable
     * @test
     */
    public function testFilenameNotWritableDirectory()
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
        $recipient = 'recipient@example.com';

        $this->BackupExport->send();
        $this->assertFalse($this->getProperty($this->BackupExport, 'emailRecipient'));

        $this->BackupExport->send($recipient);
        $this->assertEquals($recipient, $this->getProperty($this->BackupExport, 'emailRecipient'));
    }

    /**
     * Test for `export()` method, without compression
     * @test
     */
    public function testExport()
    {
        $filename = $this->BackupExport->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql$/', basename($filename));

        //Exports with `compression()`
        $filename = $this->BackupExport->compression('bzip2')->export();
        $this->assertFileExists($filename);
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.bz2$/', basename($filename));

        //Exports with `filename()`
        $filename = $this->BackupExport->filename('backup.sql.bz2')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql.bz2', basename($filename));

        //Exports with a different chmod
        Configure::write(DATABASE_BACKUP . '.chmod', 0777);
        $filename = $this->BackupExport->filename('exportWithDifferentChmod.sql')->export();
        $this->assertEquals('0777', substr(sprintf('%o', fileperms($filename)), -4));

        //Exports with `send()`
        $recipient = 'recipient@example.com';
        $filename = $this->BackupExport->filename('exportWithSend.sql')->send($recipient)->export();
        $log = file_get_contents(LOGS . 'debug.log');
        $this->assertTextContains('Called `send()` with args: `' . $filename . '`, `' . $recipient . '`', $log);
    }
}
