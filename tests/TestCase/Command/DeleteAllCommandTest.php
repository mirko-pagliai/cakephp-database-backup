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
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * DeleteAllCommandTest class
 *
 * @uses \DatabaseBackup\Command\DeleteAllCommand
 */
class DeleteAllCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @test
     * @uses \DatabaseBackup\Command\DeleteAllCommand::execute()
     */
    public function testExecute(): void
    {
        $files = createSomeBackups();
        $this->exec('database_backup.delete_all -v');
        $this->assertExitSuccess();
        foreach ($files as $file) {
            $this->assertOutputContains('Backup `' . $file . '` has been deleted');
        }
        $this->assertOutputContains('<success>Deleted backup files: 3</success>');
        $this->assertErrorEmpty();

        //With no backups
        $this->exec('database_backup.delete_all -v');
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('No backup has been deleted');
        $this->assertErrorEmpty();
    }

    /**
     * @test
     * @uses \DatabaseBackup\Command\DeleteAllCommand::execute()
     */
    #[WithoutErrorHandler]
    public function testExecuteIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->exec('database_backup.delete_all -v');
        });
    }
}
