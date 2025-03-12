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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * ExportCommandTest class.
 */
#[CoversClass(ExportCommand::class)]
class ExportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected string $command = 'database_backup.export -v';

    /**
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecute(): void
    {
        $this->exec($this->command);
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `--compression` option.
     *
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecuteCompressionOption(): void
    {
        $this->exec($this->command . ' --compression bzip2');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql\.bz2` has been exported/');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `--filename` option.
     *
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecuteFilenameOption(): void
    {
        $this->exec($this->command . ' --filename backup.sql');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup.sql` has been exported/');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `--rotate` option.
     *
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecuteRotateOption(): void
    {
        $files = $this->createSomeBackups();
        $this->exec($this->command . ' --rotate 3');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertOutputContains('Backup `' . basename($files[0]) . '` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 1</success>');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `--rotate` option, but no files to rotate.
     *
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecuteRotateOptionWithNoFileToDelete(): void
    {
        $this->exec($this->command . ' --rotate 3');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertOutputContains('No backup has been deleted');
        $this->assertErrorEmpty();
    }

    /**
     * Test for `execute()` method, with `--send` option.
     *
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecuteSendOption(): void
    {
        Configure::write('DatabaseBackup.mailSender', 'sender@example.com');

        $this->exec($this->command . ' --send mymail@example.com');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` was sent via mail/');
        $this->assertErrorEmpty();
    }

    /**
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testExecuteSendOptionIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->exec($this->command . ' --send mymail@example.com');
        });
    }

    /**
     * Test for `execute()` method, with `--timeout` option.
     *
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecuteTimeoutOption(): void
    {
        $this->exec($this->command . ' --timeout 10');
        $this->assertExitSuccess();
        $this->assertOutputContains('Timeout for shell commands: 10 seconds');
        $this->assertErrorEmpty();
    }

    /**
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecuteNotWritableTarget(): void
    {
        $this->exec($this->command . ' --filename /noExistingDir/backup.sql');
        $this->assertExitError();
        $this->assertErrorContains('File or directory `/noExistingDir` is not writable');
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    public function testExecuteOnStoppedEvent(): void
    {
        $ExportCommand = $this->createPartialMock(ExportCommand::class, ['getBackupExport']);
        $ExportCommand
            ->method('getBackupExport')
            ->willReturn($this->createConfiguredMock(BackupExport::class, ['export' => false]));

        $this->expectException(StopException::class);
        $this->expectExceptionMessage('The `Backup.beforeExport` event stopped the operation');
        $ExportCommand->run(
            [],
            new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput())
        );
    }
}
