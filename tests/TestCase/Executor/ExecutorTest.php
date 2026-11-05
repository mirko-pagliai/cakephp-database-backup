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
use App\Executor\FakeExecutor;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\Executor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
            'Backup.beforeExport' => ['callable' => 'beforeExport', 'priority' => 1000],
            'Backup.beforeImport' => ['callable' => 'beforeImport', 'priority' => 1000],
        ];
        $result = new FakeExecutor()->implementedEvents();

        $this->assertSame($expected, $result);
    }

    /**
     * The `before` events run after any other listener, so that a listener can stop the operation before the
     * `Executor` acts.
     *
     * @link \DatabaseBackup\Executor\Executor::implementedEvents()
     */
    #[Test]
    #[TestWith(['Backup.beforeExport'])]
    #[TestWith(['Backup.beforeImport'])]
    public function testBeforeEventsRunAfterOtherListeners(string $eventName): void
    {
        $Executor = new class extends FakeExecutor {
            public bool $beforeCalled = false;

            public function beforeExport(EventInterface $Event): void
            {
                $this->beforeCalled = true;
            }

            public function beforeImport(EventInterface $Event): void
            {
                $this->beforeCalled = true;
            }
        };

        // This listener is attached after the `Executor`, with the default priority
        $Executor->getEventManager()->on($eventName, static function (EventInterface $Event): void {
            $Event->stopPropagation();
        });

        $Executor->dispatchEvent($eventName);

        $this->assertFalse($Executor->beforeCalled);
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::findBinary()
     */
    #[Test]
    public function testFindBinary(): void
    {
        $oldPath = (string)getenv('PATH');

        // The `PATH` is restricted to the directory of the PHP binary, so the only binary that can be found is PHP
        putenv('PATH=' . dirname(PHP_BINARY));

        try {
            $result = new FakeExecutor()->findBinary(basename(PHP_BINARY));
        } finally {
            putenv("PATH={$oldPath}");
        }

        $this->assertSame(PHP_BINARY, $result);
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
        Configure::write(config: "DatabaseBackup.binaries.{$binaryName}", value: "/customPath/{$binaryName}");

        $result = new FakeExecutor()->findBinary($binaryName);
        Configure::delete("DatabaseBackup.binaries.{$binaryName}");

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
        $result = new FakeExecutor(OperationType: $OperationType)
            ->getCommand(Compression: $Compression);

        $this->assertSame($expectedCommand, $result);
    }

    /**
     * Test for the `runProcess()` method.
     *
     * The process is really executed and prints its own environment, which is what the `Executor` has passed to it.
     *
     * @link \DatabaseBackup\Executor\Executor::runProcess()
     */
    #[Test]
    #[TestWith(['filename.sql', OperationType::Export])]
    #[TestWith(['filename.sql.gz', OperationType::Export])]
    #[TestWith(['filename.sql.bz2', OperationType::Export])]
    #[TestWith(['filename.sql', OperationType::Import])]
    #[TestWith(['filename.sql.gz', OperationType::Import])]
    #[TestWith(['filename.sql.bz2', OperationType::Import])]
    public function testRunProcess(string $filename, OperationType $OperationType): void
    {
        $Compression = Compression::fromFilename($filename);

        $Executor = new class (OperationType: $OperationType) extends FakeExecutor {
            public string $authFile = 'path/to/auth_file';

            public function getCommand(Compression $Compression): string
            {
                return '"${:BINARY}" -r "echo json_encode(getenv());"';
            }

            // The binary of the operation is PHP, so that the process can be really executed
            public function findBinary(Compression|string $binaryName): string
            {
                return $binaryName instanceof Compression ? '/usr/bin/' . lcfirst($binaryName->name) : PHP_BINARY;
            }
        };

        $Process = $Executor->runProcess($filename);

        $this->assertSame(60.0, $Process->getTimeout());

        $expectedEnv = [
            'AUTH_FILE' => 'path/to/auth_file',
            'BINARY' => PHP_BINARY,
            'DB_HOST' => 'my_hostname',
            'DB_NAME' => 'my_database',
            'DB_PASSWORD' => 'my_password',
            'DB_USERNAME' => 'my_username',
            'FILENAME' => $filename,
        ];

        // The value of `COMPRESSION_BINARY` depends on the compression (derived from `$filename`)
        if ($Compression->isValid()) {
            $expectedEnv['COMPRESSION_BINARY'] = '/usr/bin/' . lcfirst($Compression->name);
        }

        /** @var array<string, string> $env */
        $env = json_decode($Process->getOutput(), true);
        foreach (array_keys($expectedEnv + ['COMPRESSION_BINARY' => null]) as $key) {
            // A variable that the `Executor` does not set can still be inherited from the environment
            $this->assertSame($expectedEnv[$key] ?? (getenv($key) ?: null), $env[$key] ?? null);
        }
    }

    /**
     * Tests that `host`, `password` and `username` of the connection are not passed to the process if they are `null`.
     *
     * @link \DatabaseBackup\Executor\Executor::runProcess()
     */
    #[Test]
    #[TestWith(['host', 'DB_HOST'])]
    #[TestWith(['password', 'DB_PASSWORD'])]
    #[TestWith(['username', 'DB_USERNAME'])]
    public function testRunProcessWithNullConnectionConfig(string $configKey, string $envKey): void
    {
        $Executor = new class (new FakeConnection([$configKey => null]), OperationType::Export) extends Executor {
            public function getBinaryName(): string
            {
                return 'export-binary';
            }

            public function getCommand(Compression $Compression): string
            {
                return '"${:BINARY}" -r "echo json_encode(getenv());"';
            }

            public function findBinary(Compression|string $binaryName): string
            {
                return PHP_BINARY;
            }
        };

        $Process = $Executor->runProcess('filename.sql');

        $expectedEnv = [
            'BINARY' => PHP_BINARY,
            'DB_HOST' => 'my_hostname',
            'DB_NAME' => 'my_database',
            'DB_PASSWORD' => 'my_password',
            'DB_USERNAME' => 'my_username',
            'FILENAME' => 'filename.sql',
        ];
        unset($expectedEnv[$envKey]);

        /** @var array<string, string> $env */
        $env = json_decode($Process->getOutput(), true);
        foreach (array_keys($expectedEnv + [$envKey => null]) as $key) {
            // A variable that the `Executor` does not set can still be inherited from the environment
            $this->assertSame($expectedEnv[$key] ?? (getenv($key) ?: null), $env[$key] ?? null);
        }
    }

    /**
     * @link \DatabaseBackup\Executor\Executor::runProcess()
     */
    #[Test]
    public function testRunProcessOnFailure(): void
    {
        $Executor = new class extends FakeExecutor {
            public function getCommand(Compression $Compression): string
            {
                return 'exit 1';
            }

            public function findBinary(Compression|string $binaryName): string
            {
                return '/usr/bin/binary';
            }
        };

        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage('The command "exit 1" failed.');
        $Executor->runProcess('backup.sql');
    }
}
