<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase\Console;

use DatabaseBackup\Console\Command;
use DatabaseBackup\TestSuite\TestCase;
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
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Console\Command::makeRelativeFilename()
     */
    #[Test]
    #[TestWith(['backup.sql', 'backup.sql'])]
    #[TestWith(['backup.sql', ROOT . 'backup.sql'])]
    #[TestWith(['backups/backup.sql', ROOT . 'backups/backup.sql'])]
    #[TestWith([TMP . 'backup.sql', TMP . 'backup.sql'])]
    #[TestWith(['/anotherDir/backup.sql', '/anotherDir/backup.sql'])]
    public function testMakeRelativeFilename(string $expectedRelativeFilename, string $filename): void
    {
        $result = $this->createPartialMock(Command::class, [])
            ->makeRelativeFilename($filename);
        $this->assertSame($expectedRelativeFilename, $result);
    }
}
