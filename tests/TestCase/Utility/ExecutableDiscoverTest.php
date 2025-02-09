<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase\Utility;

use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\ExecutableDiscover;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Process\ExecutableFinder;

/**
 * ExecutableDiscoverTest.
 */
#[CoversClass(ExecutableDiscover::class)]
class ExecutableDiscoverTest extends TestCase
{
    /**
     * @uses \DatabaseBackup\Utility\ExecutableDiscover::find()
     */
    #[Test]
    public function testFind(): void
    {
        $ExecutableDiscover = new ExecutableDiscover();
        $result = $ExecutableDiscover->find('gzip');
        $this->assertStringEndsWith('gzip', $result);
    }

    /**
     * Tests for `find()` method.
     *
     * In this case `mariadb` and `mariadb-dump` executables are available, so these will always be returned, even when
     *  trying to find `mysql` and `mysql-dump`.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\ExecutableDiscover::find()
     */
    #[Test]
    #[TestWith(['mysql', '/usr/bin/mariadb'])]
    #[TestWith(['mysql-dump', '/usr/bin/mariadb-dump'])]
    public function testFindMariaDbAndMariaDbDumpAvailable(string $name, string $expectedExecutable): void
    {
        $ExecutableFinder = $this->createPartialMock(ExecutableFinder::class, ['find']);
        $ExecutableFinder
            ->expects($this->any())
            ->method('find')
            ->willReturnCallback(fn (string $name): string => '/usr/bin/' . $name);

        $ExecutableDiscover = $this->createPartialMock(ExecutableDiscover::class, ['getExecutableFinder']);
        $ExecutableDiscover
            ->expects($this->once())
            ->method('getExecutableFinder')
            ->willReturn($ExecutableFinder);

        $result = $ExecutableDiscover->find($name);
        $this->assertSame($expectedExecutable, $result);
    }

    /**
     * Tests for `find()` method.
     *
     * In this case `mariadb` and `mariadb-dump` executables are NOT available, so the executables of `mysql` and
     *  `mysql-dump` will always be returned.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\ExecutableDiscover::find()
     */
    #[Test]
    #[TestWith(['mysql', '/usr/bin/mysql'])]
    #[TestWith(['mysql-dump', '/usr/bin/mysql-dump'])]
    public function testFindMariaDbAndMariaDbDumpNotAvailable(string $name, string $expectedExecutable): void
    {
        $ExecutableFinder = $this->createPartialMock(ExecutableFinder::class, ['find']);
        $ExecutableFinder
            ->expects($this->any())
            ->method('find')
            ->willReturnCallback(function (string $name, string $default = null): ?string {
                if (in_array($name, ['mariadb', 'mariadb-dump'])) {
                    return $default;
                }

                return '/usr/bin/' . $name;
            });

        $ExecutableDiscover = $this->createPartialMock(ExecutableDiscover::class, ['getExecutableFinder']);
        $ExecutableDiscover
            ->expects($this->once())
            ->method('getExecutableFinder')
            ->willReturn($ExecutableFinder);

        $result = $ExecutableDiscover->find($name);
        $this->assertSame($expectedExecutable, $result);
    }
}
