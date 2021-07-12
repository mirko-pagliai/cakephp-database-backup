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
 * RotateCommandTest class
 */
class RotateCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $command = 'database_backup.rotate -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute(): void
    {
        $this->createSomeBackups();
        $this->exec($this->command . ' 1');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `backup_test_\d+\.sql\.bz2` has been deleted/');
        $this->assertOutputRegExp('/Backup `backup_test_\d+\.sql` has been deleted/');
        $this->assertOutputContains('<success>Deleted backup files: 2</success>');
    }

    /**
     * Test for `execute()` method, with an invalid value
     * @test
     */
    public function testExecuteInvalidValue(): void
    {
        $this->exec($this->command . ' string');
        $this->assertExitWithError();
    }

    /**
     * Test for `execute()` method, with no backups
     * @test
     */
    public function testExecuteNoBackups(): void
    {
        $this->exec($this->command . ' 1');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('No backup has been deleted');
    }
}
