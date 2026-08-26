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
use Cake\Core\Configure;
use DatabaseBackup\Command\ExportCommand;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use Exception;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * ExportCommandTest.
 */
#[CoversClass(ExportCommand::class)]
class ExportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @link \DatabaseBackup\Command\ExportCommand::buildOptionParser()
     */
    #[Test]
    #[RequiresOperatingSystemFamily('Linux')]
    public function testBuildOptionParser(): void
    {
        $this->exec('database_backup.export -h');
        $this->assertExitSuccess();
        $this->assertOutputContains('Exports a database backup');
        $this->assertOutputContains('cake database_backup.export [options]');

        /**
         * Requires at least 5.2.10, otherwise the `--quiet` option has a slightly different description.
         *
         * @todo remove after it will require at least Cakephp 5.2.10
         */
        $this->skipUnless(version_compare(Configure::version(), '5.2.10', '>='));

        $root = ROOT;
        $defaultTarget = Configure::readOrFail('DatabaseBackup.target');

        $expected = <<<txt
Exports a database backup

<info>Usage:</info>
cake database_backup.export [options]

<info>Options:</info>

--compression, -c  Compression type. By default, no compression will be
                   used <comment>(choices: gzip|bzip2)</comment>
--connection       Name of the alternative connection to use, for
                   example if you are not using the default connection
--debug            Enable debug mode
--filename, -f     Filename. It can be an absolute path and may contain
                   patterns. The compression type will be automatically
                   set. Filenames can be relative to
                   <comment>$root</comment>
                   (root of your app) or
                   <comment>$defaultTarget</comment>
                   (default target directory).
--help, -h         Display this help.
--quiet, -q        Enable quiet output and non-interactive mode.
--timeout, -t      Timeout for shell commands
--verbose, -v      Enable verbose output.

txt;
        $this->assertOutputContains($expected);
        $this->assertErrorEmpty();
    }

    /**
     * @link \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testExecute(): void
    {
        $expectedFilename = TMP . 'my_backup.sql';

        $BackupExport = Mockery::mock('overload:' . BackupExport::class);

        $BackupExport
            ->shouldReceive('__construct')
            ->once()
            ->with('');

        $BackupExport
            ->shouldNotReceive('timeout');

        $BackupExport
            ->shouldNotReceive('filename');

        $BackupExport
            ->shouldNotReceive('compression');

        $BackupExport
            ->shouldReceive('export')
            ->once()
            ->andReturn($expectedFilename);

        $this->exec('database_backup.export');
        $this->assertExitSuccess();
        $this->assertOutputContains('<success>Backup `' . $expectedFilename . '` has been exported</success>');
        $this->assertErrorEmpty();
    }

    /**
     * Tests for the `execute()` method with the `--filename` option and absolute and relative filenames.
     *
     * @link \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    #[TestWith([ROOT . 'backups' . DS . 'absolute_filename.sql'])]
    #[TestWith(['backups/relative_filename.sql'])]
    #[RunInSeparateProcess]
    public function testExecuteWithFilenameOption(string $filename): void
    {
        $BackupExport = Mockery::mock('overload:' . BackupExport::class);

        $BackupExport
            ->shouldReceive('__construct')
            ->once()
            ->with('');

        $BackupExport
            ->shouldReceive('filename')
            ->with($filename)
            ->once();

        $BackupExport
            ->shouldReceive('export')
            ->once()
            ->andReturn($filename);

        $this->exec("database_backup.export --filename $filename");
        $this->assertExitSuccess();
        $this->assertOutputRegExp('#^<success>Backup `[^`]*' . preg_quote(basename($filename)) . '` has been exported</success>$#');
        $this->assertErrorEmpty();
    }

    /**
     * Tests for the `execute()` method with some options.
     *
     * @link \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteWithSomeOptions(): void
    {
        $filename = 'custom_filename.sql';

        $BackupExport = Mockery::mock('overload:' . BackupExport::class);

        $BackupExport
            ->shouldReceive('__construct')
            ->once()
            ->with('custom_connection');

        $BackupExport
            ->shouldReceive('timeout')
            ->once()
            ->with(120);

        $BackupExport
            ->shouldReceive('filename')
            ->once()
            ->with($filename);

        //Note that in this case the `--compression` option was passed, but is ignored
        $BackupExport
            ->shouldNotReceive('compression');

        $BackupExport
            ->shouldReceive('export')
            ->once()
            ->andReturn($filename);

        $this->exec('database_backup.export --connection custom_connection --timeout 120 --compression gzip --filename ' . $filename);
        $this->assertExitSuccess();
        $this->assertOutputContains('<success>Backup `' . $filename . '` has been exported</success>');
        $this->assertErrorEmpty();
    }

    /**
     * Tests for the `execute()` method with the `--compression` option.
     *
     * @link \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteWithCompressionOption(): void
    {
        $BackupExport = Mockery::mock('overload:' . BackupExport::class);

        $BackupExport
            ->shouldReceive('__construct')
            ->once()
            ->with('');

        $BackupExport
            ->shouldNotReceive('timeout');

        $BackupExport
            ->shouldNotReceive('filename');

        $BackupExport
            ->shouldReceive('compression')
            ->once()
            ->with('gzip');

        $BackupExport
            ->shouldReceive('export')
            ->once()
            ->andReturn('my_backup.sql.gz');

        $this->exec('database_backup.export --compression gzip');
        $this->assertExitSuccess();
        $this->assertOutputContains('<success>Backup `my_backup.sql.gz` has been exported</success>');
        $this->assertErrorEmpty();
    }

    /**
     * Tests for the `execute()` method when `BackupExport::import()` throws an exception.
     *
     * @link \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteOnException(): void
    {
        Mockery::mock('overload:' . BackupExport::class)
            ->shouldReceive('export')
            ->once()
            ->andThrow(new Exception('Exception message'));

        $this->exec('database_backup.export');
        $this->assertExitError();
        $this->assertErrorContains('<error>Exception message</error>');
    }

    /**
     * Tests for the `execute()` method on the stopped event (`BackupExport::export()` returns `false`).
     *
     * @link \DatabaseBackup\Command\ExportCommand::execute()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteOnStoppedEvent(): void
    {
        Mockery::mock('overload:' . BackupExport::class)
            ->shouldReceive('export')
            ->once()
            ->andReturnFalse();

        $this->exec('database_backup.export');
        $this->assertExitError();
        $this->assertErrorContains('<error>The `Backup.beforeExport` event stopped the operation</error>');
    }
}
