<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase\TestSuite;

use Cake\TestSuite\TestCase as CakeTestCase;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * TestCaseTest.
 *
 * This class extends `\Cake\TestSuite\TestCase` to avoid conflicts.
 */
#[CoversClass(TestCase::class)]
class TestCaseTest extends CakeTestCase
{
    /**
     * @return array<array{string}>
     */
    public static function providerTestCreateBackup(): array
    {
        return [
            ['backup.sql'],
            ['backup.sql.gz'],
            ['backup.sql.bz2'],
        ];
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[DataProvider('providerTestCreateBackup')]
    public function testCreateBackup(string $filename): void
    {
        $BackupExport = $this->createMock(BackupExport::class);

        $BackupExport
            ->expects($this->once())
            ->method('filename')
            ->with($filename)
            ->willReturnSelf();

        $BackupExport
            ->expects($this->once())
            ->method('export')
            ->willReturn($filename);

        $TestCase = $this->createPartialMock(TestCase::class, ['getBackupExport']);

        $TestCase
            ->expects($this->once())
            ->method('getBackupExport')
            ->willReturn($BackupExport);

        $result = $TestCase->createBackup(filename: $filename);

        $this->assertSame($filename, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[DataProvider('providerTestCreateBackup')]
    public function testCreateBackupAsFakeBackup(string $filename): void
    {
        $TestCase = $this->createPartialMock(TestCase::class, ['getBackupExport']);

        $TestCase
            ->expects($this->never())
            ->method('getBackupExport');

        $result = $TestCase->createBackup(filename: $filename, fakeBackup: true);

        $this->assertFileExists($result);
        unlink($result);
        $this->assertSame(TMP . 'backups' . DS . $filename, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testCreateSomeBackups(): void
    {
        $timestamp = time();
        $expectedFiles = [
            'backup_test_' . ($timestamp - 60) . '.sql',
            'backup_test_' . ($timestamp - 120) . '.sql.gz',
            'backup_test_' . ($timestamp - 180) . '.sql.bz2',
        ];

        $TestCase = $this->createPartialMock(TestCase::class, ['createBackup']);

        $TestCase->expects($this->exactly(3))
            ->method('createBackup')
            ->with(...self::withConsecutive([$expectedFiles[0]], [$expectedFiles[1]], [$expectedFiles[2]]))
            ->willReturnArgument(0);

        $resultFiles = $TestCase->createSomeBackups(timestamp: $timestamp);

        array_map(callback: 'unlink', array: $resultFiles);

        $this->assertSame($expectedFiles, $resultFiles);
    }
}
