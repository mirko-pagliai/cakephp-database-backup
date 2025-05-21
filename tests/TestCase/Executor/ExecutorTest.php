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
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\Executor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * ExecutorTest.
 */
#[CoversClass(Executor::class)]
class ExecutorTest extends TestCase
{
    protected Executor $Executor;

    /**
     * @inheritDoc
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->Executor = new class (OperationType: OperationType::Export) extends Executor {
            public function getBinaryName(): string
            {
                return 'binary-name';
            }
        };
    }

    #[Test]
    public function testImplementedEvents(): void
    {
        $result = $this->Executor->implementedEvents();

        $this->assertContains('beforeExport', $result);
        $this->assertContains('afterExport', $result);
        $this->assertContains('beforeImport', $result);
        $this->assertContains('afterImport', $result);
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
}