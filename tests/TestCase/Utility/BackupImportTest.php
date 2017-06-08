<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace MysqlBackup\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use MysqlBackup\Utility\BackupExport;
use MysqlBackup\Utility\BackupImport;
use Reflection\ReflectionTrait;

/**
 * BackupImportTest class
 */
class BackupImportTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @var \MysqlBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * @var \MysqlBackup\Utility\$BackupImport
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
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->BackupExport, $this->BackupImport);

        //Deletes all backups
        foreach (glob(Configure::read(MYSQL_BACKUP . '.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        $this->assertInstanceof('MysqlBackup\Driver\Mysql', $this->getProperty($this->BackupImport, 'driver'));
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
        $this->assertEquals('/tmp/backups/backup.sql', $this->getProperty($this->BackupImport, 'filename'));

        //Creates a `sql.bz2` backup
        $backup = $this->BackupExport->filename('backup.sql.bz2')->export();

        $this->BackupImport->filename($backup);
        $this->assertEquals('/tmp/backups/backup.sql.bz2', $this->getProperty($this->BackupImport, 'filename'));

        //Creates a `sql.gz` backup
        $backup = $this->BackupExport->filename('backup.sql.gz')->export();

        $this->BackupImport->filename($backup);
        $this->assertEquals('/tmp/backups/backup.sql.gz', $this->getProperty($this->BackupImport, 'filename'));

        //Relative path
        $this->BackupImport->filename(basename($backup));
        $this->assertEquals('/tmp/backups/backup.sql.gz', $this->getProperty($this->BackupImport, 'filename'));
    }

    /**
     * Test for `filename()` method, with invalid directory
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File or directory `/tmp/backups/noExistingDir/backup.sql` not readable
     * @test
     */
    public function testFilenameWithInvalidDirectory()
    {
        $this->BackupImport->filename('noExistingDir' . DS . 'backup.sql');
    }

    /**
     * Test for `filename()` method, with invalid extension
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid compression type
     * @test
     */
    public function testFilenameWithInvalidExtension()
    {
        file_put_contents(Configure::read(MYSQL_BACKUP . '.target') . DS . 'backup.txt', null);

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
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Before you import a database, you have to set the filename
     * @test
     */
    public function testImportWithoutFilename()
    {
        $this->BackupImport->import();
    }
}
