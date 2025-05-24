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

    #[Test]
    #[RunInSeparateProcess]
    public function testFilenameProperty(): void
    {
        $filename = TMP . 'backup.sql';

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('exists')
            ->with($filename)
            ->once()
            ->andReturnTrue();

        $this->BackupImport->filename = $filename;

        $this->assertSame($filename, $this->BackupImport->filename);
    }

    #[Test]
    public function testFilenamePropertyNoReadableFile(): void
    {
        $filename = TMP . 'noExistingDir' . DS . 'backup.sql';

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File `' . $filename . '` does not exist');
        $this->BackupImport->filename = $filename;
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testCallMagicMethod(): void
    {
        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('exists')
            ->once()
            ->andReturnTrue();

        $result = $this->BackupImport->filename(TMP . 'backup.sql');
        $this->assertInstanceOf(BackupImport::class, $result);
        $this->assertSame(TMP . 'backup.sql', $this->BackupImport->filename);

        $result = $this->BackupImport->timeout(120);
        $this->assertInstanceOf(BackupImport::class, $result);
        $this->assertSame(120, $this->BackupImport->timeout);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testImport(): void
    {
        $filename = TMP . 'backup.sql';

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('exists')
            ->with($filename)
            ->once()
            ->andReturnTrue();

        /** @var \DatabaseBackup\Executor\Executor&\Mockery\MockInterface $Executor */
        $Executor = Mockery::mock(FakeExecutor::class)->makePartial();
        $Executor
            ->shouldReceive('runProcess')
            ->with($filename, 120)
            ->once()
            ->andReturn(new ReflectionClass(Process::class)->newInstanceWithoutConstructor());

        $this->BackupImport->Executor = $Executor;
        $this->BackupImport->Executor->getEventManager()->setEventList(new EventList());

        $result = $this->BackupImport
            ->filename($filename)
            ->timeout(120)
            ->import();

        $this->assertSame($filename, $result);
        $this->assertEventFired('Backup.beforeImport', $this->BackupImport->Executor->getEventManager());
        $this->assertEventFired('Backup.afterImport', $this->BackupImport->Executor->getEventManager());
    }

    /**
     * `import()` is stopped by the `Backup.beforeImport` event (implemented by the `Executor` class)
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

    #[Test]
    public function testImportWithFilenamePropertyNotSet(): void
    {
        $this->BackupImport->Executor = new FakeExecutor();

        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }
}
