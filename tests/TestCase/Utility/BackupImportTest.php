<?php
/** @noinspection PhpUnhandledExceptionInspection */
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
use DatabaseBackup\Utility\BackupImport;
use Tools\Exception\NotReadableException;
use Tools\Filesystem;
use Tools\TestSuite\ReflectionTrait;

/**
 * BackupImportTest class
 */
class BackupImportTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * @var \DatabaseBackup\Utility\BackupImport
     */
    protected $BackupImport;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupExport = $this->BackupExport ?: new BackupExport();
        $this->BackupImport = $this->BackupImport ?: new BackupImport();
    }

    /**
     * Test for `filename()` method. This tests also `$compression` property
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::filename()
     */
    public function testFilename(): void
    {
        //Creates a `sql` backup
        $backup = $this->BackupExport->filename('backup.sql')->export();
        $this->BackupImport->filename($backup);
        $this->assertEquals($backup, $this->getProperty($this->BackupImport, 'filename'));

        //Creates a `sql.bz2` backup
        $backup = $this->BackupExport->filename('backup.sql.bz2')->export();
        $this->BackupImport->filename($backup);
        $this->assertEquals($backup, $this->getProperty($this->BackupImport, 'filename'));

        //Creates a `sql.gz` backup
        $backup = $this->BackupExport->filename('backup.sql.gz')->export();
        $this->BackupImport->filename($backup);
        $this->assertEquals($backup, $this->getProperty($this->BackupImport, 'filename'));

        //With a relative path
        $this->BackupImport->filename(basename($backup));
        $this->assertEquals($backup, $this->getProperty($this->BackupImport, 'filename'));

        //With an invalid directory
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('File or directory `' . TMP . 'noExistingDir' . DS . 'backup.sql` is not readable');
        $this->BackupImport->filename(TMP . 'noExistingDir' . DS . 'backup.sql');

        //With invalid extension
        $this->expectExceptionMessage('Invalid file extension');
        $this->BackupImport->filename(Filesystem::createTmpFile());
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupImport::import()
     */
    public function testImport(): void
    {
        //Exports and imports with no compression
        $backup = $this->BackupExport->compression(null)->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertMatchesRegularExpression('/^backup_test_[0-9]{14}\.sql$/', basename($filename));

        //Exports and imports with `bzip2` compression
        $backup = $this->BackupExport->compression('bzip2')->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertMatchesRegularExpression('/^backup_test_[0-9]{14}\.sql\.bz2$/', basename($filename));

        //Exports and imports with `gzip` compression
        $backup = $this->BackupExport->compression('gzip')->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertMatchesRegularExpression('/^backup_test_[0-9]{14}\.sql\.gz$/', basename($filename));

        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }
}
