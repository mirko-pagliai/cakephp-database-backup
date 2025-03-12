<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase\TestSuite;

use Cake\TestSuite\TestCase as CakeTestCase;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * TestCaseTest.
 *
 * This class extends `\Cake\TestSuite\TestCase` to avoid conflicts.
 */
#[CoversClass(TestCase::class)]
class TestCaseTest extends CakeTestCase
{
    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\TestSuite\TestCase::createBackup()
     */
    #[Test]
    #[TestWith(['backup.sql', true])]
    #[TestWith(['backup.sql', false])]
    #[TestWith(['backup.sql.gz', true])]
    #[TestWith(['backup.sql.gz', false])]
    #[TestWith(['backup.sql.bz2', true])]
    #[TestWith(['backup.sql.bz2', false])]
    public function testCreateBackup(string $filename, bool $realBackup): void
    {
        $result = $this->createPartialMock(TestCase::class, [])
            ->createBackup($filename, $realBackup);

        $this->assertFileExists($result);
        unlink($result);

        $this->assertSame(TMP . 'backups' . DS . $filename, $result);
    }

    /**
     * @uses \DatabaseBackup\TestSuite\TestCase::createSomeBackups()
     */
    #[Test]
    public function testCreateSomeBackups(): void
    {
        $timestamp = time();
        $expectedFiles = [
            'backup_test_' . ($timestamp - 180) . '.sql',
            'backup_test_' . ($timestamp - 120) . '.sql.gz',
            'backup_test_' . ($timestamp - 60) . '.sql.bz2',
        ];

        $TestCase = $this->getMockBuilder(TestCase::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createBackup'])
            ->getMock();

        $TestCase->expects($this->exactly(3))
            ->method('createBackup')
            ->willReturnArgument(0);

        $result = $TestCase->createSomeBackups(timestamp: $timestamp);

        array_map(callback: 'unlink', array: $result);

        $this->assertSame($expectedFiles, $result);
    }
}
