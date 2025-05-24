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
use DatabaseBackup\Compression;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use Error;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionClass;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use ValueError;

/**
 * BackupExportTest
 */
#[CoversClass(BackupExport::class)]
class BackupExportTest extends TestCase
{
    protected BackupExport $BackupExport;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupExport = new BackupExport(Connection: new FakeConnection());
    }

    #[Test]
    #[TestWith([Compression::None, Compression::None])]
    #[TestWith([Compression::Gzip, Compression::Gzip])]
    #[TestWith([Compression::Gzip, 'Gzip'])]
    #[TestWith([Compression::Gzip, 'gzip'])]
    #[TestWith([Compression::None, null])]
    public function testCompressionProperty(Compression $ExpectedCompression, mixed $Compression): void
    {
        //This is the expected default value
        $this->assertSame(Compression::None, $this->BackupExport->compression);

        $this->BackupExport->compression = $Compression;

        $this->assertSame($ExpectedCompression, $this->BackupExport->compression);
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith(['invalidCompression'])]
    public function testCompressionPropertyWithInvalidValue(mixed $invalidCompression): void
    {
        $this->expectException(Error::class);
        $this->BackupExport->compression = $invalidCompression;
    }

    #[Test]
    #[TestWith([TMP . 'backup.sql', Compression::None, TMP . 'backup.sql'])]
    #[TestWith([TMP . 'backup.sql.gz', Compression::Gzip, TMP . 'backup.sql.gz'])]
    #[TestWith([TMP . 'backup.sql.bz2', Compression::Bzip2, TMP . 'backup.sql.bz2'])]
    public function testFilenameProperty(string $expectedFilename, Compression $ExpectedCompression, string $filename): void
    {
        $this->BackupExport->filename = $filename;

        $this->assertSame($expectedFilename, $this->BackupExport->filename);

        //By setting the filename, the compression is also set
        $this->assertSame($ExpectedCompression, $this->BackupExport->compression);
    }

    #[Test]
    #[TestWith([TMP . 'my_file_my_hostname.sql', TMP . 'my_file_{$HOSTNAME}.sql'])]
    #[TestWith([TMP . 'my_file_my_database.sql', TMP . 'my_file_{$DATABASE}.sql'])]
    #[TestWith(['/my_file_\d{14}\.sql$/', TMP . 'my_file_{$DATETIME}.sql'])]
    #[TestWith(['/my_file_\d{10}\.sql$/', TMP . 'my_file_{$TIMESTAMP}.sql'])]
    public function testFilenamePropertyWithPatterns(string $expectedFilename, string $filename): void
    {
        $this->BackupExport->filename = $filename;

        if (str_starts_with($expectedFilename,'/') && str_ends_with($expectedFilename, '/')) {
            $this->assertMatchesRegularExpression($expectedFilename, $this->BackupExport->filename);
        } else {
            $this->assertSame($expectedFilename, $this->BackupExport->filename);
        }
    }

    #[Test]
    public function testFilenamePropertyWithNoWritableTarget(): void
    {
        $filename = '/noExistingDir/backup.sql';

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File or directory `' . dirname($filename) . '` is not writable');
        $this->BackupExport->filename = $filename;
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testFilenamePropertyWithFileAlreadyExists(): void
    {
        $filename = TMP . 'backup.sql';

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('exists')
            ->with($filename)
            ->andReturnTrue();

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File `' . $filename . '` already exists');
        $this->BackupExport->filename = $filename;
    }

    #[Test]
    public function testFilenamePropertyWithInvalidFilenameAndCompression(): void
    {
        $filename = TMP . 'backup.txt';

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('No valid `' . Compression::class . '` value was found for filename `' . $filename . '`');
        $this->BackupExport->filename = $filename;
    }

    #[Test]
    public function testCallMagicMethod(): void
    {
        $result = $this->BackupExport->filename(TMP . 'backup.sql');
        $this->assertInstanceOf(BackupExport::class, $result);
        $this->assertSame(TMP . 'backup.sql', $this->BackupExport->filename);

        $result = $this->BackupExport->compression(Compression::Gzip);
        $this->assertInstanceOf(BackupExport::class, $result);
        $this->assertSame(Compression::Gzip, $this->BackupExport->compression);

        $result = $this->BackupExport->timeout(120);
        $this->assertInstanceOf(BackupExport::class, $result);
        $this->assertSame(120, $this->BackupExport->timeout);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    public function testExport(): void
    {
        $filename = TMP . 'backup.sql';

        /** @var \DatabaseBackup\Executor\Executor&\Mockery\MockInterface $Executor */
        $Executor = Mockery::mock(FakeExecutor::class)->makePartial();
        $Executor
            ->shouldReceive('runProcess')
            ->with($filename, 120)
            ->once()
            ->andReturn(new ReflectionClass(Process::class)->newInstanceWithoutConstructor());

        $this->BackupExport->Executor = $Executor;
        $this->BackupExport->Executor->getEventManager()->setEventList(new EventList());

        $result = $this->BackupExport
            ->filename($filename)
            ->timeout(120)
            ->export();

        $this->assertSame($filename, $result);
        $this->assertEventFired('Backup.beforeExport', $this->BackupExport->Executor->getEventManager());
        $this->assertEventFired('Backup.afterExport', $this->BackupExport->Executor->getEventManager());
    }

    /**
     * `export()` is stopped by the `Backup.beforeExport` event (implemented by the `Executor` class)
     */
    #[Test]
    public function testExportStoppedByBeforeExport(): void
    {
        $this->BackupExport->Executor = new class extends FakeExecutor
        {
            public function beforeExport(EventInterface $Event): void
            {
                $Event->stopPropagation();
            }
        };

        $result = $this->BackupExport->export();
        $this->assertFalse($result);
    }
}
