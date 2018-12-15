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

use DatabaseBackup\TestSuite\ConsoleIntegrationTestTrait;
use DatabaseBackup\TestSuite\TestCase;

/**
 * ImportCommandTest class
 */
class ImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected static $command = 'database_backup.import -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute()
    {
        $backup = $this->createBackup();
        $this->exec(self::$command . ' ' . $backup);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('<success>Backup `' . $backup . '` has been imported</success>');
    }

    /**
     * Test for `execute()` method, with a no existing filename
     * @test
     */
    public function testExecuteWithNoExistingFilename()
    {
        $this->exec(self::$command . ' /noExistingDir/backup.sql');
        $this->assertExitWithError();
    }
}
