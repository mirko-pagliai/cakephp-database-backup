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
use Cake\Event\EventList;
use Cake\TestSuite\EmailTrait;
use DatabaseBackup\Driver\Sqlite;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * BackupExportTest class
 *
 * @uses \DatabaseBackup\Utility\BackupExport
 */
class BackupExportTest extends TestCase
{
    use EmailTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected BackupExport $BackupExport;

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->BackupExport ??= new BackupExport();
        $this->BackupExport->getDriver()->getEventManager()->setEventList(new EventList());
    }

    /**
     * Test for `compression()` method. This also tests for `$extension` property
     * @test
     * @uses \DatabaseBackup\Utility\BackupExport::compression()
     */
    public function testCompression(): void
    {
        $this->BackupExport->compression('bzip2');
        $this->assertSame('bzip2', $this->BackupExport->compression);
        $this->assertSame('sql.bz2', $this->BackupExport->extension);

        //With an invalid type
        $this->expectExceptionMessage('Invalid compression type');
        $this->BackupExport->compression('invalidType');
    }

    /**
     * Test for `filename()` method. This also tests for patterns and for the `$compression` property
     * @test
     * @uses \DatabaseBackup\Utility\BackupExport::filename()
     */
    public function testFilename(): void
    {
        $this->BackupExport->filename('backup.sql.bz2');
        $this->assertSame(Configure::read('DatabaseBackup.target') . 'backup.sql.bz2', $this->BackupExport->filename);
        $this->assertSame('bzip2', $this->BackupExport->compression);
        $this->assertSame('sql.bz2', $this->BackupExport->extension);

        //Compression is ignored, because there's a filename
        $this->BackupExport->compression('gzip')->filename('backup.sql.bz2');
        $this->assertSame('backup.sql.bz2', basename($this->BackupExport->filename));
        $this->assertSame('bzip2', $this->BackupExport->compression);
        $this->assertSame('sql.bz2', $this->BackupExport->extension);

        //Filename with `{$DATABASE}` pattern
        $this->BackupExport->filename('{$DATABASE}.sql');
        $this->assertSame('test.sql', basename($this->BackupExport->filename));

        //Filename with `{$DATETIME}` pattern
        $this->BackupExport->filename('{$DATETIME}.sql');
        $this->assertMatchesRegularExpression('/^\d{14}\.sql$/', basename($this->BackupExport->filename));

        //Filename with `{$HOSTNAME}` pattern
        $this->BackupExport->filename('{$HOSTNAME}.sql');
        $this->assertSame('localhost.sql', basename($this->BackupExport->filename));

        //Filename with `{$TIMESTAMP}` pattern
        $this->BackupExport->filename('{$TIMESTAMP}.sql');
        $this->assertMatchesRegularExpression('/^\d{10}\.sql$/', basename($this->BackupExport->filename));

        //With invalid extension
        $this->expectExceptionMessage('Invalid `txt` file extension');
        $this->BackupExport->filename('backup.txt');
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupExport::rotate()
     */
    public function testRotate(): void
    {
        $this->BackupExport->rotate(10);
        $this->assertSame(10, $this->BackupExport->rotate);

        $this->expectExceptionMessage('Invalid rotate value');
        $this->BackupExport->rotate(-1)->export();
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupExport::send()
     */
    public function testSend(): void
    {
        $this->BackupExport->send();
        $this->assertNull($this->BackupExport->emailRecipient);

        $recipient = 'recipient@example.com';
        $this->BackupExport->send($recipient);
        $this->assertSame($recipient, $this->BackupExport->emailRecipient);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupExport::timeout()
     */
    public function testTimeout(): void
    {
        $this->BackupExport->timeout(120);
        $this->assertSame(120, $this->BackupExport->timeout);
    }

    /**
     * @test
     * @throws \ReflectionException
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     */
    public function testExport(): void
    {
        $file = $this->BackupExport->export() ?: '';
        $this->assertFileExists($file);
        $this->assertMatchesRegularExpression('/^backup_test_\d{14}\.sql$/', basename($file));
        $this->assertEventFired('Backup.beforeExport', $this->BackupExport->getDriver()->getEventManager());
        $this->assertEventFired('Backup.afterExport', $this->BackupExport->getDriver()->getEventManager());

        //Exports with `compression()`
        $file = $this->BackupExport->compression('bzip2')->export() ?: '';
        $this->assertFileExists($file);
        $this->assertMatchesRegularExpression('/^backup_test_\d{14}\.sql\.bz2$/', basename($file));

        //Exports with `filename()`
        $file = $this->BackupExport->filename('backup.sql.bz2')->export() ?: '';
        $this->assertFileExists($file);
        $this->assertSame('backup.sql.bz2', basename($file));

        //Exports with `send()`
        $recipient = 'recipient@example.com';
        $file = $this->BackupExport->filename('exportWithSend.sql')->send($recipient)->export() ?: '';
        $this->assertMailSentFrom(Configure::readOrFail('DatabaseBackup.mailSender'));
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
     * @throws \ReflectionException
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     */
    public function testExportWithDifferentChmod(): void
    {
        $file = $this->BackupExport->filename('exportWithNormalChmod.sql')->export() ?: '';
        $this->assertSame('0664', substr(sprintf('%o', fileperms($file)), -4));

        Configure::write('DatabaseBackup.chmod', 0777);
        $file = $this->BackupExport->filename('exportWithDifferentChmod.sql')->export() ?: '';
        $this->assertSame('0777', substr(sprintf('%o', fileperms($file)), -4));
    }

    /**
     * Test for `export()` method. Export is stopped by the `Backup.beforeExport` event (implemented by driver)
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \ReflectionException
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     */
    public function testExportStoppedByBeforeExport(): void
    {
        $Driver = $this->createPartialMock(Sqlite::class, ['beforeExport']);
        $Driver->method('beforeExport')->willReturn(false);
        $Driver->getEventManager()->on($Driver);
        $BackupExport = $this->createPartialMock(BackupExport::class, ['getDriver']);
        $BackupExport->method('getDriver')->willReturn($Driver);
        $this->assertFalse($BackupExport->export());
    }

    /**
     * Test for `export()` method, on failure (error for `Process`)
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \ReflectionException
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     */
    public function testExportOnFailure(): void
    {
        $expectedError = 'mysqldump: Got error: 1044: "Access denied for user \'root\'@\'localhost\' to database \'noExisting\'" when selecting the database';
        $this->expectExceptionMessage('Export failed with error message: `' . $expectedError . '`');
        $Process = $this->createConfiguredMock(Process::class, ['getErrorOutput' => $expectedError . PHP_EOL, 'isSuccessful' => false]);
        $BackupExport = $this->createPartialMock(BackupExport::class, ['getProcess']);
        $BackupExport->method('getProcess')->willReturn($Process);
        $BackupExport->export();
    }

    /**
     * Test for `export()` method, exceeding the timeout
     * @see https://symfony.com/doc/current/components/process.html#process-timeout
     * @test
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \ReflectionException
     */
    public function testExportExceedingTimeout(): void
    {
        $this->expectException(ProcessTimedOutException::class);
        $this->expectExceptionMessage('The process "dir" exceeded the timeout of 60 seconds');
        $ProcessTimedOutException = new ProcessTimedOutException(Process::fromShellCommandline('dir'), 1);
        $BackupExport = $this->createPartialMock(BackupExport::class, ['getProcess']);
        $BackupExport->method('getProcess')->willThrowException($ProcessTimedOutException);
        $BackupExport->export();
    }
}
