<?php
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

use Cake\Core\Configure;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupImport;
use Tools\ReflectionTrait;

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
     * @var \DatabaseBackup\Utility\$BackupImport
     */
    protected $BackupImport;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->BackupExport = new BackupExport;
        $this->BackupImport = new BackupImport;
    }

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        $this->assertInstanceof(DATABASE_BACKUP . '\Driver\Mysql', $this->getProperty($this->BackupImport, 'driver'));
        $this->assertNull($this->getProperty($this->BackupImport, 'filename'));
    }

    /**
     * Test for `filename()` method. This tests also `$compression` property
     * @test
     */
    public function testFilename()
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
    }

    /**
     * Test for `filename()` method, with invalid directory
     * @expectedException ErrorException
     * @expectedExceptionMessageRegExp /^File or directory `[\s\w\/:\\]+backup\.sql` is not readable$/
     * @test
     */
    public function testFilenameWithInvalidDirectory()
    {
        $this->BackupImport->filename('noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with invalid extension
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid file extension
     * @test
     */
    public function testFilenameWithInvalidExtension()
    {
        file_put_contents(Configure::read(DATABASE_BACKUP . '.target') . DS . 'backup.txt', null);

        $this->BackupImport->filename('backup.txt');
    }

    /**
     * Test for `import()` method, without compression
     * @test
     */
    public function testImport()
    {
        //Exports and imports with no compression
        $backup = $this->BackupExport->compression(false)->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql$/', basename($filename));

        //Exports and imports with `bzip2` compression
        $backup = $this->BackupExport->compression('bzip2')->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.bz2$/', basename($filename));

        //Exports and imports with `gzip` compression
        $backup = $this->BackupExport->compression('gzip')->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.gz$/', basename($filename));
    }

    /**
     * Test for `import()` method, without a filename
     * @expectedException RuntimeException
     * @expectedExceptionMessage You must first set the filename
     * @test
     */
    public function testImportWithoutFilename()
    {
        $this->BackupImport->import();
    }
}
