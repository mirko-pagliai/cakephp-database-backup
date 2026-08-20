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
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupImport;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
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
    #[RunInSeparateProcess]
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
        $BackupImport->Executor->getEventManager()->setEventList(new EventList());

        $result = $BackupImport
            ->filename($filename)
            ->import();

        $this->assertSame($filename, $result);
        $this->assertEventFired('Backup.beforeImport', $BackupImport->Executor->getEventManager());
        $this->assertEventFired('Backup.afterImport', $BackupImport->Executor->getEventManager());
    }

    /**
     * @link \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testImportWithTimeoutFromConfiguration(): void
    {
        Configure::write('DatabaseBackup.processTimeout', 45);

        $filename = TMP . 'backup.sql';

        /** @var \DatabaseBackup\Executor\Executor&\Mockery\MockInterface $Executor */
        $Executor = Mockery::mock(FakeExecutor::class)->makePartial();
        $Executor
            ->shouldReceive('runProcess')
            ->with($filename, 45)
            ->once()
            ->andReturn(new ReflectionClass(Process::class)->newInstanceWithoutConstructor());

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
    }

    /**
     * `import()` is stopped by the `Backup.beforeImport` event (implemented by the `Executor` class).
     *
     * @link \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImportStoppedByBeforeImport(): void
    {
        $this->BackupImport->Executor = new class extends FakeExecutor
        {
            public function beforeImport(EventInterface $Event): void
            {
                $Event->stopPropagation();
            }
        };

        $result = $this->BackupImport->import();
        $this->assertFalse($result);
    }

    /**
     * @link \DatabaseBackup\Utility\BackupImport::import()
     */
    #[Test]
    public function testImportWithFilenamePropertyNotSet(): void
    {
        $this->BackupImport->Executor = new FakeExecutor();

        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }
}
