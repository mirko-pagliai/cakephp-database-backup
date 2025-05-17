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

namespace DatabaseBackup\Test\TestCase;

use DatabaseBackup\Compression;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ValueError;

/**
 * CompressionTest
 */
#[CoversClass(Compression::class)]
class CompressionTest extends TestCase
{
    #[Test]
    public function testValidCases(): void
    {
        $compressions = Compression::validCases();

        $this->assertNotEmpty($compressions);
        $this->assertNotContains(Compression::None, $compressions);
        foreach ($compressions as $compression) {
            $this->assertTrue($compression->isValid());
        }
    }

    #[Test]
    #[TestWith([false, Compression::None])]
    #[TestWith([true, Compression::Gzip])]
    #[TestWith([true, Compression::Bzip2])]
    public function testIsValid(bool $expectedIsValid, Compression $Compression): void
    {
        $this->assertSame($expectedIsValid, $Compression->isValid());
    }

    #[Test]
    #[TestWith([Compression::None, 'filename.sql'])]
    #[TestWith([Compression::None, 'FILENAME.SQL'])]
    #[TestWith([Compression::Gzip, 'filename.sql.gz'])]
    #[TestWith([Compression::Gzip, 'FILENAME.SQL.GZ'])]
    #[TestWith([Compression::Bzip2, 'filename.sql.bz2'])]
    #[TestWith([Compression::Bzip2, 'FILENAME.SQL.BZ2'])]
    public function testFromFilename(Compression $ExpectedCompression, string $filename): void
    {
        $this->assertSame($ExpectedCompression, Compression::fromFilename($filename));
    }

    #[Test]
    public function testFromFilenameThrowsException(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('No valid `' . Compression::class . '` value was found for filename `filename.txt`');
        Compression::fromFilename('filename.txt');
    }

    #[Test]
    #[TestWith([Compression::None, 'filename.sql'])]
    #[TestWith([Compression::None, 'FILENAME.SQL'])]
    #[TestWith([Compression::Gzip, 'filename.sql.gz'])]
    #[TestWith([Compression::Gzip, 'FILENAME.SQL.GZ'])]
    #[TestWith([Compression::Bzip2, 'filename.sql.bz2'])]
    #[TestWith([Compression::Bzip2, 'FILENAME.SQL.BZ2'])]
    #[TestWith([null, 'filename.txt'])]
    #[TestWith([null, 'FILENAME.TXT'])]
    public function testTryFromFilename(?Compression $ExpectedCompression, string $filename): void
    {
        $this->assertSame($ExpectedCompression, Compression::tryFromFilename($filename));
    }
}
