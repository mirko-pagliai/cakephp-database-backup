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
use DatabaseBackup\Utility\BackupManager;

/**
 * RotateCommandTest class
 */
class RotateCommandTest extends CommandTestCase
{
    /**
     * @test
     * @uses \DatabaseBackup\Command\RotateCommand::execute()
     */
    public function testExecute(): void
    {
        createSomeBackups();
        $this->exec('database_backup.rotate -v 1');
        $this->assertExitSuccess();
        $this->assertOutputRegExp('/Backup `backup_test_\d+\.sql\.bz2` has been deleted/');
        $this->assertOutputRegExp('/Backup `backup_test_\d+\.sql` has been deleted/');
        $this->assertOutputContains('<success>Deleted backup files: 2</success>');
        $this->assertErrorEmpty();

        //With no backups
        BackupManager::deleteAll();
        $this->exec('database_backup.rotate -v 1');
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('No backup has been deleted');
        $this->assertErrorEmpty();

        //With an invalid value
        $this->exec('database_backup.rotate -v string');
        $this->assertExitError();
    }
}
