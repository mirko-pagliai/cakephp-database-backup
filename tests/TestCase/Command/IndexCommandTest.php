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
 * IndexCommandTest class
 */
class IndexCommandTest extends CommandTestCase
{
    /**
     * @test
     * @uses \DatabaseBackup\Command\IndexCommand::execute()
     */
    public function testExecute(): void
    {
        //With no backups
        $this->exec('database_backup.index -v');
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('Backup files found: 0');
        $this->assertErrorEmpty();

        createSomeBackups();
        $this->exec('database_backup.index -v');
        $this->assertExitSuccess();
        $this->assertOutputContains('Backup files found: 3');
        $this->assertOutputRegExp('/backup\.sql\.gz\s+|\s+sql\.gz\s+|\s+gzip\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
        $this->assertOutputRegExp('/backup\.sql\.bz2\s+|\s+sql\.bz2\s+|\s+bzip2\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
        $this->assertOutputRegExp('/backup\.sq\s+|\s+sql\s+|\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
        $this->assertErrorEmpty();
    }
}
