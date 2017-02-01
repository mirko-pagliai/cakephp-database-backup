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
use MysqlBackup\Utility\BackupManager;

/**
 * BackupManagerTest class
 */
class BackupManagerTest extends TestCase
{
    /**
     * @var \MysqlBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * Creates some backups
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     */
    protected function _createSomeBackups($sleep = false)
    {
        $this->BackupExport->filename('backup.sql')->export();

        if ($sleep) {
            sleep(1);
        }

        $this->BackupExport->filename('backup.sql.bz2')->export();

        if ($sleep) {
            sleep(1);
        }

        $this->BackupExport->filename('backup.sql.gz')->export();

        return BackupManager::index();
    }

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
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->BackupExport);

        //Deletes all backups
        foreach (glob(Configure::read('MysqlBackup.target') . DS . '*') as $file) {
            unlink($file);
        }
    }

    /**
     * Test for `delete()` method
     * @test
     */
    public function testDelete()
    {
        $filename = $this->BackupExport->export();

        $this->assertFileExists($filename);
        $this->assertTrue(BackupManager::delete($filename));
        $this->assertFileNotExists($filename);

        //Relative path
        $filename = $this->BackupExport->export();

        $this->assertFileExists($filename);
        $this->assertTrue(BackupManager::delete(basename($filename)));
        $this->assertFileNotExists($filename);
    }

    /**
     * Test for `deleteAll()` method
     * @test
     */
    public function testDeleteAll()
    {
        //Creates some backups
        $this->_createSomeBackups(true);

        $this->assertNotEmpty(BackupManager::index());
        $this->assertEquals([
            'backup.sql.gz',
            'backup.sql.bz2',
            'backup.sql',
        ], BackupManager::deleteAll());
        $this->assertEmpty(BackupManager::index());
    }

    /**
     * Test for `delete()` method, with a no existing file
     * @test
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File or directory `/tmp/backups/noExistingFile.sql` not writable
     */
    public function testDeleteNoExistingFile()
    {
        BackupManager::delete('noExistingFile.sql');
    }

    /**
     * Test for `index()` method
     * @test
     */
    public function testIndex()
    {
        $this->assertEmpty(BackupManager::index());

        //Creates a text file. This file should be ignored
        file_put_contents(Configure::read('MysqlBackup.target') . DS . 'text.txt', null);

        $this->assertEmpty(BackupManager::index());

        //Creates some backups
        $files = $this->_createSomeBackups(true);
        $this->assertEquals(3, count(BackupManager::index()));

        $this->assertEquals('gzip', $files[0]->compression);
        $this->assertEquals('bzip2', $files[1]->compression);
        $this->assertEquals(false, $files[2]->compression);

        //Checks for properties of each backup object
        foreach ($files as $file) {
            $this->assertInstanceOf('stdClass', $file);

            $this->assertTrue(property_exists($file, 'filename'));
            $this->assertRegExp('/^backup\.sql(\.(bz2|gz))?$/', $file->filename);

            $this->assertTrue(property_exists($file, 'extension'));
            $this->assertTrue(in_array($file->extension, ['sql', 'sql.bz2', 'sql.gz']));

            $this->assertTrue(property_exists($file, 'size'));
            $this->assertTrue(isPositive($file->size));

            $this->assertTrue(property_exists($file, 'compression'));
            $this->assertTrue(in_array($file->compression, [false, 'bzip2', 'gzip']));

            $this->assertTrue(property_exists($file, 'datetime'));
            $this->assertInstanceOf('Cake\I18n\FrozenTime', $file->datetime);
        }
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        $this->assertEquals([], BackupManager::rotate(1));

        //Creates some backups
        $files = $this->_createSomeBackups(true);

        //Keeps 2 backups. Only 1 backup was deleted
        $rotate = BackupManager::rotate(2);
        $this->assertEquals(1, count($rotate));

        //Now there are two files. Only uncompressed file was deleted
        $filesAfterRotate = BackupManager::index();
        $this->assertEquals(2, count($filesAfterRotate));
        $this->assertEquals('gzip', $filesAfterRotate[0]->compression);
        $this->assertEquals('bzip2', $filesAfterRotate[1]->compression);

        //Gets the difference
        $diff = array_udiff($files, $filesAfterRotate, function ($a, $b) {
            return strcmp($a->filename, $b->filename);
        });

        //Again, only 1 backup was deleted
        $this->assertEquals(1, count($diff));

        //The difference is the same
        $this->assertEquals(array_values($diff)[0], array_values($rotate)[0]);
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @test
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid rotate value
     */
    public function testRotateWithInvalidValue()
    {
        BackupManager::rotate(-1);
    }
}
