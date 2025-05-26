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
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\Attributes\Test;

/**
 * ImportCommandTest.
 */
#[CoversClass(ImportCommand::class)]
class ImportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    #[Test]
    #[RequiresOperatingSystemFamily('Linux')]
    public function testBuildOptionParser(): void
    {
        $this->exec('database_backup.import -h');
        $this->assertExitSuccess();

$expected = <<<txt
Imports a database backup

<info>Usage:</info>
cake database_backup.import [--connection] [-h] [-q] [-t] [-v] <filename>

<info>Options:</info>

--connection      Name of the alternative connection to use, for example
                  if you are not using the default connection
--help, -h        Display this help.
--quiet, -q       Enable quiet output.
--timeout, -t     Timeout for shell commands
--verbose, -v     Enable verbose output.

<info>Arguments:</info>

filename  Filename. It can be an absolute path

txt;
        $this->assertSame($expected, $this->_out->messages()[0]);
    }
}
