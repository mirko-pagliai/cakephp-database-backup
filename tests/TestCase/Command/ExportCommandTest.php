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
use Cake\Core\Configure;
use DatabaseBackup\Command\ExportCommand;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * ExportCommandTest class
 *
 * @uses \DatabaseBackup\Command\ExportCommand
 */
class ExportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected string $command = 'database_backup.export -v';

    /**
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecute(): void
    {
        $this->exec($this->command);
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertErrorEmpty();

        //With an invalid option value
        $this->exec($this->command . ' --filename /noExistingDir/backup.sql');
        $this->assertExitError();
    }

    /**
     * Test for `execute()` method on stopped event.
     *
     * @test
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteOnStoppedEvent(): void
    {
        $this->expectException(StopException::class);
        $this->expectExceptionMessage('The `Backup.beforeExport` event stopped the operation');
        $ExportCommand = $this->createPartialMock(ExportCommand::class, ['getBackupExport']);
        $ExportCommand->method('getBackupExport')
            ->willReturn($this->createConfiguredMock(BackupExport::class, ['export' => false]));
        $ExportCommand->run([], new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput()));
    }

    /**
     * Test for `execute()` method, with `compression` option.
     *
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteCompressionOption(): void
    {
        $this->exec($this->command . ' --compression bzip2');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql\.bz2` has been exported/');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `filename` option.
     *
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteFilenameOption(): void
    {
        $this->exec($this->command . ' --filename backup.sql');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup.sql` has been exported/');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `rotate` option.
     *
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteRotateOption(): void
    {
        $files = createSomeBackups();
        $this->exec($this->command . ' --rotate 3 -v');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertOutputContains('Backup `' . basename($files[0]) . '` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 1</success>');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `rotate` option but no files to delete.
     *
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteRotateOption2(): void
    {
        $this->exec($this->command . ' --rotate 3 -v');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertOutputContains('No backup has been deleted');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `timeout` option.
     *
     * @test
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    public function testExecuteTimeoutOption(): void
    {
        $this->exec($this->command . ' --timeout 10');
        $this->assertExitSuccess();
        $this->assertOutputContains('Timeout for shell commands: 10 seconds');
        $this->assertErrorEmpty();
    }
}
