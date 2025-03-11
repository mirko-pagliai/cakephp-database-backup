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

namespace DatabaseBackup\Test\TestCase\Command;

use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Console\TestSuite\StubConsoleOutput;
use DatabaseBackup\Command\ImportCommand;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupImport;

/**
 * ImportCommandTest class
 *
 * @uses \DatabaseBackup\Command\ImportCommand
 */
class ImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected string $command = 'database_backup.import -v';

    /**
     * @test
     * @uses \DatabaseBackup\Command\ImportCommand::execute()
     */
    public function testExecute(): void
    {
        $backup = $this->createBackup();
        $this->exec($this->command . ' ' . $backup);
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('<success>Backup `' . $backup . '` has been imported</success>');
        $this->assertErrorEmpty();

        //With a no existing file
        $this->exec($this->command . ' /noExistingDir/backup.sql');
        $this->assertExitError();
    }

    /**
     * Test for `execute()` method on stopped event.
     *
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Command\ImportCommand::execute()
     */
    public function testExecuteOnStoppedEvent(): void
    {
        $BackupImport = $this->createConfiguredMock(BackupImport::class, ['import' => false]);
        $ImportCommand = $this->createPartialMock(ImportCommand::class, ['getBackupImport']);
        $ImportCommand->method('getBackupImport')
            ->willReturn($BackupImport);

        $this->expectException(StopException::class);
        $this->expectExceptionMessage('The `Backup.beforeImport` event stopped the operation');
        $ImportCommand->run(['--filename' => $this->createBackup(fakeBackup: true)], new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput()));
    }

    /**
     * Test for `execute()` method, with `timeout` option.
     *
     * @test
     * @uses \DatabaseBackup\Command\ImportCommand::execute()
     */
    public function testExecuteTimeoutOption(): void
    {
        $this->exec($this->command . ' --timeout 10 ' . $this->createBackup());
        $this->assertExitSuccess();
        $this->assertOutputContains('Timeout for shell commands: 10 seconds');
        $this->assertErrorEmpty();
    }
}
