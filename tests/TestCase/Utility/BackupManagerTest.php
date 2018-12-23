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
use Cake\I18n\FrozenTime;
use Cake\Mailer\Email;
use Cake\ORM\Entity;
use Cake\TestSuite\EmailAssertTrait;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use InvalidArgumentException;
use Tools\Exception\NotReadableException;
use Tools\Exception\NotWritableException;

/**
 * BackupManagerTest class
 */
class BackupManagerTest extends TestCase
{
    use BackupTrait;
    use EmailAssertTrait;

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

        //With a no existing file
        $this->expectException(NotWritableException::class);
        $this->expectExceptionMessage('File or directory `' . $this->getAbsolutePath('noExistingFile') . '` is not writable');
        $this->BackupManager->delete('noExistingFile');
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
            $this->assertInstanceOf(Entity::class, $file);
            $this->assertTrue(is_positive($file->size));
            $this->assertInstanceOf(FrozenTime::class, $file->datetime);
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

        //With an invalid value
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rotate value');
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

        //With an invalid sender
        safe_unlink($file);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email set for "from". You passed "invalidSender".');
        Configure::write('DatabaseBackup.mailSender', 'invalidSender');
        $this->BackupManager->send($this->createBackup(), 'recipient@example.com');
    }

    /**
     * Test for `send()` method, with an invalid file
     * @test
     */
    public function testSendInvalidFile()
    {
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('File or directory `' . Configure::read('DatabaseBackup.target') . DS . 'noExistingFile` is not readable');
        $this->BackupManager->send('noExistingFile', 'recipient@example.com');
    }
}
