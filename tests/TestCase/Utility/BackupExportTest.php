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
use DatabaseBackup\Compression;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use Error;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
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

        $this->BackupExport = new BackupExport();
        $this->BackupExport->Connection = new FakeConnection();
    }

    #[Test]
    #[TestWith([Compression::None, Compression::None])]
    #[TestWith([Compression::Gzip, Compression::Gzip])]
    #[TestWith([Compression::Gzip, 'Gzip'])]
    #[TestWith([Compression::Gzip, 'gzip'])]
    #[TestWith([Compression::None, null])]
    #[TestWith([Compression::None, false])]
    public function testCompressionProperty(Compression $ExpectedCompression, mixed $Compression): void
    {
        $this->assertSame(Compression::None, $this->BackupExport->Compression);

        $this->BackupExport->Compression = $Compression;

        $this->assertSame($ExpectedCompression, $this->BackupExport->Compression);
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith(['invalidCompression'])]
    public function testCompressionPropertyWithInvalidValue(mixed $invalidCompression): void
    {
        $this->expectException(Error::class);
        $this->BackupExport->Compression = $invalidCompression;
    }

    #[Test]
    #[TestWith([TMP . 'backup.sql', Compression::None, TMP . 'backup.sql'])]
    #[TestWith([TMP . 'backup.sql.gz', Compression::Gzip, TMP . 'backup.sql.gz'])]
    #[TestWith([TMP . 'backup.sql.bz2', Compression::Bzip2, TMP . 'backup.sql.bz2'])]
    public function testFilenameProperty(string $expectedFilename, Compression $ExpectedCompression, string $filename): void
    {
        $this->BackupExport->filename = $filename;

        $this->assertSame($expectedFilename, $this->BackupExport->filename);
        $this->assertSame($ExpectedCompression, $this->BackupExport->Compression);
    }

    #[Test]
    #[TestWith([TMP . 'my_file_my_hostname.sql', TMP . 'my_file_{$HOSTNAME}.sql'])]
    #[TestWith([TMP . 'my_file_my_database.sql', TMP . 'my_file_{$DATABASE}.sql'])]
    public function testFilenamePropertyWithPatterns(string $expectedFilename, string $filename): void
    {
        $this->BackupExport->filename = $filename;

        $this->assertSame($expectedFilename, $this->BackupExport->filename);
    }

    /**
     * Like the previous test, but using patterns that require a regular expression to match
     */
    #[Test]
    #[TestWith(['/my_file_\d{14}\.sql$/', TMP . 'my_file_{$DATETIME}.sql'])]
    #[TestWith(['/my_file_\d{10}\.sql$/', TMP . 'my_file_{$TIMESTAMP}.sql'])]
    public function testFilenamePropertyWithPatternsMatchesRegularExpression(string $expectedFilename, string $filename): void
    {
        $this->BackupExport->filename = $filename;

        $this->assertMatchesRegularExpression($expectedFilename, $this->BackupExport->filename);
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
}
