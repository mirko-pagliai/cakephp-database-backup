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
use Cake\Mailer\Email;
use Cake\TestSuite\EmailAssertTrait;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use Tools\ReflectionTrait;

/**
 * BackupManagerTest class
 */
class BackupManagerTest extends TestCase
{
    use EmailAssertTrait;
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * @var \DatabaseBackup\Utility\BackupManager
     */
    protected $BackupManager;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->BackupExport = new BackupExport;
        $this->BackupManager = new BackupManager;
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

        //With a relative path
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
        $this->createSomeBackups(true);

        $this->assertEquals(['backup.sql.gz', 'backup.sql.bz2', 'backup.sql'], $this->BackupManager->deleteAll());
        $this->assertEmpty($this->BackupManager->index()->toList());
    }

    /**
     * Test for `delete()` method, with a no existing file
     * @expectedException ErrorException
     * @expectedExceptionMessageRegExp /^File or directory `[\s\w\/:\\\-]+noExistingFile.sql` is not writable$/
     * @test
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
        //Creates a text file. This file should be ignored
        file_put_contents(Configure::read('DatabaseBackup.target') . DS . 'text.txt', null);

        $this->createSomeBackups(true);

        $files = $this->BackupManager->index();

        //Checks compressions
        $compressions = $files->extract('compression')->toList();
        $this->assertEquals(['gzip', 'bzip2', false], $compressions);

        //Checks filenames
        $filenames = $files->extract('filename')->toList();
        $this->assertEquals(['backup.sql.gz', 'backup.sql.bz2', 'backup.sql'], $filenames);

        //Checks extensions
        $extensions = $files->extract('extension')->toList();
        $this->assertEquals(['sql.gz', 'sql.bz2', 'sql'], $extensions);

        //Checks for properties of each backup object
        foreach ($files as $file) {
            $this->assertInstanceOf('Cake\ORM\Entity', $file);
            $this->assertTrue(is_positive($file->size));
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

        $this->createSomeBackups(true);

        $initialFiles = $this->BackupManager->index();

        //Keeps 2 backups. Only 1 backup was deleted
        $rotate = $this->BackupManager->rotate(2);
        $this->assertEquals(1, count($rotate));

        //Now there are two files. Only uncompressed file was deleted
        $filesAfterRotate = $this->BackupManager->index();
        $this->assertEquals(2, $filesAfterRotate->count());
        $this->assertEquals(['gzip', 'bzip2'], $filesAfterRotate->extract('compression')->toList());

        //Gets the difference
        $diff = array_udiff($initialFiles->toList(), $filesAfterRotate->toList(), function ($a, $b) {
            return strcmp($a->filename, $b->filename);
        });

        //Again, only 1 backup was deleted
        $this->assertEquals(1, count($diff));

        //The difference is the same
        $this->assertEquals(collection($diff)->first(), collection($rotate)->first());
    }

    /**
     * Test for `rotate()` method, with an invalid value
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid rotate value
     * @test
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
        $file = $this->createBackup();
        $mimetype = mime_content_type($file);
        $to = 'recipient@example.com';

        $instance = new BackupManager;
        $this->_email = $this->invokeMethod($instance, 'getEmailInstance', [$file, $to]);
        $this->assertInstanceof(Email::class, $this->_email);

        $this->assertEmailFrom(Configure::read('DatabaseBackup.mailSender'));
        $this->assertEmailTo($to);
        $this->assertEmailSubject('Database backup ' . basename($file) . ' from localhost');
        $this->assertEmailAttachmentsContains(basename($file), compact('file', 'mimetype'));
        $this->assertArrayKeysEqual(['headers', 'message'], $this->BackupManager->send($file, $to));
    }

    /**
     * Test for `send()` method, with an invalid file
     * @expectedException ErrorException
     * @expectedExceptionMessageRegExp /^File or directory `[\s\w\/:\\\-]+` is not readable$/
     * @test
     */
    public function testSendInvalidFile()
    {
        $this->BackupManager->send('noExistingFile', 'recipient@example.com');
    }

    /**
     * Test for `send()` method, with an invalid sender
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid email set for "from". You passed "invalidSender".
     * @test
     */
    public function testSendInvalidSender()
    {
        Configure::write('DatabaseBackup.mailSender', 'invalidSender');

        $this->BackupManager->send($this->createBackup(), 'recipient@example.com');
    }
}
