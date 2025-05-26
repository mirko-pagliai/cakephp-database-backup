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

use App\Database\FakeConnection;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Command\ExportCommand;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;

/**
 * ExportCommandTest.
 */
#[CoversClass(ExportCommand::class)]
class ExportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    #[Test]
    public function testBuildOptionParser(): void
    {
        $this->exec('database_backup.export -h');
        $this->assertExitSuccess();

        $this->assertOutputContains('Exports a database backup');
        $this->assertOutputContains('<info>Usage:</info>
cake database_backup.export [options]');

        $this->assertOutputContains('--compression, -c  Compression type. By default, no compression will be
                   used <comment>(choices: gzip|bzip2)</comment>');
        $this->assertOutputContains('--connection       Name of the alternative connection to use, for
                   example if you are not using the default connection
                   <comment>(default: default)</comment>');
        $this->assertOutputContains('--filename, -f     Filename. It can be an absolute path and may contain
                   patterns. The compression type will be automatically
                   set');
        $this->assertOutputContains('--timeout, -t      Timeout for shell commands');
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecute(): void
    {
        $BackupExport = Mockery::mock('overload:' . BackupExport::class);
        $BackupExport->shouldReceive('__construct')->with('default')->once();
        $BackupExport->shouldNotReceive('timeout');
        $BackupExport->shouldNotReceive('filename');
        $BackupExport->shouldNotReceive('compression');
        $BackupExport->shouldReceive('export')->once()->andReturn(ROOT . 'backups' . DS . 'my_backup.sql');

        $this->exec('database_backup.export');

        $this->assertExitSuccess();
        $this->assertOutputContains('Backup `backups/my_backup.sql` has been exported');
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteWithSomeOptions(): void
    {
        $customFilename = 'custom_filename.sql';

        $BackupExport = Mockery::mock('overload:' . BackupExport::class);
        $BackupExport->shouldReceive('__construct')->with('custom_connection')->once();
        $BackupExport->shouldReceive('timeout')->with(120)->once();
        $BackupExport->shouldReceive('filename')->with($customFilename)->once();
        //Note that in this case the `--compression` option was passed, but is ignored
        $BackupExport->shouldNotReceive('compression');
        $BackupExport->shouldReceive('export')->once()->andReturn($customFilename);

        $this->exec('database_backup.export --connection custom_connection --timeout 120 --compression gzip --filename ' . $customFilename);

        $this->assertExitSuccess();
        $this->assertOutputContains('Backup `' . $customFilename . '` has been exported');
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteWithCompressionOption(): void
    {
        $BackupExport = Mockery::mock('overload:' . BackupExport::class);
        $BackupExport->shouldReceive('__construct')->with('default')->once();
        $BackupExport->shouldNotReceive('timeout');
        $BackupExport->shouldNotReceive('filename');
        $BackupExport->shouldReceive('compression')->with('gzip')->once();
        $BackupExport->shouldReceive('export')->once()->andReturn('my_backup.sql.gz');

        $this->exec('database_backup.export --compression gzip');

        $this->assertExitSuccess();
        $this->assertOutputContains('Backup `my_backup.sql.gz` has been exported');
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteOnStoppedEvent(): void
    {
        $BackupExport = Mockery::mock('overload:' . BackupExport::class);
        $BackupExport->shouldReceive('export')->once()->andReturnFalse();

        $this->exec('database_backup.export');

        $this->assertExitError();
        $this->assertErrorContains('<error>The `Backup.beforeExport` event stopped the operation</error>');
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteWithProcessOnFailure(): void
    {
        ConnectionManager::setConfig('test', new FakeConnection());

        $Process = Mockery::mock('overload:' . Process::class)->shouldIgnoreMissing();
        $Process->shouldReceive('fromShellCommandline')->andReturnSelf();
        $Process->shouldReceive('isSuccessful')->andReturnFalse();
        $Process->shouldReceive('getCommandLine')->andReturn('failureCommand');

        $this->exec('database_backup.export --connection test');
        $this->assertExitError();
        $this->assertErrorContains('The command "failureCommand" failed.');

        ConnectionManager::drop('test');
    }
}
