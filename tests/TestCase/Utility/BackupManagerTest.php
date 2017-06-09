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
use Cake\TestSuite\EmailAssertTrait;
use Cake\TestSuite\TestCase;
use MysqlBackup\Utility\BackupExport;
use MysqlBackup\Utility\BackupManager;
use Reflection\ReflectionTrait;

/**
 * BackupManagerTest class
 */
class BackupManagerTest extends TestCase
{
    use EmailAssertTrait;
    use ReflectionTrait;

    /**
     * @var \MysqlBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * @var \MysqlBackup\Utility\BackupManager
     */
    protected $BackupManager;

    /**
     * Internal method to create a backup file
     * @return string
     */
    protected function _createBackup()
    {
        return $this->BackupExport->filename('backup.sql')->export();
    }

    /**
     * Internal method to create some backup files
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     */
    protected function _createSomeBackups($sleep = false)
    {
        $files[] = $this->BackupExport->filename('backup.sql')->export();

        if ($sleep) {
            sleep(1);
        }

        $files[] = $this->BackupExport->filename('backup.sql.bz2')->export();

        if ($sleep) {
            sleep(1);
        }

        $files[] = $this->BackupExport->filename('backup.sql.gz')->export();

        return $files;
    }

    /**
     * Internal method to delete all backups
     */
    protected function _deleteAllBackups()
    {
        foreach (glob(Configure::read(MYSQL_BACKUP . '.target') . DS . '*') as $file) {
            unlink($file);
        }
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
        $this->BackupManager = new BackupManager;

        $this->_deleteAllBackups();
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->BackupExport);

        $this->_deleteAllBackups();
    }

    /**
     * Test for `delete()` method
     * @test
     */
    public function testDelete()
    {
        $filename = $this->BackupExport->export();

        $this->assertFileExists($filename);
        $this->assertTrue($this->BackupManager->delete($filename));
        $this->assertFileNotExists($filename);

        //Relative path
        $filename = $this->BackupExport->export();

        $this->assertFileExists($filename);
        $this->assertTrue($this->BackupManager->delete(basename($filename)));
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

        $this->assertNotEmpty($this->BackupManager->index());
        $this->assertEquals([
            'backup.sql.gz',
            'backup.sql.bz2',
            'backup.sql',
        ], $this->BackupManager->deleteAll());
        $this->assertEmpty($this->BackupManager->index());
    }

    /**
     * Test for `delete()` method, with a no existing file
     * @test
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage File or directory `/tmp/backups/noExistingFile.sql` not writable
     */
    public function testDeleteNoExistingFile()
    {
        $this->BackupManager->delete('noExistingFile.sql');
    }

    /**
     * Test for `index()` method
     * @test
     */
    public function testIndex()
    {
        $this->assertEmpty($this->BackupManager->index());

        //Creates a text file. This file should be ignored
        file_put_contents(Configure::read(MYSQL_BACKUP . '.target') . DS . 'text.txt', null);

        $this->assertEmpty($this->BackupManager->index());

        //Creates some backups
        $this->_createSomeBackups(true);
        $files = $this->BackupManager->index();
        $this->assertEquals(3, count($files));

        //Checks compressions
        $compressions = collection($files)->extract('compression')->toArray();
        $this->assertEquals(['gzip', 'bzip2', false], $compressions);

        //Checks filenames
        $filenames = collection($files)->extract('filename')->toArray();
        $this->assertEquals([
            'backup.sql.gz',
            'backup.sql.bz2',
            'backup.sql',
        ], $filenames);

        //Checks extensions
        $extensions = collection($files)->extract('extension')->toArray();
        $this->assertEquals(['sql.gz', 'sql.bz2', 'sql'], $extensions);

        //Checks for properties of each backup object
        foreach ($files as $file) {
            $this->assertInstanceOf('Cake\ORM\Entity', $file);
            $this->assertTrue(isPositive($file->size));
            $this->assertInstanceOf('Cake\I18n\FrozenTime', $file->datetime);
        }
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        $this->assertEquals([], $this->BackupManager->rotate(1));

        //Creates some backups
        $this->_createSomeBackups(true);
        $initialFiles = $this->BackupManager->index();

        //Keeps 2 backups. Only 1 backup was deleted
        $rotate = $this->BackupManager->rotate(2);
        $this->assertEquals(1, count($rotate));

        //Now there are two files. Only uncompressed file was deleted
        $filesAfterRotate = $this->BackupManager->index();
        $this->assertEquals(2, count($filesAfterRotate));
        $this->assertEquals('gzip', $filesAfterRotate[0]->compression);
        $this->assertEquals('bzip2', $filesAfterRotate[1]->compression);

        //Gets the difference
        $diff = array_udiff($initialFiles, $filesAfterRotate, function ($a, $b) {
            return strcmp($a->filename, $b->filename);
        });

        //Again, only 1 backup was deleted
        $this->assertEquals(1, count($diff));

        //The difference is the same
        $this->assertEquals(collection($diff)->first(), collection($rotate)->first());
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @test
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Invalid rotate value
     */
    public function testRotateWithInvalidValue()
    {
        $this->BackupManager->rotate(-1);
    }

    /**
     * Test for `send()` and `_send()` methods
     * @test
     */
    public function testSend()
    {
        $to = 'recipient@example.com';

        //Get a backup file
        $file = $this->_createBackup();

        $instance = new BackupManager;
        $this->_email = $this->invokeMethod($instance, '_send', [$file, $to]);
        $this->assertInstanceof('Cake\Mailer\Email', $this->_email);

        $this->assertEmailFrom(Configure::read(MYSQL_BACKUP . '.mailSender'));
        $this->assertEmailTo($to);
        $this->assertEmailSubject('Database backup ' . basename($file) . ' from localhost');
        $this->assertEmailAttachmentsContains(basename($file), [
            'file' => $file,
            'mimetype' => mime_content_type($file),
        ]);

        $send = $this->BackupManager->send($file, $to);
        $this->assertNotEmpty($send);
        $this->assertEquals(['headers', 'message'], array_keys($send));
    }

    /**
     * Test for `send()` method, without a sender
     * @test
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage You must first set the mail sender
     */
    public function testSendWithoutSender()
    {
        Configure::write(MYSQL_BACKUP . '.mailSender', false);

        $this->BackupManager->send('file.sql', 'recipient@example.com');
    }
}
