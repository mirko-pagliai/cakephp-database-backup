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

use DatabaseBackup\Command\Command;
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Filesystem\Filesystem;

/**
 * CommandTest.
 */
#[CoversClass(Command::class)]
class CommandTest extends TestCase
{
    #[Test]
    #[TestWith([ROOT . 'file_exists_in_root.sql', 'file_exists_in_root.sql'])]
    #[TestWith(['file_does_not_exist_in_root.sql', 'file_does_not_exist_in_root.sql'])]
    #[TestWith([ROOT . 'absolute_root_file.sql', ROOT . 'absolute_root_file.sql'])]
    #[TestWith([TMP . 'absolute_tmp_file.sql', TMP . 'absolute_tmp_file.sql'])]
    #[RunInSeparateProcess]
    public function testMakeAbsolutePath(string $expectedAbsolutePath, string $path): void
    {
        $Filesystem = Mockery::mock('overload:' . Filesystem::class);

        /**
         * `Filesystem::exists()` returns `true` only when the argument is `file_exists_in_root.sql`.
         *
         * So it will return `false` when the argument is `file_does_not_exist_in_root.sql`.
         */
        $Filesystem->shouldReceive('exists')->andReturn($path == 'file_exists_in_root.sql');

        $Command = new class extends Command { };
        $result = $Command->makeAbsolutePath($path);

        $this->assertSame($expectedAbsolutePath, $result);
    }

    #[Test]
    #[TestWith(['backup.sql', 'backup.sql'])]
    #[TestWith(['backup.sql', ROOT . 'backup.sql'])]
    #[TestWith(['backups/backup.sql', ROOT . 'backups/backup.sql'])]
    #[TestWith([TMP . 'backup.sql', TMP . 'backup.sql'])]
    #[TestWith(['/anotherDir/backup.sql', '/anotherDir/backup.sql'])]
    public function testMakeRelativePath(string $expectedRelativePath, string $path): void
    {
        $Command = new class extends Command { };

        $result = $Command->makeRelativePath($path);
        $this->assertSame($expectedRelativePath, $result);
    }
}
