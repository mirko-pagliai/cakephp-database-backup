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
use Cake\I18n\Number;
use DatabaseBackup\TestSuite\TestCase;

/**
 * IndexCommandTest class
 *
 * @uses \DatabaseBackup\Command\IndexCommand
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
        $backups = createSomeBackups();
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

        $expectedRows = array_reverse(array_map(fn (string $filename): array => [
            '',
            basename($filename),
            $this->getExtension($filename),
            $this->getCompression($filename) ?: '',
            Number::toReadableSize(filesize($filename) ?: 0),
            DateTime::createFromTimestamp(filemtime($filename) ?: 0)->nice(),
            '',
        ], $backups));
        $rows = array_map(
            callback: fn (string $row): array => preg_split(pattern: '/\s*\|\s*/', subject: $row) ?: [],
            array: array_slice($this->_out->messages(), 9, 3)
        );
        $this->assertSame($expectedRows, $rows);

        $this->_out = $this->_err = null;

        //With `reverse` option
        $this->exec('database_backup.index -v --reverse');
        $this->assertExitSuccess();
        $this->assertOutputContains('Backup files found: 3');
        $rows = array_map(
            callback: fn (string $row): array => preg_split(pattern: '/\s*\|\s*/', subject: $row) ?: [],
            array: array_slice($this->_out->messages(), 9, 3)
        );
        $this->assertSame(array_reverse($expectedRows), $rows);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Command\IndexCommand::execute()
     */
    public function testExecuteWithNoFiles(): void
    {
        //With no backups
        $this->exec('database_backup.index -v');
        $this->assertExitSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputRegExp('/Driver: (Mysql|Postgres|Sqlite)/');
        $this->assertOutputContains('Backup files found: 0');
        $this->assertErrorEmpty();
    }
}
