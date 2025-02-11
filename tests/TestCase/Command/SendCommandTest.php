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

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use DatabaseBackup\TestSuite\TestCase;

/**
 * SendCommandTest class.
 *
 * @uses \DatabaseBackup\Command\SendCommand
 */
class SendCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @test
     * @uses \DatabaseBackup\Command\SendCommand::execute()
     */
    public function testExecute(): void
    {
        $file = createBackup();
        $this->exec('database_backup.send -v' . ' ' . $file . ' recipient@example.com');
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('<success>Backup `' . $file . '` was sent via mail</success>');

        //With no sender configuration
        Configure::write('DatabaseBackup.mailSender', false);
        $this->exec('database_backup.send -v file.sql recipient@example.com');
        $this->assertExitError();

        //With no existing file
        $this->exec('database_backup.send -v /noExistingDir/backup.sql');
        $this->assertExitError();
    }
}
