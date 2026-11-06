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

namespace DatabaseBackup\Test\TestCase\Utility;

use App\Database\FakeConnection;
use App\Executor\FakeExecutor;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventList;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupImport;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionClass;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * BackupImportTest.
 */
#[CoversClass(BackupImport::class)]
class BackupImportTest extends TestCase
{
    protected BackupImport $BackupImport;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupImport = new BackupImport(Connection: new FakeConnection());
    }

    /**
     * @link \DatabaseBackup\Utility\BackupImport::$filename
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testFilenameProperty(): void
    {
        $filename = TMP . 'backup.sql';

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('exists')
            ->andReturn(true);

        $this->BackupImport->filename = $filename;

        $this->assertSame($filename, $this->BackupImport->filename);
    }

    /**
     * @link \DatabaseBackup\Utility\BackupImport::$filename
     */
    #[Test]
    public function testFilenamePropertyNoReadableFile(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File `/noExistingDir/backup.sql` does not exist');
        $this->BackupImport->filename = '/noExistingDir/backup.sql';
    }

    /**
     * @link \DatabaseBackup\Utility\BackupImport::__call()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testCallMagicMethod(): void
    {
        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('exists')
            ->andReturn(true);

        $result = $this->BackupImport->filename(TMP . 'backup.sql');
        $this->assertInstanceOf(BackupImport::class, $result);
        $this->assertSame(TMP . 'backup.sql', $this->BackupImport->filename);

        $result = $this->BackupImport->timeout(120);
        $this->assertInstanceOf(BackupImport::class, $result);
        $this->assertSame(120, $this->BackupImport->timeout);
    }

    /**
     * @link \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImport(): void
    {
        $filename = TMP . 'backup.sql';

        $Executor = new class extends FakeExecutor {
            public function runProcess(string $filename, int $timeout = 60): Process
            {
                return new ReflectionClass(Process::class)->newInstanceWithoutConstructor();
            }
        };

        $BackupImport = new class (Connection: new FakeConnection()) extends BackupImport {
            public string $filename {
                set(string $filename) {

                    $this->filename = $filename;
                }
            }
        };

        $BackupImport->Executor = $Executor;

        /** @var \Cake\Event\EventManager $EventManager */
        $EventManager = $BackupImport->Executor->getEventManager();
        $EventManager->setEventList(new EventList());

        $result = $BackupImport
            ->filename($filename)
            ->import();

        $this->assertSame($filename, $result);
        $this->assertEventFired('Backup.beforeImport', $EventManager);
        $this->assertEventFired('Backup.afterImport', $EventManager);
    }

    /**
     * @link \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImportWithTimeoutFromConfiguration(): void
    {
        Configure::write('DatabaseBackup.processTimeout', 45);

        $filename = TMP . 'backup.sql';

        $Executor = new class extends FakeExecutor {
            public string $filename = '';

            public int $timeout = 0;

            public function runProcess(string $filename, int $timeout = 60): Process
            {
                $this->filename = $filename;
                $this->timeout = $timeout;

                return new ReflectionClass(Process::class)->newInstanceWithoutConstructor();
            }
        };

        $BackupImport = new class (Connection: new FakeConnection()) extends BackupImport {
            public string $filename {
                set(string $filename) {
                    $this->filename = $filename;
                }
            }
        };

        $BackupImport->Executor = $Executor;

        $BackupImport
            ->filename($filename)
            ->import();

        $this->assertSame($filename, $Executor->filename);
        $this->assertSame(45, $Executor->timeout);
    }

    /**
     * `import()` is stopped by the `Backup.beforeImport` event (implemented by the `Executor` class).
     *
     * @link \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImportStoppedByBeforeImport(): void
    {
        $BackupImport = new class (Connection: new FakeConnection()) extends BackupImport {
            public string $filename {
                set(string $filename) {
                    $this->filename = $filename;
                }
            }
        };

        $BackupImport->filename = TMP . 'backup.sql';
        $BackupImport->Executor = new class extends FakeExecutor
        {
            public function beforeImport(EventInterface $Event): void
            {
                $Event->stopPropagation();
            }
        };

        $result = $BackupImport->import();
        $this->assertFalse($result);
    }

    /**
     * If the filename has not been set, `import()` fails before the `Backup.beforeImport` event, so with SQLite the
     * tables are not dropped.
     *
     * @link \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImportWithFilenamePropertyNotSet(): void
    {
        $this->BackupImport->Executor = new class (new FakeConnection(), OperationType::Import) extends SqliteExecutor {
            public function dropAllTables(): array
            {
                Assert::fail('The tables must not be dropped');
            }
        };

        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }

    /**
     * If the binary for the compression cannot be found, `import()` fails before the `Backup.beforeImport` event, so
     * with SQLite the tables are not dropped.
     *
     * @link \DatabaseBackup\Utility\BackupImport::import()
     * @todo Temporary test for the 3.0.x patch (issue #238), to be reviewed or removed when properly fixed in 3.1.x
     */
    #[Test]
    #[TestWith(['gzip', 'backup.sql.gz'])]
    #[TestWith(['bzip2', 'backup.sql.bz2'])]
    public function testImportWithCompressionBinaryNotFound(string $binaryName, string $filename): void
    {
        Configure::write("DatabaseBackup.binaries.{$binaryName}", '');

        $BackupImport = new class (Connection: new FakeConnection()) extends BackupImport {
            public string $filename {
                set(string $filename) {
                    $this->filename = $filename;
                }
            }
        };

        $BackupImport->filename = TMP . $filename;
        $BackupImport->Executor = new class (new FakeConnection(), OperationType::Import) extends SqliteExecutor {
            public function dropAllTables(): array
            {
                Assert::fail('The tables must not be dropped');
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Binary for `{$binaryName}` not found");
        $BackupImport->import();
    }
}
