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
 * DeleteAllCommandTest class
 */
class DeleteAllCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected $command = 'database_backup.delete_all -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute(): void
    {
        $files = $this->createSomeBackups();
        $this->exec($this->command);
        $this->assertExitWithSuccess();
        foreach ($files as $file) {
            $this->assertOutputContains('Backup `' . $file . '` has been deleted');
        }
        $this->assertOutputContains('<success>Deleted backup files: 3</success>');
    }

    /**
     * Test for `execute()` method, with no backups
     * @test
     */
    public function testExecuteNoBackups(): void
    {
        $this->exec($this->command);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('No backup has been deleted');
    }
}
