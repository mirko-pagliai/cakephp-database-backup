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
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * BackupExportTest
 */
#[CoversClass(BackupExport::class)]
class BackupExportTest extends TestCase
{
    protected BackupExport $BackupExport;

    public function setUp(): void
    {
        parent::setUp();

        $this->BackupExport = new BackupExport();
        $this->BackupExport->Connection = new FakeConnection();
    }

    #[Test]
    #[TestWith(['my_file_my_hostname.sql', 'my_file_{$HOSTNAME}.sql'])]
    #[TestWith(['my_file_my_database.sql', 'my_file_{$DATABASE}.sql'])]
    public function testReplaceFilenamePatterns(string $expectedFilename, string $filename): void
    {
        $result = $this->BackupExport->replaceFilenamePatterns(filename: $filename);
        $this->assertSame($expectedFilename, $result);
    }

    /**
     * Like the previous test, but using patterns that require a regular expression to match
     */
    #[Test]
    #[TestWith(['/^my_file_\d{14}\.sql$/', 'my_file_{$DATETIME}.sql'])]
    #[TestWith(['/^my_file_\d{10}\.sql$/', 'my_file_{$TIMESTAMP}.sql'])]
    public function testReplaceFilenamePatternsMatchesRegularExpression(string $expectedFilename, string $filename): void
    {
        $result = $this->BackupExport->replaceFilenamePatterns(filename: $filename);
        $this->assertMatchesRegularExpression($expectedFilename, $result);
    }
}
