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

namespace DatabaseBackup\Test\TestCase\Executor;

use Cake\Core\Configure;
use Cake\Database\Connection;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use Generator;
use InvalidArgumentException;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * AbstractExecutorTest.
 */
#[CoversClass(AbstractExecutor::class)]
class AbstractExecutorTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Executor\AbstractExecutor
     */
    protected AbstractExecutor $Executor;

    /**
     * @inheritDoc
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->Executor = new class (
            Connection: new Connection(['driver' => 'Sqlite']),
            OperationType: OperationType::Export
        ) extends AbstractExecutor {
            public function getBinaryName(): string
            {
                return $this->OperationType->value . '-binary';
            }
        };
    }

    #[Test]
    public function testImplementedEvents(): void
    {
        $this->assertNotEmpty($this->Executor->implementedEvents());
    }

    #[Test]
    #[TestWith(['/usr/bin/mariadb', 'mariadb'])]
    #[TestWith(['/usr/bin/mariadb', 'mariadb', 'mysql'])]
    #[TestWith(['/usr/bin/mariadb', 'mariadb', 'noExisting'])]
    #[TestWith(['/usr/bin/mariadb', 'noExisting', 'mariadb'])]
    #[TestWith(['/usr/bin/mysql', 'mysql'])]
    #[TestWith(['/usr/bin/gzip', Compression::Gzip])]
    #[RunInSeparateProcess]
    public function testFindBinary(string $expectedBinary, string|Compression ...$name): void
    {
        /** @var \Symfony\Component\Process\ExecutableFinder&\Mockery\MockInterface $ExecutableFinder */
        $ExecutableFinder = Mockery::spy('overload:Symfony\Component\Process\ExecutableFinder');
        $ExecutableFinder
            ->shouldReceive('find')
            ->between(1, 2)
            ->andReturnUsing(fn (string $name): ?string => match ($name) {
                'noExisting' => null,
                default => '/usr/bin/' . $name,
            });

        $binary = $this->Executor->findBinary(...$name);
        $this->assertSame($expectedBinary, $binary);
    }

    #[Test]
    #[TestWith(['/customPath/mariadb', 'mariadb'])]
    #[TestWith(['/customPath/mariadb', 'mariadb', 'mysql'])]
    #[TestWith(['/customPath/mariadb', 'mariadb', 'noExisting'])]
    #[TestWith(['/customPath/mariadb', 'noExisting', 'mariadb'])]
    #[TestWith(['/customPath/mysql', 'mysql'])]
    #[TestWith(['/customPath/gzip', Compression::Gzip])]
    public function testFindBinaryFromConfiguration(string $expectedBinary, string|Compression ...$name): void
    {
        Configure::write('DatabaseBackup.binaries.mariadb', '/customPath/mariadb');
        Configure::write('DatabaseBackup.binaries.mysql', '/customPath/mysql');
        Configure::write('DatabaseBackup.binaries.gzip', '/customPath/gzip');

        $binary = $this->Executor->findBinary(...$name);
        $this->assertSame($expectedBinary, $binary);
    }

    #[Test]
    public function testFindBinaryWithNoExistingBinary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Binary for `noExisting` could not be found. You have to set its path manually on your bootstrap with: `%s`',
            'Configure::write(\'DatabaseBackup.binaries.noExisting\', \'/your/full/path/to/noExisting\')'
        ));
        $this->Executor->findBinary('noExisting');
    }

    #[Test]
    #[TestWith([Compression::None])]
    #[TestWith([Compression::None, 'gzip'])]
    #[TestWith(['gzip', Compression::None])]
    public function testFindBinaryWithCompressionNone(string|Compression ...$name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to search for binary for "none" Compression');
        $this->Executor->findBinary(...$name);
    }

    #[Test]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump > "${:FILENAME}"', OperationType::Export, Compression::None])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump | "${:COMPRESSION_BINARY}" > "${:FILENAME}"', OperationType::Export, Compression::Gzip])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump | "${:COMPRESSION_BINARY}" > "${:FILENAME}"', OperationType::Export, Compression::Bzip2])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" < "${:FILENAME}"', OperationType::Import, Compression::None])]
    #[TestWith(['"${:COMPRESSION_BINARY}" -dc "${:FILENAME}" | "${:BINARY}" "${:DB_NAME}"', OperationType::Import, Compression::Gzip])]
    #[TestWith(['"${:COMPRESSION_BINARY}" -dc "${:FILENAME}" | "${:BINARY}" "${:DB_NAME}"', OperationType::Import, Compression::Bzip2])]
    public function testGetCommand(string $expectedCommand, OperationType $OperationType, Compression $Compression): void
    {
        $this->Executor->OperationType = $OperationType;

        $result = $this->Executor->getCommand(Compression: $Compression);
        $this->assertSame($expectedCommand, $result);
    }

    public static function providerTestRunProcess(): Generator
    {
        yield [
            ['COMPRESSION_BINARY' => null],
            '"${:BINARY}" "${:DB_NAME}" .dump > "${:FILENAME}"',
            'filename.sql',
            OperationType::Export,
        ];

        yield [
            ['COMPRESSION_BINARY' => null],
            '"${:BINARY}" "${:DB_NAME}" < "${:FILENAME}"',
            'filename.sql',
            OperationType::Import,
        ];

        yield [
            ['COMPRESSION_BINARY' => '/usr/bin/gzip'],
            '"${:BINARY}" "${:DB_NAME}" .dump | "${:COMPRESSION_BINARY}" > "${:FILENAME}"',
            'filename.sql.gz',
            OperationType::Export,
        ];

        yield [
            ['COMPRESSION_BINARY' => '/usr/bin/gzip'],
            '"${:COMPRESSION_BINARY}" -dc "${:FILENAME}" | "${:BINARY}" "${:DB_NAME}"',
            'filename.sql.gz',
            OperationType::Import,
        ];
    }

    /**
     * @param array<string, string> $expectedEnvVars
     * @param string $expectedCommand
     * @param string $filename
     * @param \DatabaseBackup\OperationType $OperationType
     * @return void
     */
    #[Test]
    #[DataProvider('providerTestRunProcess')]
    #[RunInSeparateProcess]
    public function testRunProcess(array $expectedEnvVars, string $expectedCommand, string $filename, OperationType $OperationType): void
    {
        Mockery::mock('overload:Symfony\Component\Process\ExecutableFinder')
            ->shouldReceive('find')
            ->atLeast()
            ->andReturnUsing(fn (string $name): string => '/usr/bin/' . $name)
            ->once();

        $Process = Mockery::mock('alias:Symfony\Component\Process\Process');

        $Process
            ->shouldReceive('fromShellCommandline')
            ->withSomeOfArgs($expectedCommand)
            ->andReturnSelf()
            ->once();

        $Process
            ->shouldReceive('run')
            ->withArgs(
                function (?callable $callback = null, array $env = []) use ($expectedEnvVars, $filename, $OperationType): bool {
                    $this->assertSame([], array_diff(
                        $env,
                        $expectedEnvVars + [
                            'AUTH_FILE' => '',
                            'BINARY' => '/usr/bin/' . $OperationType->value . '-binary',
                            'DB_HOST' => 'my-host',
                            'DB_NAME' => 'my-database',
                            'DB_PASSWORD' => 'my-password',
                            'DB_USER' => 'my-username',
                            'FILENAME' => $filename,
                        ],
                    ));

                    return true;
                }
            )
            ->once();

        $this->Executor->OperationType = $OperationType;

        $result = $this->Executor->runProcess(filename: $filename);
        $this->assertSame($Process, $result);
    }

    #[Test]
    #[TestWith(['my-database', 'database'])]
    #[TestWith([null, 'noExisting'])]
    public function testGetConfig(?string $expectedValue, string $configName): void
    {
        /** @var \Cake\Database\Connection&\Mockery\MockInterface $Connection */
        $Connection = Mockery::mock(Connection::class)
            ->shouldReceive('config')
            ->andReturn(['database' => 'my-database'])
            ->getMock();

        $this->Executor->Connection = $Connection;

        $result = $this->Executor->getConfig($configName);
        $this->assertSame($expectedValue, $result);
    }
}
