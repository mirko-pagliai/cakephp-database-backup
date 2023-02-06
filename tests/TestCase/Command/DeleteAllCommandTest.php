<?php
/** @noinspection PhpUnhandledExceptionInspection */
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

use DatabaseBackup\TestSuite\CommandTestCase;

/**
 * DeleteAllCommandTest class
 */
class DeleteAllCommandTest extends CommandTestCase
{
    /**
     * @test
     * @uses \DatabaseBackup\Command\DeleteAllCommand::execute()
     */
    public function testExecute(): void
    {
        $command = 'database_backup.delete_all -v';

        $files = createSomeBackups();
        $this->exec($command);
        $this->assertExitSuccess();
        foreach ($files as $file) {
            $this->assertOutputContains('Backup `' . $file . '` has been deleted');
        }
        $this->assertOutputContains('<success>Deleted backup files: 3</success>');

        //With no backups
        $this->exec($command);
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('No backup has been deleted');
    }
}
