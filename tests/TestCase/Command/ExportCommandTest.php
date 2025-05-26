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

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use DatabaseBackup\Command\ExportCommand;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use Exception;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

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

        $expected = <<<txt
Exports a database backup

<info>Usage:</info>
cake database_backup.export [options]

<info>Options:</info>

--compression, -c  Compression type. By default, no compression will be
                   used <comment>(choices: gzip|bzip2)</comment>
--connection       Name of the alternative connection to use, for
                   example if you are not using the default connection
--filename, -f     Filename. It can be an absolute path and may contain
                   patterns. The compression type will be automatically
                   set
--help, -h         Display this help.
--quiet, -q        Enable quiet output.
--timeout, -t      Timeout for shell commands
--verbose, -v      Enable verbose output.

txt;
        $this->assertSame($expected, $this->_out->messages()[0]);
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecute(): void
    {
        $BackupExport = Mockery::mock('overload:' . BackupExport::class);
        $BackupExport->shouldReceive('__construct')->with('')->once();
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
        $BackupExport->shouldReceive('__construct')->with('')->once();
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
    public function testExecuteOnException(): void
    {
        $BackupExport = Mockery::mock('overload:' . BackupExport::class);
        $BackupExport->shouldReceive('export')->once()->andThrow(new Exception('Exception message'));

        $this->exec('database_backup.export --connection test');
        $this->assertExitError();
        $this->assertErrorContains('<error>Exception message</error>');
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
}
