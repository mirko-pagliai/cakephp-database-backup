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
     * @var string
     */
    protected $command = 'database_backup.send -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute(): void
    {
        $file = $this->createBackup();
        $this->exec($this->command . ' ' . $file . ' recipient@example.com');
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('<success>Backup `' . $file . '` was sent via mail</success>');
    }

    /**
     * Test for `execute()` method, with no existing file
     * @test
     */
    public function testExecuteNoExistingFile(): void
    {
        $this->exec($this->command . ' /noExistingDir/backup.sql');
        $this->assertExitWithError();
    }

    /**
     * Test for `execute()` method, with no sender configuration
     * @test
     */
    public function testExecuteNoSender(): void
    {
        Configure::write('DatabaseBackup.mailSender', false);
        $this->exec($this->command . ' file.sql recipient@example.com');
        $this->assertExitWithError();
    }
}
