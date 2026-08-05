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

use App\Executor\FakeExecutor;
use Cake\Core\Configure;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\Executor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use Mockery;
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
    /**
     * @link \DatabaseBackup\Executor\Executor::implementedEvents()
     */
    #[Test]
    public function testImplementedEvents(): void
    {
        $expected = [
            'Backup.afterExport' => 'afterExport',
            'Backup.afterImport' => 'afterImport',
            'Backup.beforeExport' => 'beforeExport',
            'Backup.beforeImport' => 'beforeImport',
        ];
        $result = new FakeExecutor()->implementedEvents();

        $this->assertSame($expected, $result);
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::findBinary()
     */
    #[Test]
    #[TestWith(['/usr/bin/mariadb', 'mariadb'])]
    #[TestWith(['/usr/bin/mysql', 'mysql'])]
    #[TestWith(['/usr/bin/gzip', Compression::Gzip])]
    #[RunInSeparateProcess]
    public function testFindBinary(string $expectedBinary, Compression|string $name): void
    {
        Mockery::spy('overload:Symfony\Component\Process\ExecutableFinder')
            ->shouldReceive('find')
            ->once()
            ->andReturnUsing(fn(string $name): string => "/usr/bin/$name");

        $result = new FakeExecutor()->findBinary($name);

        $this->assertSame($expectedBinary, $result);
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::findBinary()
     */
    #[Test]
    #[TestWith(['/customPath/mariadb', 'mariadb'])]
    #[TestWith(['/customPath/mysql', 'mysql'])]
    #[TestWith(['/customPath/gzip', Compression::Gzip])]
    public function testFindBinaryFromConfiguration(string $expectedBinary, Compression|string $binaryName): void
    {
        $binaryName = $binaryName instanceof Compression ? lcfirst($binaryName->name) : $binaryName;
        Configure::write(config: "DatabaseBackup.binaries.$binaryName", value: "/customPath/$binaryName");

        $result = new FakeExecutor()->findBinary($binaryName);
        Configure::delete("DatabaseBackup.binaries.$binaryName");

        $this->assertSame($expectedBinary, $result);
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::findBinary()
     */
    #[Test]
    public function testFindBinaryWithNoExistingBinary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Binary for `noExisting` not found. Set path manually: `Configure::write(\'DatabaseBackup.binaries.noExisting\', \'/path/to/noExisting\')`',
        );
        new FakeExecutor()->findBinary('noExisting');
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::findBinary()
     */
    #[Test]
    public function testFindBinaryWithCompressionNone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to search for binary for "none" Compression');
        new FakeExecutor()->findBinary(Compression::None);
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::getCommand()
     */
    #[Test]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump > "${:FILENAME}"', OperationType::Export, Compression::None])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump | "${:COMPRESSION_BINARY}" > "${:FILENAME}"', OperationType::Export, Compression::Gzip])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" .dump | "${:COMPRESSION_BINARY}" > "${:FILENAME}"', OperationType::Export, Compression::Bzip2])]
    #[TestWith(['"${:BINARY}" "${:DB_NAME}" < "${:FILENAME}"', OperationType::Import, Compression::None])]
    #[TestWith(['"${:COMPRESSION_BINARY}" -dc "${:FILENAME}" | "${:BINARY}" "${:DB_NAME}"', OperationType::Import, Compression::Gzip])]
    #[TestWith(['"${:COMPRESSION_BINARY}" -dc "${:FILENAME}" | "${:BINARY}" "${:DB_NAME}"', OperationType::Import, Compression::Bzip2])]
    public function testGetCommand(string $expectedCommand, OperationType $OperationType, Compression $Compression): void
    {
        $result = new FakeExecutor(OperationType: $OperationType)->getCommand(Compression: $Compression);

        $this->assertSame($expectedCommand, $result);
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::runProcess()
     */
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
            ->andReturnUsing(fn(string $name): string => "/usr/bin/$name");

        /**
         * With a valid compression, `ExecutableFinder::find()` expects the lowercase name of the compression (e.g.
         *  `gzip`) and returns and returns that name with the prefix `/usr/bin` (e.g. `/usr/bin/gzip`)
         */
        if ($Compression->isValid()) {
            $ExecutableFinder
                ->shouldReceive('find')
                ->withSomeOfArgs(lcfirst($Compression->name))
                ->andReturnUsing(fn(string $name): string => "/usr/bin/$name");
        }

        $Process = Mockery::mock('overload:' . Process::class);

        $Process->shouldReceive('isSuccessful')->andReturnTrue();

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
         * Instead, the value of `COMPRESSION_BINARY` is a variable based on the type of compression (in this case
         *  derived from `$filename`). It is `null` without compression.
         */
        $Process
            ->shouldReceive('run')
            ->withArgs(function ($env) use ($Compression, $filename, $OperationType): bool {
                $expectedEnv = [
                    'AUTH_FILE' => 'path/to/auth_file',
                    'BINARY' => '/usr/bin/' . $OperationType->value . '-binary',
                    'DB_HOST' => 'my_hostname',
                    'DB_NAME' => 'my_database',
                    'DB_PASSWORD' => 'my_password',
                    'DB_USERNAME' => 'my_username',
                    'FILENAME' => $filename,
                ];
                if ($Compression->isValid()) {
                    $expectedEnv['COMPRESSION_BINARY'] = '/usr/bin/' . lcfirst($Compression->name);
                }
                $this->assertEquals($expectedEnv, $env);

                return true;
            })
            ->once();

        $Executor = new class (OperationType: $OperationType) extends FakeExecutor {
            public string $authFile = 'path/to/auth_file';
        };

        $result = $Executor->runProcess($filename);

        $this->assertSame($Process, $result);
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::runProcess()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testRunProcessOnFailure(): void
    {
        Mockery::mock('overload:' . ExecutableFinder::class)
            ->shouldReceive('find')
            ->andReturn('/usr/bin/binary');

        $Process = Mockery::mock('overload:' . Process::class)->shouldIgnoreMissing();
        $Process->shouldReceive('fromShellCommandline')->andReturnSelf();
        $Process->shouldReceive('isSuccessful')->andReturnFalse();
        $Process->shouldReceive('getCommandLine')->andReturn('failureCommand');

        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage('The command "failureCommand" failed.');
        new FakeExecutor()->runProcess('backup.sql');
    }
}
