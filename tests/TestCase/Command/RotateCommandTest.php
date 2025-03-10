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
 * RotateCommandTest class
 *
 * @uses \DatabaseBackup\Command\RotateCommand
 */
class RotateCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @test
     * @uses \DatabaseBackup\Command\RotateCommand::execute()
     */
    public function testExecute(): void
    {
        //With no backups
        $this->exec('database_backup.rotate -v 1');
        $this->assertExitSuccess();
        $this->assertOutputContains('No backup has been deleted');
        $this->assertErrorEmpty();

        $expectedFiles = createSomeBackups();
        array_pop($expectedFiles);
        $this->exec('database_backup.rotate -v 1');
        $this->assertExitSuccess();
        foreach ($expectedFiles as $expectedFile) {
            $this->assertOutputContains('Backup `' . basename($expectedFile) . '` has been deleted');
        }
        $this->assertOutputContains('<success>Deleted backup files: 2</success>');
        $this->assertErrorEmpty();

        //With an invalid value
        $this->exec('database_backup.rotate -v string');
        $this->assertExitError();
    }

    /**
     * @test
     * @uses \DatabaseBackup\Command\RotateCommand::execute()
     */
    #[WithoutErrorHandler]
    public function testExecuteIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->exec('database_backup.rotate -v 1');
        });
    }
}
