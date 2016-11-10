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
     * Creates some backups
     * @return array
     */
    protected function _createSomeBackups()
    {
        $instance = new BackupExport();
        $instance->export();
        $instance->compression('bzip2')->export();
        $instance->compression('gzip')->export();

        return BackupManager::index();
    }

    /**
     * Creates some backups, waiting a second for each
     * @return array
     */
    protected function _createSomeBackupsWithSleep()
    {
        $instance = new BackupExport();
        $instance->export();
        sleep(1);
        $instance->compression('bzip2')->export();
        sleep(1);
        $instance->compression('gzip')->export();

        return BackupManager::index();
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

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
        $filename = (new BackupExport())->export('backup.sql');

        $this->assertFileExists($filename);
        $this->assertTrue(BackupManager::delete($filename));
        $this->assertFileNotExists($filename);

        //Absolute path
        $filename = (new BackupExport())->export(Configure::read('MysqlBackup.target') . DS . 'backup.sql');

        $this->assertFileExists($filename);
        $this->assertTrue(BackupManager::delete($filename));
        $this->assertFileNotExists($filename);
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
     * @uses _createSomeBackups()
     */
    public function testIndex()
    {
        $this->assertEmpty(BackupManager::index());

        //Creates a text file. This file should be ignored
        file_put_contents(Configure::read('MysqlBackup.target') . DS . 'text.txt', null);

        $this->assertEmpty(BackupManager::index());

        //Creates some backups
        $this->_createSomeBackups();
        $this->assertEquals(3, count(BackupManager::index()));
    }

    /**
     * Test for `index()` method. This tests the backups order
     * @test
     * @uses _createSomeBackupsWithSleep()
     */
    public function testIndexOrder()
    {
        $files = $this->_createSomeBackupsWithSleep();
        $this->assertEquals('gzip', $files[0]->compression);
        $this->assertEquals('bzip2', $files[1]->compression);
        $this->assertEquals(false, $files[2]->compression);
    }

    /**
     * Test for `index()` method, properties
     * @test
     * @uses _createSomeBackups()
     */
    public function testIndexProperties()
    {
        //Creates some backups
        foreach ($this->_createSomeBackups() as $file) {
            $this->assertEquals('stdClass', get_class($file));

            $this->assertTrue(property_exists($file, 'filename'));
            $this->assertRegExp('/^backup_test_[0-9]{14}\.sql(\.(bz2|gz))?$/', $file->filename);

            $this->assertTrue(property_exists($file, 'extension'));
            $this->assertTrue(in_array($file->extension, ['sql', 'sql.bz2', 'sql.gz']));

            $this->assertTrue(property_exists($file, 'size'));
            $this->assertTrue(isPositive($file->size));

            $this->assertTrue(property_exists($file, 'compression'));
            $this->assertTrue(in_array($file->compression, [false, 'bzip2', 'gzip']));

            $this->assertTrue(property_exists($file, 'time'));
            $this->assertEquals('Cake\I18n\FrozenTime', get_class($file->time));
        }
    }

    /**
     * Test for `rotate()` method
     * @test
     * @uses _createSomeBackupsWithSleep()
     */
    public function testRotate()
    {
        $this->assertEquals([], BackupManager::rotate(1));

        //Creates some backups
        $files = $this->_createSomeBackupsWithSleep();

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
