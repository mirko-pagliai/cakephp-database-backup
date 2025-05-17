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

use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * BackupExportTest
 */
#[CoversClass(BackupExport::class)]
class BackupExportTest extends TestCase
{
    public static function replaceFilenamePatternsDataProvider(): array
    {
        return [
            ['my_file_' . date('YmdHis') . '.sql', 'my_file_{$DATETIME}.sql'],
            ['/^my_file_\d{10}\.sql$/', 'my_file_{$TIMESTAMP}.sql'],
        ];
    }

    #[Test]
    #[DataProvider('replaceFilenamePatternsDataProvider')]
    public function testReplaceFilenamePatterns(string $expectedFilename, string $filename): void
    {
        $BackupExport = new BackupExport();
        $result = $BackupExport->replaceFilenamePatterns(filename: $filename);

        if (str_starts_with($expectedFilename, '/') && str_ends_with($expectedFilename, '/')) {
            $this->assertMatchesRegularExpression($expectedFilename, $result);
        } else {
            $this->assertSame($expectedFilename, $result);
        }
    }
}
