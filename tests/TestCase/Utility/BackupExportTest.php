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
use Cake\TestSuite\EmailTrait;
use DatabaseBackup\Driver\Driver;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use Tools\Filesystem;

/**
 * BackupExportTest class
 */
class BackupExportTest extends TestCase
{
    use EmailTrait;

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct(): void
    {
        $this->assertNull($this->getProperty($this->BackupExport, 'compression'));

        $this->assertInstanceof(Driver::class, $this->BackupExport->Driver);
        $this->assertEquals('sql', $this->getProperty($this->BackupExport, 'defaultExtension'));
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
    public function testCompression(): void
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
    public function testFilename(): void
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
        $this->expectExceptionMessage('Invalid `txt` file extension');
        $this->BackupExport->filename('backup.txt');
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate(): void
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
    public function testSend(): void
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
    public function testExport(): void
    {
        $file = $this->BackupExport->export();
        $this->assertFileExists($file);
        $this->assertMatchesRegularExpression('/^backup_test_\d{14}\.sql$/', basename($file));

        //Exports with `compression()`
        $file = $this->BackupExport->compression('bzip2')->export();
        $this->assertFileExists($file);
        $this->assertMatchesRegularExpression('/^backup_test_\d{14}\.sql\.bz2$/', basename($file));

        //Exports with `filename()`
        $file = $this->BackupExport->filename('backup.sql.bz2')->export();
        $this->assertFileExists($file);
        $this->assertEquals('backup.sql.bz2', basename($file));

        //Exports with `send()`
        $recipient = 'recipient@example.com';
        $file = $this->BackupExport->filename('exportWithSend.sql')->send($recipient)->export();
        $this->assertMailSentFrom(Configure::read('DatabaseBackup.mailSender'));
        $this->assertMailSentTo($recipient);
        $this->assertMailSentWith('Database backup ' . basename($file) . ' from localhost', 'subject');
        $this->assertMailContainsAttachment(basename($file), compact('file') + ['mimetype' => mime_content_type($file)]);

        //With a file that already exists
        $this->expectExceptionMessage('File `' . $this->BackupExport->getAbsolutePath('backup.sql.bz2') . '` already exists');
        $this->BackupExport->filename('backup.sql.bz2')->export();
    }

    /**
     * Test for `export()` method, with a different chmod
     * @requires OS Linux
     * @test
     */
    public function testExportWithDifferendChmod(): void
    {
        $file = $this->BackupExport->filename('exportWithNormalChmod.sql')->export();
        $this->assertEquals('0664', substr(sprintf('%o', fileperms($file)), -4));

        Configure::write('DatabaseBackup.chmod', 0777);
        $file = $this->BackupExport->filename('exportWithDifferentChmod.sql')->export();
        $this->assertEquals('0777', substr(sprintf('%o', fileperms($file)), -4));
    }
}
