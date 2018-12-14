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
namespace DatabaseBackup\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\Log\Log;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use Tools\ReflectionTrait;

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
        $this->BackupExport->BackupManager = $this->getMockBuilder(BackupManager::class)
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

        //Deletes debug log
        safe_unlink(LOGS . 'debug.log');
    }

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        $this->assertNull($this->getProperty($this->BackupExport, 'compression'));

        $config = $this->getProperty($this->BackupExport, 'config');
        $this->assertEquals($config['scheme'], 'mysql');
        $this->assertEquals($config['database'], 'test');
        $this->assertEquals($config['driver'], 'Cake\Database\Driver\Mysql');

        $this->assertEquals('sql', $this->getProperty($this->BackupExport, 'defaultExtension'));
        $this->assertInstanceof(Mysql::class, $this->getProperty($this->BackupExport, 'driver'));
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
     * @expectedException InvalidArgumentException
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
        $this->assertEquals(
            $this->BackupExport->getTarget() . DS . 'backup.sql.bz2',
            $this->getProperty($this->BackupExport, 'filename')
        );
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
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp /^File `[\s\w\/:\\]+backup\.sql` already exists$/
     */
    public function testFilenameAlreadyExists()
    {
        $this->BackupExport->filename('backup.sql')->export();

        //Again, same filename
        $this->BackupExport->filename('backup.sql')->export();
    }

    /**
     * Test for `filename()` method, with a no writable directory
     * @expectedException ErrorException
     * @expectedExceptionMessageRegExp /^File or directory `[\s\w\/:\\]+` is not writable$/
     * @test
     */
    public function testFilenameNotWritableDirectory()
    {
        $this->BackupExport->filename('noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with invalid extension
     * @expectedException InvalidArgumentException
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
     * @expectedException InvalidArgumentException
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

        //Exports with `send()`
        $recipient = 'recipient@example.com';
        $filename = $this->BackupExport->filename('exportWithSend.sql')->send($recipient)->export();
        $log = file_get_contents(LOGS . 'debug.log');
        $this->assertTextContains('Called `send()` with args: `' . $filename . '`, `' . $recipient . '`', $log);
    }

    /**
     * Test for `export()` method, with a different chmod
     * @group onlyUnix
     * @test
     */
    public function testExportWithDifferendChmod()
    {
        Configure::write('DatabaseBackup.chmod', 0777);
        $filename = $this->BackupExport->filename('exportWithDifferentChmod.sql')->export();
        $this->assertFilePerms($filename, '0777');
    }
}
