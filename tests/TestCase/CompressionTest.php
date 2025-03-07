<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase;

use Cake\TestSuite\TestCase;
use DatabaseBackup\Compression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ValueError;

/**
 * CompressionTest.
 */
#[CoversClass(Compression::class)]
class CompressionTest extends TestCase
{
    /**
     * @uses \DatabaseBackup\Compression::fromFilename()
     */
    #[Test]
    #[TestWith([Compression::None, 'filename.sql'])]
    #[TestWith([Compression::Gzip, 'filename.sql.gz'])]
    #[TestWith([Compression::Bzip2, 'filename.sql.bz2'])]
    public function testFromFilename(Compression $ExpectedCompression, string $filename): void
    {
        $this->assertSame($ExpectedCompression, Compression::fromFilename($filename));
    }

    /**
     * @uses \DatabaseBackup\Compression::fromFilename()
     */
    #[Test]
    public function testFromFilenameThrowsException(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('No valid `' . Compression::class . '` value was found for filename `filename.txt`');
        Compression::fromFilename('filename.txt');
    }

    /**
     * @uses \DatabaseBackup\Compression::tryFromFilename()
     */
    #[Test]
    #[TestWith([Compression::None, 'filename.sql'])]
    #[TestWith([Compression::Gzip, 'filename.sql.gz'])]
    #[TestWith([Compression::Bzip2, 'filename.sql.bz2'])]
    #[TestWith([null, 'filename.txt'])]
    public function testTryFromFilename(?Compression $ExpectedCompression, string $filename): void
    {
        $this->assertSame($ExpectedCompression, Compression::tryFromFilename($filename));
    }
}
