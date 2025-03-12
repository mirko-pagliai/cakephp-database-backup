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
use DatabaseBackup\Compression;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * IndexCommandTest class
 *
 * @uses \DatabaseBackup\Command\IndexCommand
 */
class IndexCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected string $command = 'database_backup.index -v';

    /**
     * @uses \DatabaseBackup\Command\IndexCommand::execute()
     */
    #[Test]
    public function testExecute(): void
    {
        $files = array_reverse($this->createSomeBackups());

        $this->exec($this->command);
        $this->assertExitSuccess();
        $this->assertOutputContains('Backup files found: 3');

        //Checks for headers
        $this->assertOutputContainsRow([
            '<info>Filename</info>',
            '<info>Compression</info>',
            '<info>Size</info>',
            '<info>Datetime</info>',
        ]);

        //Checks the row of every file that has been created
        foreach ($files as $file) {
            $Compression = Compression::fromFilename($file);
            $row = [
                basename($file),
                $Compression == Compression::None ? '' : lcfirst($Compression->name),
                Number::toReadableSize(filesize($file) ?: 0),
                DateTime::createFromTimestamp(filemtime($file) ?: 0)->nice(),
            ];

            $this->assertOutputContainsRow($row);
        }

        /**
         * `$matches[1]` will be an array with the basename extracted from the output.
         *
         * Example:
         * ```
         * [
         *    'backup_test_1741792112.sql',
         *    'backup_test_1741792172.sql.gz',
         *    'backup_test_1741792232.sql.bz2',
         * ]
         * ```
         *
         * So we should expect this array to match the basename of the files we initially created.
         *
         * @var \Cake\Console\TestSuite\StubConsoleOutput $out
         */
        $out = $this->_out;
        preg_match_all('/\s+(backup_test_\d+\.sql(?:\.(?:gz|bz2))?)\s+/', implode('', $out->messages()), $matches);
        $this->assertSame(array_map(callback: 'basename', array: $files), $matches[1]);
    }

    /**
     * Test for `execute()` method, with `--reverse` option.
     *
     * @test
     * @uses \DatabaseBackup\Command\IndexCommand::execute()
     */
    #[Test]
    public function testExecuteWithReverseOption(): void
    {
        $files = $this->createSomeBackups();

        $this->exec($this->command . ' --reverse');
        $this->assertExitSuccess();
        $this->assertOutputContains('Backup files found: 3');

        /**
         * `$matches[1]` will be an array with the basename extracted from the output.
         *
         * Example:
         * ```
         * [
         *    'backup_test_1741792112.sql',
         *    'backup_test_1741792172.sql.gz',
         *    'backup_test_1741792232.sql.bz2',
         * ]
         * ```
         *
         * So we should expect this array to match the basename of the files we initially created.
         *
         * @var \Cake\Console\TestSuite\StubConsoleOutput $out
         */
        $out = $this->_out;
        preg_match_all('/\s+(backup_test_\d+\.sql(?:\.(?:gz|bz2))?)\s+/', implode('', $out->messages()), $matches);
        $this->assertSame(array_map(callback: 'basename', array: $files), $matches[1]);
    }

    /**
     * Test for `execute()` method, with no backup files.
     *
     * @uses \DatabaseBackup\Command\IndexCommand::execute()
     */
    #[Test]
    public function testExecuteWithNoFiles(): void
    {
        $this->exec($this->command);
        $this->assertExitSuccess();
        $this->assertOutputContains('Backup files found: 0');
        $this->assertErrorEmpty();
    }
}
