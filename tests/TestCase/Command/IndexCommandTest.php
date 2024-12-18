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
use Cake\I18n\DateTime;
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

        /**
         * Extracts and checks only the lines containing headers and data about backup file
         */
        $expectedHeaders = [
            '<info>Filename</info>',
            '<info>Extension</info>',
            '<info>Compression</info>',
            '<info>Size</info>',
            '<info>Datetime</info>',
        ];
        $headers = preg_split(pattern: '/\s*\|\s*/', subject: $this->_out->messages()[7], flags: PREG_SPLIT_NO_EMPTY);
        $this->assertSame($expectedHeaders, $headers);
        $rows = array_map(
            callback: fn (string $row): array  => preg_split(pattern: '/\s*\|\s*/', subject: $row, flags: PREG_SPLIT_NO_EMPTY),
            array: array_slice($this->_out->messages(), 9, 3)
        );
        $this->assertMatchesRegularExpression('/^backup_test_\d+\.sql\.bz2$/', $rows[0][0]);
        $this->assertSame('sql.bz2', $rows[0][1]);
        $this->assertSame('bzip2', $rows[0][2]);
        $this->assertMatchesRegularExpression('/^[\d\.]+ \w+$/', $rows[0][3]);
        $this->assertMatchesRegularExpression('/^\w{3} \d{1,2}, \d{4}, \d{1,2}:\d{2}/', $rows[0][4]);
        $this->assertErrorEmpty();
    }
}
