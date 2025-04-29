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

use BadMethodCallException;
use Cake\Event\EventInterface;
use Cake\Event\EventList;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\AbstractBackupUtility;
use DatabaseBackup\Utility\BackupImport;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use ValueError;

/**
 * BackupImportTest class.
 */
#[CoversClass(BackupImport::class)]
#[UsesClass(AbstractBackupUtility::class)]
class BackupImportTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Utility\BackupImport
     */
    protected BackupImport $BackupImport;

    /**
     * @param list<non-empty-string> $methods Methods you want to mock
     * @return \DatabaseBackup\Utility\BackupImport&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getBackupImportMock(array $methods = []): BackupImport
    {
        return $this->getMockBuilder(BackupImport::class)
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        $this->BackupImport = new BackupImport();
    }

    #[Test]
    #[TestWith([TMP . 'backups/backup.sql', 'backup.sql'])]
    #[TestWith([TMP . 'backups/backup.sql.gz', 'backup.sql.gz'])]
    #[TestWith([TMP . 'backups/backup.sql.bz2', 'backup.sql.bz2'])]
    #[TestWith([TMP . 'backups/backup.sql.bz2', TMP . 'backups/backup.sql.bz2'])]
    public function testFilename(string $expectedFilename, string $filename): void
    {
        $filename = $this->createBackup(filename: $filename, fakeBackup: true);
        $this->BackupImport->filename($filename);
        $result = $this->BackupImport->getFilename();
        $this->assertSame($expectedFilename, $result);
    }

    #[Test]
    public function testFilenameNoReadableFile(): void
    {
        $filename = TMP . 'noExistingDir/backup.sql';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File or directory `' . $filename . '` is not readable');
        $this->BackupImport->filename($filename);
    }

    #[Test]
    public function testFilenameWithInvalidFilename(): void
    {
        $filename = tempnam(TMP, 'invalidFile');
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('No valid `' . Compression::class . '` value was found for filename `' . $filename . '`');
        $this->BackupImport->filename($filename);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testImport(): void
    {
        $filename = $this->createBackup(fakeBackup: true);

        $BackupImport = $this->getBackupImportMock(['getProcess']);

        $BackupImport
            ->expects($this->once())
            ->method('getProcess')
            ->willReturn($this->createConfiguredMock(Process::class, ['isSuccessful' => true]));

        $BackupImport->getExecutor()->getEventManager()->setEventList(new EventList());

        $result = $BackupImport
            ->filename($filename)
            ->import();

        $this->assertIsString($result);
        $this->assertSame($filename, $result);
        $this->assertEventFired('Backup.beforeImport', $BackupImport->getExecutor()->getEventManager());
        $this->assertEventFired('Backup.afterImport', $BackupImport->getExecutor()->getEventManager());
    }

    #[Test]
    public function testImportOnMissingFilename(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }

    /**
     * Import is stopped by the `Backup.beforeImport` event (implemented by the `Executor` class).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testImportStoppedByBeforeImport(): void
    {
        $Executor = $this->createPartialMock(AbstractExecutor::class, ['beforeImport', 'getBinary']);

        $Executor
            ->expects($this->once())
            ->method('beforeImport')
            ->willReturnCallback(fn (EventInterface $Event) => $Event->stopPropagation());

        $Executor->getEventManager()->on($Executor);

        $BackupImport = $this->getBackupImportMock(['getExecutor']);

        $BackupImport
            ->expects($this->any())
            ->method('getExecutor')
            ->willReturn($Executor);

        $result = $BackupImport
            ->filename($this->createBackup(fakeBackup: true))
            ->import();

        $this->assertFalse($result);
    }

    /**
     * Test for `import()` method, on failure (error for `Process`).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testImportOnFailure(): void
    {
        $expectedError = 'ERROR 1044 (42000): Access denied for user \'root\'@\'localhost\' to database \'noExisting\'';
        $Process = $this->createConfiguredMock(Process::class, ['getErrorOutput' => $expectedError . PHP_EOL, 'isSuccessful' => false]);

        $BackupImport = $this->getBackupImportMock(['getProcess']);

        $BackupImport
            ->expects($this->once())
            ->method('getProcess')
            ->willReturn($Process);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import failed with error message: `' . $expectedError . '`');
        $BackupImport
            ->filename($this->createBackup(fakeBackup: true))
            ->import();
    }

    /**
     * Test for `import()` method, exceeding the timeout.
     *
     * @see https://symfony.com/doc/current/components/process.html#process-timeout
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testImportExceedingTimeout(): void
    {
        $ProcessTimedOutException = new ProcessTimedOutException(Process::fromShellCommandline('dir'), 1);

        $BackupImport = $this->getBackupImportMock(['getProcess']);

        $BackupImport
            ->expects($this->once())
            ->method('getProcess')
            ->willThrowException($ProcessTimedOutException);

        $this->expectException(ProcessTimedOutException::class);
        $this->expectExceptionMessage('The process "dir" exceeded the timeout of 60 seconds');
        $BackupImport
            ->filename($this->createBackup(fakeBackup: true))
            ->import();
    }
}
