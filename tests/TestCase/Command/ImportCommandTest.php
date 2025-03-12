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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * ImportCommandTest class.
 */
#[CoversClass(ImportCommand::class)]
class ImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected string $command = 'database_backup.import -v';

    /**
     * @uses \DatabaseBackup\Command\ImportCommand::makeAbsoluteFilename()
     */
    #[Test]
    #[TestWith(['file.sql', 'file.sql'])]
    #[TestWith([TMP . 'backups' . DS . 'file.sql', TMP . 'backups' . DS . 'file.sql'])]
    #[TestWith([ROOT . 'version', 'version'])]
    public function testMakeAbsoluteFilename(string $expectedFilename, string $filename): void
    {
        $ImportCommand = new ImportCommand();
        $result = $ImportCommand->makeAbsoluteFilename($filename);
        $this->assertSame($expectedFilename, $result);
    }

    /**
     * @uses \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    public function testExecute(): void
    {
        $backup = $this->createBackup();

        $this->exec($this->command . ' ' . $backup);
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: Cake\\\\Database\\\\Driver\\\\\w+/');
        $this->assertOutputContains('<success>Backup `' . $backup . '` has been imported</success>');
        $this->assertErrorEmpty();
    }

    /**
     * @uses \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    public function testExecuteNoExistingFile(): void
    {
        $filename = '/noExistingDir/backup.sql';
        $this->exec($this->command . ' ' . $filename);
        $this->assertExitError();
        $this->assertErrorContains('File or directory `' . $filename . '` is not readable');
    }

    /**
     * @uses \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    public function testExecuteTimeoutOption(): void
    {
        $this->exec($this->command . ' --timeout 10 ' . $this->createBackup());
        $this->assertExitSuccess();
        $this->assertOutputContains('Timeout for shell commands: 10 seconds');
        $this->assertErrorEmpty();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Command\ImportCommand::execute()
     */
    #[Test]
    public function testExecuteOnStoppedEvent(): void
    {
        $BackupImport = $this->createConfiguredMock(BackupImport::class, ['import' => false]);
        $ImportCommand = $this->createPartialMock(ImportCommand::class, ['getBackupImport']);
        $ImportCommand->method('getBackupImport')
            ->willReturn($BackupImport);

        $this->expectException(StopException::class);
        $this->expectExceptionMessage('The `Backup.beforeImport` event stopped the operation');
        $ImportCommand->run(
            ['--filename' => $this->createBackup(fakeBackup: true)],
            new ConsoleIo(new StubConsoleOutput(), new StubConsoleOutput())
        );
    }
}
