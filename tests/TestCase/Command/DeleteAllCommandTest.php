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
     * Test for `execute()` method
     * @test
     */
    public function testExecute()
    {
        $command = 'database_backup.delete_all -v';

        $this->exec($command);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('No backup has been deleted');

        $this->createSomeBackups(true);
        $this->exec($command);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Backup `backup.sql.gz` has been deleted');
        $this->assertOutputContains('Backup `backup.sql.bz2` has been deleted');
        $this->assertOutputContains('Backup `backup.sql` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 3</success>');
    }
}
