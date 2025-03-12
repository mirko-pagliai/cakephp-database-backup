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

use Cake\Event\EventList;
use DatabaseBackup\Compression;
use DatabaseBackup\Driver\Sqlite;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupImport;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use ValueError;

/**
 * BackupImportTest class.
 *
 * @uses \DatabaseBackup\Utility\BackupImport
 */
class BackupImportTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Utility\BackupImport
     */
    protected BackupImport $BackupImport;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->BackupImport ??= new BackupImport();
        $this->BackupImport->getDriver()->getEventManager()->setEventList(new EventList());
    }

    /**
     * Test for `filename()` method. This tests also `$compression` property.
     *
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::filename()
     */
    public function testFilename(): void
    {
        foreach (Compression::cases() as $Compression) {
            $result = $this->createBackup(filename: 'backup.' . $Compression->value, fakeBackup: true);
            $this->BackupImport->filename($result);
            $this->assertSame($result, $this->BackupImport->getFilename());
        }

        //With a relative path
        $result = $this->createBackup(filename: 'backup_' . time() . '.sql', fakeBackup: true);
        $this->BackupImport->filename(basename($result));
        $this->assertSame($result, $this->BackupImport->getFilename());

        //With an invalid directory
        $this->expectExceptionMessage('File or directory `' . TMP . 'noExistingDir' . DS . 'backup.sql` is not readable');
        $this->BackupImport->filename(TMP . 'noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with an invalid file extension.
     *
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::filename()
     */
    public function testFilenameWithInvalidFileExtension(): void
    {
        $filename = tempnam(TMP, 'invalidFile');

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('No valid `' . Compression::class . '` value was found for filename `' . $filename . '`');
        $this->BackupImport->filename($filename);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::timeout()
     */
    public function testTimeout(): void
    {
        $this->assertSame(0, $this->BackupImport->getTimeout());

        $this->BackupImport->timeout(120);
        $this->assertSame(120, $this->BackupImport->getTimeout());
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    public function testImport(): void
    {
        foreach (Compression::cases() as $Compression) {
            $expectedFilename = $this->createBackup('backup.' . $Compression->value);
            $result = $this->BackupImport->filename($expectedFilename)->import() ?: '';
            $this->assertStringEndsWith('backup.' . $Compression->value, $result);
            $this->assertSame($expectedFilename, $result);
            $this->assertEventFired('Backup.beforeImport', $this->BackupImport->getDriver()->getEventManager());
            $this->assertEventFired('Backup.afterImport', $this->BackupImport->getDriver()->getEventManager());
        }

        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }

    /**
     * Test for `import()` method. Export is stopped by the `Backup.beforeImport` event (implemented by driver).
     *
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    public function testImportStoppedByBeforeExport(): void
    {
        $Driver = $this->createPartialMock(Sqlite::class, ['beforeImport']);
        $Driver->method('beforeImport')
            ->willReturn(false);
        $Driver->getEventManager()->on($Driver);

        $BackupImport = $this->createConfiguredMock(BackupImport::class, ['getDriver' => $Driver]);

        $result = $BackupImport
            ->filename($this->createBackup(fakeBackup: true))
            ->import();
        $this->assertFalse($result);
    }

    /**
     * Test for `import()` method, on failure (error for `Process`).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImportOnFailure(): void
    {
        $expectedError = 'ERROR 1044 (42000): Access denied for user \'root\'@\'localhost\' to database \'noExisting\'';
        $Process = $this->createConfiguredMock(Process::class, ['getErrorOutput' => $expectedError . PHP_EOL, 'isSuccessful' => false]);

        $BackupImport = $this->createPartialMock(BackupImport::class, ['getProcess']);
        $BackupImport->method('getProcess')
            ->willReturn($Process);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import failed with error message: `' . $expectedError . '`');
        $BackupImport->filename($this->createBackup(fakeBackup: true))->import();
    }

    /**
     * Test for `import()` method, exceeding the timeout.
     *
     * @see https://symfony.com/doc/current/components/process.html#process-timeout
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testImportExceedingTimeout(): void
    {
        $ProcessTimedOutException = new ProcessTimedOutException(Process::fromShellCommandline('dir'), 1);

        $BackupImport = $this->createPartialMock(BackupImport::class, ['getProcess']);
        $BackupImport->method('getProcess')
            ->willThrowException($ProcessTimedOutException);

        $this->expectException(ProcessTimedOutException::class);
        $this->expectExceptionMessage('The process "dir" exceeded the timeout of 60 seconds');
        $BackupImport
            ->filename($this->createBackup(fakeBackup: true))
            ->import();
    }
}
