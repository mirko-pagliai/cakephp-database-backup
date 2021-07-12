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

use DatabaseBackup\TestSuite\TestCase;
use MeTools\TestSuite\ConsoleIntegrationTestTrait;

/**
 * ExportCommandTest class
 */
class ExportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $command = 'database_backup.export -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute(): void
    {
        $this->exec($this->command);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql` has been exported/');
    }

    /**
     * Test for `execute()` method, with `compression` param
     * @test
     */
    public function testExecuteCompressionParam(): void
    {
        $this->exec($this->command . ' --compression bzip2');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql\.bz2` has been exported/');
    }

    /**
     * Test for `execute()` method, with `filename` param
     * @test
     */
    public function testExecuteFilenameParam(): void
    {
        $this->exec($this->command . ' --filename backup.sql');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup.sql` has been exported/');
    }

    /**
     * Test for `execute()` method, with `rotate` param
     * @test
     */
    public function testExecuteRotateParam(): void
    {
        $files = $this->createSomeBackups();
        $this->exec($this->command . ' --rotate 3 -v');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql` has been exported/');
        $this->assertOutputContains('Backup `' . basename(array_value_first($files)) . '` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 1</success>');
    }

    /**
     * Test for `execute()` method, with `send` param
     * @test
     */
    public function testExecuteSendParam(): void
    {
        $this->exec($this->command . ' --send mymail@example.com');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql` has been exported/');
        $this->assertOutputRegExp('/Backup `[\w\-\/\:\\\\]+backup_test_\d+\.sql` was sent via mail/');
    }

    /**
     * Test for `execute()` method, with an invalid option value
     * @test
     */
    public function testExecuteInvalidOptionValue(): void
    {
        $this->exec($this->command . ' --filename /noExistingDir/backup.sql');
        $this->assertExitWithError();
    }
}
