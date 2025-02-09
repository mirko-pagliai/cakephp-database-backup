<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase\Utility;

use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\ExecutableDiscover;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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
        $BinaryDiscover = new ExecutableDiscover();
        $result = $BinaryDiscover->find('gzip');
        $this->assertStringEndsWith('gzip', $result);

        $result = $BinaryDiscover->find('mysqldump');
        $this->assertStringEndsWith('mariadb-dump', $result);
    }

    /**
     * Tests for `find()` method, with find `mariadb` and `mariadb-dump` executables unavailable (so `mysql` and
     *  `mysql-dump` will be returned instead).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\ExecutableDiscover::find()
     */
    #[Test]
    public function testFindMariaDbAndMariaDbDumpNotAvailable(): void
    {
        /**
         * This `ExecutableFinder` mock cannot find `mariadb` and `mariadb-dump` executables.
         * It will always return `mysql` and `mysql-dump` executables instead.
         */
        $ExecutableFinder = $this->createPartialMock(ExecutableFinder::class, ['find']);
        $ExecutableFinder
            ->expects($this->any())
            ->method('find')
            ->willReturnCallback(function (string $name): ?string {
                if (in_array($name, ['mariadb', 'mariadb-dump'])) {
                    return null;
                }

                $OriginalExecutableFinder = new ExecutableFinder();

                return $OriginalExecutableFinder->find($name);
            });

        $BinaryDiscover = $this->createPartialMock(ExecutableDiscover::class, ['getExecutableFinder']);
        $BinaryDiscover
            ->expects($this->exactly(2))
            ->method('getExecutableFinder')
            ->willReturn($ExecutableFinder);

        $result = $BinaryDiscover->find('mysql');
        $this->assertStringEndsWith('mysql', $result);

        $result = $BinaryDiscover->find('mysqldump');
        $this->assertStringEndsWith('mysqldump', $result);
    }
}
