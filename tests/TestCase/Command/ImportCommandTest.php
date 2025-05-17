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
use DatabaseBackup\Command\ImportCommand;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * ImportCommandTest.
 */
#[CoversClass(ImportCommand::class)]
class ImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    #[Test]
    public function testBuildOptionParser(): void
    {
        $this->exec('database_backup.import -h');
        $this->assertExitSuccess();
        $this->assertOutputContains('cake database_backup.import [--connection default] [-h] [-q] [-t] [-v]');
        $this->assertOutputContains('Imports a database backup');
    }
}
