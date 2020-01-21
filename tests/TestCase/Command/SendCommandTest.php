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

use Cake\Core\Configure;
use DatabaseBackup\TestSuite\TestCase;
use MeTools\TestSuite\ConsoleIntegrationTestTrait;

/**
 * SendCommandTest class
 */
class SendCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute()
    {
        $command = 'database_backup.send -v';
        $file = $this->createBackup();

        $this->exec($command . ' ' . $file . ' recipient@example.com');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('<success>Backup `' . $file . '` was sent via mail</success>');

        //With a no existing filename
        $this->exec($command . ' /noExistingDir/backup.sql');
        $this->assertExitWithError();

        //Without a sender in the configuration
        Configure::write('DatabaseBackup.mailSender', false);
        $this->exec($command . ' file.sql recipient@example.com');
        $this->assertExitWithError();
    }
}
