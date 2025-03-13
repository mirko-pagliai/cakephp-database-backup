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
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventList;
use DatabaseBackup\Compression;
use DatabaseBackup\Driver\AbstractDriver;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use ValueError;

/**
 * BackupExportTest class.
 */
#[CoversClass(BackupExport::class)]
class BackupExportTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected BackupExport $BackupExport;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->BackupExport = new BackupExport();
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupExport::compression()
     */
    #[Test]
    #[TestWith([Compression::None])]
    #[TestWith([Compression::Gzip])]
    #[TestWith([Compression::Bzip2])]
    public function testCompression(Compression $Compression): void
    {
        $this->BackupExport->compression($Compression);
        $this->assertSame($Compression, $this->BackupExport->getCompression());
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupExport::filename()
     */
    #[Test]
    #[TestWith(['backup.sql'])]
    #[TestWith(['backup.sql.gz'])]
    #[TestWith(['backup.sql.bz2'])]
    #[TestWith([TMP . 'backups/backup.sql.bz2'])]
    public function testFilename(string $filename): void
    {
        $expectedFilename = Configure::read('DatabaseBackup.target') . basename($filename);
        $expectedCompression = Compression::fromFilename($filename);

        $this->BackupExport->filename($filename);
        $this->assertSame($expectedFilename, $this->BackupExport->getFilename());
        $this->assertSame($expectedCompression, $this->BackupExport->getCompression());

        /**
         * The compression set by the `compression()` method is however overridden by the `filename()` method.
         *
         * So the result is the same as the previous one.
         */
        $this->BackupExport
            ->compression(Compression::Gzip)
            ->filename($filename);
        $this->assertSame($expectedFilename, $this->BackupExport->getFilename());
        $this->assertSame($expectedCompression, $this->BackupExport->getCompression());
    }

    /**
     * Test for `filename()` method, with a no writable target.
     *
     * @uses \DatabaseBackup\Utility\BackupExport::filename()
     */
    #[Test]
    public function testFilenameWithNoWritableTarget(): void
    {
        $filename = '/noExistingDir/backup.sql';
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File or directory `' . dirname($filename) . '` is not writable');
        $this->BackupExport->filename($filename);
    }

    /**
     * Test for `filename()` method, with file already exist.
     *
     * @uses \DatabaseBackup\Utility\BackupExport::filename()
     */
    #[Test]
    public function testFilenameWithFileAlreadyExists(): void
    {
        $filename = $this->createBackup();
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File `' . $filename . '` already exists');
        $this->BackupExport->filename($filename);
    }

    /**
     * Test for `filename()` method, with patterns on the filename (`{$DATABASE}`, `{$DATETIME}` and so on...).
     *
     * @uses \DatabaseBackup\Utility\BackupExport::filename()
     */
    #[Test]
    #[TestWith(['test.sql', '{$DATABASE}.sql'])]
    #[TestWith(['/^\d{14}\.sql$/', '{$DATETIME}.sql'])]
    #[TestWith(['localhost.sql', '{$HOSTNAME}.sql'])]
    #[TestWith(['/^\d{10}\.sql$/', '{$TIMESTAMP}.sql'])]
    public function testFilenameWithPatterns(string $expectedFilename, string $filenameWithPattern): void
    {
        $this->BackupExport->filename($filenameWithPattern);
        $result = $this->BackupExport->getFilename();

        $assertMethod = str_starts_with($expectedFilename, '/') ? 'assertMatchesRegularExpression' : 'assertSame';
        $this->{$assertMethod}($expectedFilename, basename($result));
    }

    /**
     * Test for `filename()` method, with an invalid filename.
     *
     * @uses \DatabaseBackup\Utility\BackupExport::filename()
     */
    #[Test]
    public function testFilenameInvalidFilename(): void
    {
        $filename = TMP . 'backup.txt';
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('No valid `' . Compression::class . '` value was found for filename `' . $filename . '`');
        $this->BackupExport->filename($filename);
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupExport::rotate()
     */
    #[Test]
    public function testRotate(): void
    {
        $this->BackupExport->rotate(10);
        $this->assertSame(10, $this->BackupExport->getRotate());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     */
    #[Test]
    public function testExport(): void
    {
        $BackupExport = $this->createPartialMock(BackupExport::class, ['getFilesystem', 'getProcess']);
        $BackupExport
            ->method('getProcess')
            ->willReturn($this->createConfiguredMock(Process::class, ['isSuccessful' => true]));
        $BackupExport
            ->method('getFilesystem')
            ->willReturn($this->createStub(Filesystem::class));

        $BackupExport->getDriver()->getEventManager()->setEventList(new EventList());

        $filename = $BackupExport->export();
        $this->assertIsString($filename);
        $this->assertMatchesRegularExpression('/^backup_test_\d{14}\.sql$/', basename($filename));
        $this->assertEventFired('Backup.beforeExport', $BackupExport->getDriver()->getEventManager());
        $this->assertEventFired('Backup.afterExport', $BackupExport->getDriver()->getEventManager());

        //Exports with `compression()`
        $filename = $BackupExport
            ->compression(Compression::Gzip)
            ->export();
        $this->assertIsString($filename);
        $this->assertMatchesRegularExpression('/backup_test_\d{14}\.sql\.gz$/', $filename);

        //Exports with `filename()`
        $filename = $BackupExport
            ->filename('backup_test.sql.bz2')
            ->export();
        $this->assertIsString($filename);
        $this->assertMatchesRegularExpression('/backup_test\.sql\.bz2$/', $filename);

        //Exports with `rotate()`
        $this->createSomeBackups();
        $filename = $BackupExport
            ->rotate(1)
            ->export();
        $this->assertIsString($filename);
        $this->assertSame(1, BackupManager::index()->count());
    }

    /**
     * Test for `export()` method.
     *
     * Export is stopped by the `Backup.beforeExport` event (implemented by driver).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     */
    #[Test]
    public function testExportStoppedByBeforeExport(): void
    {
        $Driver = $this->getMockBuilder(AbstractDriver::class)
            ->setConstructorArgs([ConnectionManager::get('test')])
            ->onlyMethods(['beforeExport'])
            ->getMock();

        $Driver->expects($this->once())
            ->method('beforeExport')
            ->willReturn(false);

        $Driver->getEventManager()->on($Driver);

        $BackupExport = $this->getMockBuilder(BackupExport::class)
            ->onlyMethods(['getDriver'])
            ->getMock();
        $BackupExport->method('getDriver')
            ->willReturn($Driver);
        $this->assertFalse($BackupExport->export());
    }

    /**
     * Test for `export()` method, on failure (error for `Process`).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     */
    #[Test]
    public function testExportOnFailure(): void
    {
        $expectedError = 'mysqldump: Got error: 1044: "Access denied for user \'root\'@\'localhost\' to database \'noExisting\'" when selecting the database';
        $Process = $this->createConfiguredMock(Process::class, ['getErrorOutput' => $expectedError . PHP_EOL, 'isSuccessful' => false]);

        $BackupExport = $this->createPartialMock(BackupExport::class, ['getProcess']);
        $BackupExport->method('getProcess')
            ->willReturn($Process);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Export failed with error message: `' . $expectedError . '`');
        $BackupExport->export();
    }

    /**
     * Test for `export()` method, with a different chmod configuration value.
     *
     * @requires OS Linux
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     */
    #[Test]
    public function testExportWithDifferentChmod(): void
    {
        $filename = TMP . 'backups/backup.sql';
        $chmodValue = 0777;
        Configure::write('DatabaseBackup.chmod', $chmodValue);

        $Filesystem = $this->createPartialMock(Filesystem::class, ['chmod']);
        $Filesystem
            ->expects($this->once())
            ->method('chmod')
            ->with($filename, $chmodValue);

        $BackupExport = $this->createPartialMock(BackupExport::class, ['getFilesystem', 'getProcess']);
        $BackupExport
            ->method('getFilesystem')
            ->willReturn($Filesystem);
        $BackupExport
            ->method('getProcess')
            ->willReturn($this->createConfiguredMock(Process::class, ['isSuccessful' => true]));

        $BackupExport
            ->filename($filename)
            ->export();
    }

    /**
     * Test for `export()` method, `Process` exceeding the timeout.
     *
     * @see https://symfony.com/doc/current/components/process.html#process-timeout
     * @uses \DatabaseBackup\Utility\BackupExport::export()
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testExportProcessExceedingTimeout(): void
    {
        $ProcessTimedOutException = new ProcessTimedOutException(Process::fromShellCommandline('dir'), 1);

        $BackupExport = $this->createPartialMock(BackupExport::class, ['getProcess']);
        $BackupExport->method('getProcess')
            ->willThrowException($ProcessTimedOutException);

        $this->expectException(ProcessTimedOutException::class);
        $this->expectExceptionMessage('The process "dir" exceeded the timeout of 60 seconds');
        $BackupExport->export();
    }
}
