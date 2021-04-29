<?php
declare(strict_types=1);

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
use Cake\Database\Driver\Mysql as CakeMySql;
use Cake\Log\Log;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use InvalidArgumentException;
use Tools\Filesystem;

/**
 * BackupExportTest class
 */
class BackupExportTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        if (!$this->BackupExport) {
            $this->BackupExport = new BackupExport();

            //Mocks the `send()` method of `BackupManager` class, so that it writes
            //  on the debug log instead of sending a real mail
            $this->BackupExport->BackupManager = @$this->getMockBuilder(BackupManager::class)
                ->setMethods(['send'])
                ->getMock();

            $this->BackupExport->BackupManager->method('send')
                ->will($this->returnCallback(function () {
                    $args = implode(', ', array_map(function ($arg) {
                        return '`' . $arg . '`';
                    }, func_get_args()));

                    Log::write('debug', 'Called `send()` with args: ' . $args);

                    return func_get_args();
                }));
        }

        parent::setUp();
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
        $this->assertEquals($config['driver'], CakeMySql::class);

        $this->assertEquals('sql', $this->getProperty($this->BackupExport, 'defaultExtension'));
        $this->assertInstanceof(Mysql::class, $this->getProperty($this->BackupExport, 'driver'));
        $this->assertNull($this->getProperty($this->BackupExport, 'emailRecipient'));
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

        //With an invalid type
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid compression type');
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
            Filesystem::instance()->concatenate(Configure::read('DatabaseBackup.target'), 'backup.sql.bz2'),
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
        $this->assertMatchesRegularExpression('/^\d{14}\.sql$/', basename($this->getProperty($this->BackupExport, 'filename')));

        //Filename with `{$HOSTNAME}` pattern
        $this->BackupExport->filename('{$HOSTNAME}.sql');
        $this->assertEquals('localhost.sql', basename($this->getProperty($this->BackupExport, 'filename')));

        //Filename with `{$TIMESTAMP}` pattern
        $this->BackupExport->filename('{$TIMESTAMP}.sql');
        $this->assertMatchesRegularExpression('/^\d{10}\.sql$/', basename($this->getProperty($this->BackupExport, 'filename')));

        //With invalid extension
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file extension');
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rotate value');
        $this->BackupExport->rotate(-1)->export();
    }

    /**
     * Test for `send()` method
     * @test
     */
    public function testSend()
    {
        $this->BackupExport->send();
        $this->assertNull($this->getProperty($this->BackupExport, 'emailRecipient'));

        $recipient = 'recipient@example.com';
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
        $this->assertMatchesRegularExpression('/^backup_test_\d{14}\.sql$/', basename($filename));

        //Exports with `compression()`
        $filename = $this->BackupExport->compression('bzip2')->export();
        $this->assertFileExists($filename);
        $this->assertMatchesRegularExpression('/^backup_test_\d{14}\.sql\.bz2$/', basename($filename));

        //Exports with `filename()`
        $filename = $this->BackupExport->filename('backup.sql.bz2')->export();
        $this->assertFileExists($filename);
        $this->assertEquals('backup.sql.bz2', basename($filename));

        //Exports with `send()`
        $recipient = 'recipient@example.com';
        $filename = $this->BackupExport->filename('exportWithSend.sql')->send($recipient)->export();
        $log = file_get_contents(LOGS . 'debug.log') ?: '';
        $this->assertTextContains('Called `send()` with args: `' . $filename . '`, `' . $recipient . '`', $log);

        //With a file that already exists
        $this->expectExceptionMessage('File `' . $this->BackupExport->getAbsolutePath('backup.sql.bz2') . '` already exists');
        $this->BackupExport->filename('backup.sql.bz2')->export();
    }

    /**
     * Test for `export()` method, with a different chmod
     * @requires OS Linux
     * @test
     */
    public function testExportWithDifferendChmod()
    {
        Configure::write('DatabaseBackup.chmod', 0777);
        $filename = $this->BackupExport->filename('exportWithDifferentChmod.sql')->export();
        $this->assertFileIsWritable($filename);
    }
}
