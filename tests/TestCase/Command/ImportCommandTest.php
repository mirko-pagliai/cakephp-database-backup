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
use Cake\TestSuite\TestCase;
use DatabaseBackup\Command\ImportCommand;
use DatabaseBackup\Utility\BackupImport;
use Exception;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Throwable;
use TypeError;

/**
 * ImportCommandTest.
 */
#[CoversClass(ImportCommand::class)]
class ImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        ConnectionManager::setConfig('fake_connection', new FakeConnection());
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        ConnectionManager::drop('fake_connection');
    }

    /**
     * @link \DatabaseBackup\Command\ImportCommand::buildOptionParser()
     */
    #[Test]
    public function testBuildOptionParser(): void
    {
        $Parser = (new ImportCommand())->getOptionParser();
        $arguments = $Parser->arguments();

        $this->assertSame(['filename'], $Parser->argumentNames());
        $this->assertTrue($arguments[0]->isRequired());
    }

    /**
     * Tests for the `execute()` method with an absolute filename and a relative filename.
     *
     * @link \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    #[TestWith([ROOT . 'backups' . DS . 'absolute_filename.sql', ROOT . 'backups' . DS . 'absolute_filename.sql'])]
    #[TestWith([ROOT . 'backups' . DS . 'relative_filename.sql', 'backups/relative_filename.sql'])]
    #[RunInSeparateProcess]
    public function testExecute(string $expectedFilename, string $filename): void
    {
        $BackupImport = Mockery::mock('overload:' . BackupImport::class);

        $BackupImport
            ->shouldReceive('__construct')
            ->with('')
            ->once();

        $BackupImport
            ->shouldNotReceive('timeout');

        $BackupImport
            ->shouldReceive('filename')
            ->with($expectedFilename)
            ->once();

        $BackupImport
            ->shouldReceive('import')
            ->once()
            ->andReturn($expectedFilename);

        $this->exec("database_backup.import {$filename}");
        $this->assertExitSuccess();
        $this->assertOutputRegExp(
            '#^<success>Backup `[^`]+' . preg_quote(basename($filename), '#') . '` has been imported</success>$#',
        );
        $this->assertErrorEmpty();
    }

    /**
     * Tests for the `execute()` method with no existing file (absolute and relative).
     *
     * @link \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    #[TestWith([TMP . 'no_existing_filename.sql', TMP . 'no_existing_filename.sql'])]
    #[TestWith([ROOT . 'backups' . DS . 'no_existing_filename.sql', 'backups' . DS . 'no_existing_filename.sql'])]
    public function testExecuteWithNoExistingFile(string $expectedFilename, string $filename): void
    {
        $this->exec("database_backup.import {$filename}");
        $this->assertExitError();
        $this->assertErrorContains(
            "<error>Symfony\Component\Filesystem\Exception\IOException: file `{$expectedFilename}` does not exist</error>",
        );
    }

    /**
     * Tests for the `execute()` method with some options.
     *
     * @link \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteWithSomeOptions(): void
    {
        $filename = ROOT . 'backups' . DS . 'absolute_filename.sql';

        $BackupImport = Mockery::mock('overload:' . BackupImport::class);

        $BackupImport
            ->shouldReceive('__construct')
            ->with('custom_connection')
            ->once();

        $BackupImport
            ->shouldReceive('timeout')
            ->with(120)
            ->once();

        $BackupImport
            ->shouldReceive('filename')
            ->with($filename)
            ->once();

        $BackupImport
            ->shouldReceive('import')
            ->once()
            ->andReturn($filename);

        $this->exec("database_backup.import --connection custom_connection --timeout 120 {$filename}");
        $this->assertExitSuccess();
        $this->assertOutputRegExp(
            '#^<success>Backup `[^`]+' . preg_quote(basename($filename), '#') . '` has been imported</success>$#',
        );
        $this->assertErrorEmpty();
    }

    /**
     * Tests for the `execute()` method without providing the required filename argument.
     *
     * @link \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    public function testExecuteWithNoFilename(): void
    {
        $this->exec('database_backup.import');
        $this->assertExitError();
        $this->assertErrorContains('Error: Missing required argument. The `filename` argument is required.');
    }

    /**
     * Tests for the `execute()` method with an invalid `--timeout` option.
     *
     * @link \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    #[TestWith(['0'])]
    #[TestWith(['abc'])]
    #[TestWith(['1.5'])]
    public function testExecuteWithInvalidTimeout(string $timeout): void
    {
        $this->exec("database_backup.import --connection fake_connection --timeout {$timeout} backups/file.sql");
        $this->assertExitError();
        $this->assertErrorContains(
            '<error>InvalidArgumentException: the `--timeout` option must be an integer greater than 0</error>',
        );
    }

    /**
     * Tests for the `execute()` method when `BackupImport::import()` throws an exception or an error.
     *
     * @link \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    #[TestWith([new Exception('This is an exception message'), 'Exception: this is an exception message'])]
    #[TestWith([new TypeError('This is an exception message'), 'TypeError: this is an exception message'])]
    #[RunInSeparateProcess]
    public function testExecuteOnException(Throwable $Exception, string $expectedErrorMessage): void
    {
        $BackupImport = Mockery::mock('overload:' . BackupImport::class);

        $BackupImport
            ->shouldReceive('filename');

        $BackupImport
            ->shouldReceive('import')
            ->once()
            ->andThrow($Exception);

        $this->exec('database_backup.import my_backup.sql');
        $this->assertExitError();
        $this->assertErrorContains("<error>{$expectedErrorMessage}</error>");
    }

    /**
     * Tests for the `execute()` method on the stopped event.
     *
     * @link \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testExecuteOnStoppedEvent(): void
    {
        $BackupImport = Mockery::mock('overload:' . BackupImport::class);

        $BackupImport
            ->shouldReceive('filename');

        $BackupImport
            ->shouldReceive('import')
            ->once()
            ->andReturn(false);

        $this->exec('database_backup.import my_backup.sql');
        $this->assertExitError();
        $this->assertErrorContains(
            '<error>Cake\Console\Exception\StopException: the `Backup.beforeImport` event stopped the operation</error>',
        );
    }
}
