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

use Cake\Core\Configure;
use DatabaseBackup\TestSuite\ConsoleIntegrationTestCase;

/**
 * SendCommandTest class
 */
class SendCommandTest extends ConsoleIntegrationTestCase
{
    /**
     * @var string
     */
    protected static $command = 'database_backup.send -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute()
    {
        $file = $this->createBackup();
        $this->exec(self::$command . ' ' . $file . ' recipient@example.com');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('<success>Backup `' . $file . '` was sent via mail</success>');
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

    /**
     * Test for `execute()` method, without a sender in the configuration
     * @test
     */
    public function testExecuteWithoutSenderInConfiguration()
    {
        Configure::write('DatabaseBackup.mailSender', false);
        $this->exec(self::$command . ' file.sql recipient@example.com');
        $this->assertExitWithError();
    }
}
