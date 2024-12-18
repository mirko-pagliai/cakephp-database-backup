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

/**
 * IndexCommandTest class
 */
class IndexCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @test
     * @throws \ReflectionException
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
        debug($this->_out->messages());
        $this->assertOutputRegExp('/\| \<info\>Filename\<\/info\>\s+\|\s+\<info\>Extension\<\/info\>\s+\|\s+\<info\>Compression\<\/info\>\s+\|\s+\<info\>Size\<\/info\>\s+\|\s+\<info\>Datetime\<\/info\>\s+\|/');
        $this->assertOutputRegExp('/\| backup_test_\d+\.sql\.bz2\s+\| sql\.bz2\s+\| bzip2\s+\| [\d\.]+ (KB|Bytes)\s+\| \w{3} \d{1,2}, \d{4}, \d{1,2}:\d{2} (A|P)M \|/');
        $this->assertOutputRegExp('/\| backup_test_\d+\.sql\.gz\s+\| sql\.gz\s+\| gzip\s+\| [\d\.]+ (KB|Bytes)\s+\| \w{3} \d{1,2}, \d{4}, \d{1,2}:\d{2} (A|P)M \|/');
        $this->assertOutputRegExp('/\| backup_test_\d+\.sql\s+\| sql\s+\|\s+\| [\d\.]+ (KB|Bytes)\s+\| \w{3} \d{1,2}, \d{4}, \d{1,2}:\d{2} (A|P)M \|/');
        $this->assertErrorEmpty();
    }
}
