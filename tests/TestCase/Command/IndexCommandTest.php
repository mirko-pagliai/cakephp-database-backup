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

use DatabaseBackup\TestSuite\ConsoleIntegrationTestTrait;
use DatabaseBackup\TestSuite\TestCase;

/**
 * IndexCommandTest class
 */
class IndexCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected static $command = 'database_backup.index -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute()
    {
        $this->exec(self::$command);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputContains('Backup files found: 0');

        $this->createSomeBackups(true);
        $this->exec(self::$command);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Backup files found: 3');
        $this->assertOutputRegExp('/backup\.sql\.gz\s+|\s+sql\.gz\s+|\s+gzip\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
        $this->assertOutputRegExp('/backup\.sql\.bz2\s+|\s+sql\.bz2\s+|\s+bzip2\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
        $this->assertOutputRegExp('/backup\.sq\s+|\s+sql\s+|\s+|\s+[\d\.]+ \w+\s+|\s+[\d\/]+, [\d:]+ (AP)M/');
    }
}
