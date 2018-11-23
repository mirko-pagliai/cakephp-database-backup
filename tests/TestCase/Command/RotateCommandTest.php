<?php
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

use DatabaseBackup\TestSuite\ConsoleIntegrationTestCase;

/**
 * RotateCommandTest class
 */
class RotateCommandTest extends ConsoleIntegrationTestCase
{
    /**
     * @var string
     */
    protected static $command = 'database_backup.rotate -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute()
    {
        $this->exec(self::$command . ' 1 -v');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('No backup has been deleted');

        $this->createSomeBackups(true);
        $this->exec(self::$command . ' 1 -v');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Backup `backup.sql.bz2` has been deleted');
        $this->assertOutputContains('Backup `backup.sql` has been deleted');
        $this->assertOutputContains('<success>Deleted backup files: 2</success>');
    }

    /**
     * Test for `execute()` method, with an invalid value
     * @test
     */
    public function testExecuteInvalidValue()
    {
        $this->exec(self::$command . ' string');
        $this->assertExitWithError();
    }
}
