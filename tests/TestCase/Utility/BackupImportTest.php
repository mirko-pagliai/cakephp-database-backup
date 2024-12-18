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
use DatabaseBackup\Driver\Sqlite;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupImport;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * BackupImportTest class
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
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupImport ??= new BackupImport();
        $this->BackupImport->getDriver()->getEventManager()->setEventList(new EventList());
    }

    /**
     * Test for `filename()` method. This tests also `$compression` property
     * @test
     * @throws \ReflectionException
     * @uses \DatabaseBackup\Utility\BackupImport::filename()
     */
    public function testFilename(): void
    {
        foreach (array_keys(DATABASE_BACKUP_EXTENSIONS) as $extension) {
            $result = createBackup('backup.' . $extension);
            $this->BackupImport->filename($result);
            $this->assertSame($result, $this->BackupImport->filename);
        }

        //With a relative path
        $result = createBackup('backup_' . time() . '.sql');
        $this->BackupImport->filename(basename($result));
        $this->assertSame($result, $this->BackupImport->filename);

        //With an invalid directory
        $this->expectExceptionMessage('File or directory `' . TMP . 'noExistingDir' . DS . 'backup.sql` is not readable');
        $this->BackupImport->filename(TMP . 'noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with an invalid file extension
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::filename()
     */
    public function testFilenameWithInvalidFileExtension(): void
    {
        $this->expectExceptionMessage('Invalid file extension');
        $this->BackupImport->filename(tempnam(TMP, 'invalidFile'));
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::timeout()
     */
    public function testTimeout(): void
    {
        $this->BackupImport->timeout(120);
        $this->assertSame(120, $this->BackupImport->timeout);
    }

    /**
     * @test
     * @throws \ReflectionException
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    public function testImport(): void
    {
        foreach (array_keys(DATABASE_BACKUP_EXTENSIONS) as $extension) {
            $expectedFilename = createBackup('backup.' . $extension);
            $result = $this->BackupImport->filename($expectedFilename)->import() ?: '';
            $this->assertStringEndsWith('backup.' . $extension, $result);
            $this->assertSame($expectedFilename, $result);
            $this->assertEventFired('Backup.beforeImport', $this->BackupImport->getDriver()->getEventManager());
            $this->assertEventFired('Backup.afterImport', $this->BackupImport->getDriver()->getEventManager());
        }

        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }

    /**
     * Test for `import()` method. Export is stopped by the `Backup.beforeImport` event (implemented by driver)
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \ReflectionException
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    public function testImportStoppedByBeforeExport(): void
    {
        $Driver = $this->createPartialMock(Sqlite::class, ['beforeImport']);
        $Driver->method('beforeImport')->willReturn(false);
        $Driver->getEventManager()->on($Driver);
        $BackupImport = $this->createPartialMock(BackupImport::class, ['getDriver']);
        $BackupImport->method('getDriver')->willReturn($Driver);
        $this->assertFalse($BackupImport->filename(createBackup())->import());
    }

    /**
     * Test for `import()` method, on failure (error for `Process`)
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \ReflectionException
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    public function testImportOnFailure(): void
    {
        $expectedError = 'ERROR 1044 (42000): Access denied for user \'root\'@\'localhost\' to database \'noExisting\'';
        $this->expectExceptionMessage('Import failed with error message: `' . $expectedError . '`');
        $Process = $this->createConfiguredMock(Process::class, ['getErrorOutput' => $expectedError . PHP_EOL, 'isSuccessful' => false]);
        $BackupImport = $this->createPartialMock(BackupImport::class, ['getProcess']);
        $BackupImport->method('getProcess')->willReturn($Process);
        $BackupImport->filename(createBackup())->import();
    }

    /**
     * Test for `import()` method, exceeding the timeout
     * @see https://symfony.com/doc/current/components/process.html#process-timeout
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \ReflectionException
     */
    public function testImportExceedingTimeout(): void
    {
        $this->expectException(ProcessTimedOutException::class);
        $this->expectExceptionMessage('The process "dir" exceeded the timeout of 60 seconds');
        $ProcessTimedOutException = new ProcessTimedOutException(Process::fromShellCommandline('dir'), 1);
        $BackupImport = $this->createPartialMock(BackupImport::class, ['getProcess']);
        $BackupImport->method('getProcess')->willThrowException($ProcessTimedOutException);
        $BackupImport->filename(createBackup())->import();
    }
}
