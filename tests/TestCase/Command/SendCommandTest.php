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
use DatabaseBackup\TestSuite\CommandTestCase;

/**
 * SendCommandTest class
 */
class SendCommandTest extends CommandTestCase
{
    /**
     * @test
     * @uses \DatabaseBackup\Command\SendCommand::execute()
     */
    public function testExecute(): void
    {
        $command = 'database_backup.send -v';

        $file = createBackup();
        $this->exec($command . ' ' . $file . ' recipient@example.com');
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('<success>Backup `' . $file . '` was sent via mail</success>');

        //With no sender configuration
        Configure::write('DatabaseBackup.mailSender', false);
        $this->exec($command . ' file.sql recipient@example.com');
        $this->assertExitError();

        //With no existing file
        $this->exec($command . ' /noExistingDir/backup.sql');
        $this->assertExitError();
    }
}
