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

use App\Database\FakeConnection;
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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

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
            public string $authFile = 'path/to/auth_file';

            public function getBinaryName(): string
            {
                return $this->OperationType->value . '-binary';
            }
        };
        $this->Executor->Connection = new FakeConnection();
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
    #[TestWith(['/usr/bin/mysql', 'mysql'])]
    #[TestWith(['/usr/bin/gzip', Compression::Gzip])]
    #[RunInSeparateProcess]
    public function testFindBinary(string $expectedBinary, string|Compression $name): void
    {
        Mockery::spy('overload:Symfony\Component\Process\ExecutableFinder')
            ->shouldReceive('find')
            ->once()
            ->andReturnUsing(fn (string $name): string => '/usr/bin/' . $name);

        $binary = $this->Executor->findBinary($name);
        $this->assertSame($expectedBinary, $binary);
    }

    #[Test]
    #[TestWith(['/customPath/mariadb', 'mariadb'])]
    #[TestWith(['/customPath/mysql', 'mysql'])]
    #[TestWith(['/customPath/gzip', Compression::Gzip])]
    public function testFindBinaryFromConfiguration(string $expectedBinary, string|Compression $binaryName): void
    {
        $binaryName = $binaryName instanceof Compression ? lcfirst($binaryName->name) : $binaryName;
        Configure::write(config: 'DatabaseBackup.binaries.' . $binaryName, value: '/customPath/' . $binaryName);

        $binary = $this->Executor->findBinary($binaryName);
        $this->assertSame($expectedBinary, $binary);

        Configure::delete('DatabaseBackup.binaries.' . $binaryName);
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
    public function testFindBinaryWithCompressionNone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to search for binary for "none" Compression');
        $this->Executor->findBinary(Compression::None);
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

    #[Test]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump > "${:FILENAME}"', 'filename.sql', OperationType::Export])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump | "${:COMPRESSION_BINARY}" > "${:FILENAME}"', 'filename.sql.gz', OperationType::Export])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump | "${:COMPRESSION_BINARY}" > "${:FILENAME}"', 'filename.sql.bz2', OperationType::Export])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" < "${:FILENAME}"', 'filename.sql', OperationType::Import])]
    #[TestWith(['"${:COMPRESSION_BINARY}" -dc "${:FILENAME}" | "${:BINARY}" "${:DB_NAME}"', 'filename.sql.gz', OperationType::Import])]
    #[TestWith(['"${:COMPRESSION_BINARY}" -dc "${:FILENAME}" | "${:BINARY}" "${:DB_NAME}"', 'filename.sql.bz2', OperationType::Import])]
    #[RunInSeparateProcess]
    public function testRunProcess(string $expectedCommand, string $filename, OperationType $OperationType): void
    {
        $Compression = Compression::fromFilename($filename);

        $ExecutableFinder = Mockery::mock('overload:' . ExecutableFinder::class);

        /**
         * `ExecutableFinder::find()` expects `export-binary`/`import-binary` argument and returns
         *  `/usr/bin/export-binary`/`/usr/bin/import-binary`
         */
        $ExecutableFinder
            ->shouldReceive('find')
            ->withSomeOfArgs($OperationType->value . '-binary')
            ->andReturnUsing(fn (string $name): string => '/usr/bin/' . $name);

        /**
         * With a valid compression, `ExecutableFinder::find()` expects the lowercase name of the compression (e.g.
         *  `gzip`) and returns and returns that name with the prefix `/usr/bin` (e.g. `/usr/bin/gzip`)
         */
        if ($Compression->isValid()) {
            $ExecutableFinder
                ->shouldReceive('find')
                ->withSomeOfArgs(lcfirst($Compression->name))
                ->andReturnUsing(fn (string $name): string => '/usr/bin/' . $name);
        }

        $Process = Mockery::mock('overload:' . Process::class);

        /**
         * `Process::fromShellCommandline()` expects `$expectedCommand` argument.
         */
        $Process
            ->shouldReceive('fromShellCommandline')
            ->withArgs(function ($command, $timeout) use ($expectedCommand): bool {
                $this->assertSame($expectedCommand, $command);
                $this->assertSame(60, $timeout);

                return true;
            })
            ->once()
            ->andReturnSelf();

        /**
         * `Process::run()` expects an argument built from the previous context variables.
         *
         * In particular, the value of `BINARY` is variable based on the type of operation.
         * Instead, the value of `COMPRESSION_BINARY` is variable based on the type of compression (in this case
         *  derived from `$filename`). It is `null` without compression.
         */
        $Process
            ->shouldReceive('run')
            ->withArgs(function ($env) use ($Compression, $filename, $OperationType): bool {
                $expectedEnv = [
                    'AUTH_FILE' => 'path/to/auth_file',
                    'BINARY' => '/usr/bin/' . $OperationType->value . '-binary',
                    'COMPRESSION_BINARY' => $Compression->isValid() ? '/usr/bin/' . lcfirst($Compression->name) : null,
                    'DB_HOST' => 'my_hostname',
                    'DB_NAME' => 'my_database',
                    'DB_PASSWORD' => 'my_password',
                    'DB_USER' => 'my_username',
                    'FILENAME' => $filename,
                ];
                $this->assertSame($expectedEnv, $env);

                return true;
            })
            ->once();

        $this->Executor->OperationType = $OperationType;

        $result = $this->Executor->runProcess($filename);

        $this->assertSame($Process, $result);
    }

    #[Test]
    public function testRunProcessOnFailure(): void
    {
        Mockery::mock('overload:' . ExecutableFinder::class)
            ->shouldReceive('find')
            ->andReturn('/usr/bin/binary');

        $Process = Mockery::mock('overload:' . Process::class)->shouldIgnoreMissing();
        $Process->shouldReceive('fromShellCommandline')->andReturnSelf();
        $Process->shouldReceive('isSuccessful')->andReturn(false);
        $Process->shouldReceive('getCommandLine')->andReturn('failureCommand');

        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage('The command "failureCommand" failed.');
        $this->Executor->runProcess('backup.sql');
    }
}
