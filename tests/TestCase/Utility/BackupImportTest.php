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
use DatabaseBackup\Utility\BackupImport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Filesystem\Exception\IOException;

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

        $this->BackupImport = new BackupImport();
    }

    #[Test]
    public function testFilename(): void
    {
        $filename = TMP . 'backup.sql';
        file_put_contents($filename, '');

        $this->BackupImport->filename = $filename;
        unlink($filename);

        $result = $this->BackupImport->filename;
        $this->assertSame($filename, $result);
    }

    #[Test]
    public function testFilenamePropertyNoReadableFile(): void
    {
        $filename = TMP . 'noExistingDir/backup.sql';

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File or directory `' . $filename . '` is not readable');
        $this->BackupImport->filename = $filename;
    }

    #[Test]
    public function testFilenamePropertyNotSetted(): void
    {
        $this->expectExceptionMessage('You must first set the filename');
        /** @phpstan-ignore-next-line */
        $this->BackupImport->filename;
    }
}
