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

use Cake\Console\Arguments;
use DatabaseBackup\Command\Command;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * CommandTest.
 */
#[CoversClass(Command::class)]
class CommandTest extends TestCase
{
    /**
     * @param array<string, mixed> $options
     * @link \DatabaseBackup\Command\Command::getTimeoutOption()
     */
    #[Test]
    #[TestWith([null, []])]
    #[TestWith([1, ['timeout' => '1']])]
    #[TestWith([120, ['timeout' => '120']])]
    #[TestWith([120, ['timeout' => '0120']])]
    public function testGetTimeoutOption(?int $expectedTimeout, array $options): void
    {
        $Command = new class extends Command {
        };

        $result = $Command->getTimeoutOption(new Arguments([], $options, []));
        $this->assertSame($expectedTimeout, $result);
    }

    /**
     * @link \DatabaseBackup\Command\Command::getTimeoutOption()
     */
    #[Test]
    #[TestWith([''])]
    #[TestWith(['0'])]
    #[TestWith(['00'])]
    #[TestWith(['-5'])]
    #[TestWith(['1.5'])]
    #[TestWith(['abc'])]
    #[TestWith(['120s'])]
    #[TestWith([true])]
    public function testGetTimeoutOptionWithInvalidValue(mixed $timeout): void
    {
        $Command = new class extends Command {
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `--timeout` option must be an integer greater than 0');
        $Command->getTimeoutOption(new Arguments([], ['timeout' => $timeout], []));
    }

    /**
     * @link \DatabaseBackup\Command\Command::makeAbsolutePath()
     */
    #[Test]
    #[TestWith([ROOT . 'composer.json', 'composer.json'])]
    #[TestWith([ROOT . 'composer.json', ROOT . 'composer.json'])]
    #[TestWith([TMP . 'absolute_tmp_file.sql', TMP . 'absolute_tmp_file.sql'])]
    public function testMakeAbsolutePath(string $expectedAbsolutePath, string $path): void
    {
        $Command = new class extends Command {
        };

        $result = $Command->makeAbsolutePath($path);
        $this->assertSame($expectedAbsolutePath, $result);
    }

    /**
     * @link \DatabaseBackup\Command\Command::makeRelativePath()
     */
    #[Test]
    #[TestWith(['backup.sql', 'backup.sql'])]
    #[TestWith(['backup.sql', ROOT . 'backup.sql'])]
    #[TestWith(['backups/backup.sql', ROOT . 'backups/backup.sql'])]
    #[TestWith([TMP . 'backup.sql', TMP . 'backup.sql'])]
    #[TestWith(['/anotherDir/backup.sql', '/anotherDir/backup.sql'])]
    public function testMakeRelativePath(string $expectedRelativePath, string $path): void
    {
        $Command = new class extends Command {
        };

        $result = $Command->makeRelativePath($path);
        $this->assertSame($expectedRelativePath, $result);
    }
}
