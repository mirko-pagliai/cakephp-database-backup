<?php
/** @noinspection PhpUnhandledExceptionInspection */
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

use DatabaseBackup\TestSuite\CommandTestCase;

/**
 * ImportCommandTest class
 */
class ImportCommandTest extends CommandTestCase
{
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
        $backup = createBackup();
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
     * Test for `execute()` method, with `timeout` option
     * @test
     * @uses \DatabaseBackup\Command\ImportCommand::execute()
     */
    public function testExecuteTimeoutOption(): void
    {
        $backup = createBackup();
        $this->exec($this->command . ' --timeout 10 ' . $backup);
        $this->assertExitSuccess();
        $this->assertOutputContains('Timeout for shell commands: 10 seconds');
        $this->assertErrorEmpty();
    }
}
