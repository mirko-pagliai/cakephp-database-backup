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
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventList;
use DatabaseBackup\Compression;
use DatabaseBackup\Driver\AbstractDriver;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupImport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use ValueError;

/**
 * BackupImportTest class.
 */
#[CoversClass(BackupImport::class)]
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

        $this->BackupImport = new BackupImport();
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupImport::filename()
     */
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

    /**
     * @uses \DatabaseBackup\Utility\BackupImport::filename()
     */
    #[Test]
    public function testFilenameNoReadableFile(): void
    {
        $filename = TMP . 'noExistingDir/backup.sql';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File or directory `' . $filename . '` is not readable');
        $this->BackupImport->filename($filename);
    }

    /**
     * Test for `filename()` method, with an invalid filename.
     *
     * @uses \DatabaseBackup\Utility\BackupImport::filename()
     */
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
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImport(): void
    {
        $filename = $this->createBackup(fakeBackup: true);

        $BackupImport = $this->createPartialMock(BackupImport::class, ['getProcess']);
        $BackupImport
            ->method('getProcess')
            ->willReturn($this->createConfiguredMock(Process::class, ['isSuccessful' => true]));

        $BackupImport->getDriver()->getEventManager()->setEventList(new EventList());

        $result = $BackupImport->filename($filename)
            ->import();
        $this->assertIsString($result);
        $this->assertSame($filename, $result);
        $this->assertEventFired('Backup.beforeImport', $BackupImport->getDriver()->getEventManager());
        $this->assertEventFired('Backup.afterImport', $BackupImport->getDriver()->getEventManager());
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImportOnMissingFilename(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }

    /**
     * Test for `import()` method.
     *
     * Import is stopped by the `Backup.beforeImport` event (implemented by driver).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImportStoppedByBeforeExport(): void
    {
        $Driver = $this->getMockBuilder(AbstractDriver::class)
            ->setConstructorArgs([ConnectionManager::get('test')])
            ->onlyMethods(['beforeImport'])
            ->getMock();

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
