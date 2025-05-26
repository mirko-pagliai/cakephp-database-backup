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
use DatabaseBackup\Command\ImportCommand;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupImport;
use Exception;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * ImportCommandTest.
 *
 * @property \Cake\Console\TestSuite\StubConsoleOutput $_out
 */
#[CoversClass(ImportCommand::class)]
class ImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    #[Test]
    #[TestWith(['file.sql', 'file.sql'])]
    #[TestWith([TMP . 'backups' . DS . 'file.sql', TMP . 'backups' . DS . 'file.sql'])]
    #[TestWith([ROOT . 'version', 'version'])]
    public function testMakeAbsolutePath(string $expectedPath, string $path): void
    {
        $result = ImportCommand::makeAbsolutePath($path);
        $this->assertSame($expectedPath, $result);
    }

    #[Test]
    #[RequiresOperatingSystemFamily('Linux')]
    public function testBuildOptionParser(): void
    {
        $this->exec('database_backup.import -h');
        $this->assertExitSuccess();

$expected = <<<txt
Imports a database backup

<info>Usage:</info>
cake database_backup.import [--connection] [-h] [-q] [-t] [-v] <filename>

<info>Options:</info>

--connection      Name of the alternative connection to use, for example
                  if you are not using the default connection
--help, -h        Display this help.
--quiet, -q       Enable quiet output.
--timeout, -t     Timeout for shell commands
--verbose, -v     Enable verbose output.

<info>Arguments:</info>

filename  Filename. It can be an absolute path

txt;
        $this->assertSame($expected, $this->_out->messages()[0]);
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecute(): void
    {
        $filename = 'custom_filename.sql';

        $BackupImport = Mockery::mock('overload:' . BackupImport::class);
        $BackupImport->shouldReceive('__construct')->with('')->once();
        $BackupImport->shouldNotReceive('timeout');
        $BackupImport->shouldReceive('filename')->with($filename)->once();
        $BackupImport->shouldReceive('import')->once()->andReturn($filename);

        $this->exec('database_backup.import ' . $filename);

        $this->assertExitSuccess();
        $this->assertOutputContains('<success>Backup `' . $filename . '` has been imported</success>');
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteWithSomeOptions(): void
    {
        $filename = 'custom_filename.sql';

        $BackupImport = Mockery::mock('overload:' . BackupImport::class);
        $BackupImport->shouldReceive('__construct')->with('custom_connection')->once();
        $BackupImport->shouldReceive('timeout')->with(120)->once();
        $BackupImport->shouldReceive('filename')->with($filename)->once();
        $BackupImport->shouldReceive('import')->once()->andReturn($filename);

        $this->exec('database_backup.import --connection custom_connection --timeout 120 ' . $filename);

        $this->assertExitSuccess();
        $this->assertOutputContains('<success>Backup `' . $filename . '` has been imported</success>');
    }

        /**
     * Tests the execution of the database_backup.import command without providing the required filename argument
     */
    #[Test]
    public function testExecuteWithNoFilename(): void
    {
        $this->exec('database_backup.import');

        $this->assertExitError();
        $this->assertErrorContains('Error: Missing required argument. The `filename` argument is required.');
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteOnException(): void
    {
        $BackupImport = Mockery::mock('overload:' . BackupImport::class);
        $BackupImport->shouldReceive('filename');
        $BackupImport->shouldReceive('import')->once()->andThrow(new Exception('Exception message'));

        $this->exec('database_backup.import my_backup.sql');
        $this->assertExitError();
        $this->assertErrorContains('<error>Exception message</error>');
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteOnStoppedEvent(): void
    {
        $BackupImport = Mockery::mock('overload:' . BackupImport::class);
        $BackupImport->shouldReceive('filename');
        $BackupImport->shouldReceive('import')->once()->andReturnFalse();

        $this->exec('database_backup.import my_backup.sql');

        $this->assertExitError();
        $this->assertErrorContains('<error>The `Backup.beforeImport` event stopped the operation</error>');
    }
}
