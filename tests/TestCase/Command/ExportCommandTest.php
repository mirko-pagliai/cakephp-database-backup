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
use DatabaseBackup\Command\ExportCommand;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * ExportCommandTest class.
 */
#[CoversClass(ExportCommand::class)]
#[UsesClass(BackupExport::class)]
class ExportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected string $command = 'database_backup.export -v';

    #[Test]
    public function testExecute(): void
    {
        $this->exec($this->command);
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql` has been exported/');
        $this->assertErrorEmpty();
    }

    #[Test]
    public function testExecuteWithCompressionOption(): void
    {
        $this->exec($this->command . ' --compression bzip2');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_[\w_]+\.sql\.bz2` has been exported/');
        $this->assertErrorEmpty();
    }

    #[Test]
    public function testExecuteWithFilenameOption(): void
    {
        $this->exec($this->command . ' --filename backup.sql');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup.sql` has been exported/');
        $this->assertErrorEmpty();
    }

    #[Test]
    public function testExecuteWithTimeoutOption(): void
    {
        $this->exec($this->command . ' --timeout 10');
        $this->assertExitSuccess();
        $this->assertOutputContains('Timeout for shell commands: 10 seconds');
        $this->assertErrorEmpty();
    }

    #[Test]
    public function testExecuteNotWritableTarget(): void
    {
        $this->exec($this->command . ' --filename /noExistingDir/backup.sql');
        $this->assertExitError();
        $this->assertErrorContains('File or directory `/noExistingDir` is not writable');
    }

    #[Test]
    public function testExecuteOnStoppedEvent(): void
    {
        $ExportCommand = new class extends ExportCommand {
            public function getBackupExport(): BackupExport
            {
                return new class extends BackupExport {
                    public function export(): string|false
                    {
                        return false;
                    }
                };
            }
        };

        $this->expectException(StopException::class);
        $this->expectExceptionMessage('The `Backup.beforeExport` event stopped the operation');
        $ExportCommand->run(
            argv: [],
            io: new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput()),
        );
    }
}
