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

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Email;
use Cake\ORM\Entity;
use Cake\TestSuite\EmailAssertTrait;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use InvalidArgumentException;
use Tools\Exception\NotReadableException;
use Tools\Exception\NotWritableException;
use Tools\Filesystem;

/**
 * BackupManagerTest class
 */
class BackupManagerTest extends TestCase
{
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
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupExport = $this->BackupExport ?? new BackupExport();
        $this->BackupManager = $this->BackupManager ?? new BackupManager();
    }

    /**
     * Test for `delete()` method
     * @test
     */
    public function testDelete()
    {
        $filename = $this->BackupExport->export();
        $this->assertFileExists($filename);
        $this->assertSame($filename, $this->BackupManager->delete($filename));
        $this->assertFileDoesNotExist($filename);

        //With a relative path
        $filename = $this->BackupExport->export();
        $this->assertFileExists($filename);
        $this->assertSame($filename, $this->BackupManager->delete(basename($filename)));
        $this->assertFileDoesNotExist($filename);
    }

    /**
     * Test for `deleteAll()` method
     * @test
     */
    public function testDeleteAll()
    {
        $createdFiles = $this->createSomeBackups();
        $this->assertEquals(array_reverse($createdFiles), $this->BackupManager->deleteAll());
        $this->assertEmpty($this->BackupManager->index()->toList());

        $this->expectException(NotWritableException::class);
        $this->expectExceptionMessage('File or directory `' . $this->getAbsolutePath('noExistingFile') . '` does not exist');
        $this->BackupManager->delete('noExistingFile');
    }

    /**
     * Test for `index()` method
     * @test
     */
    public function testIndex()
    {
        //Creates a text file. This file should be ignored
        Filesystem::instance()->createFile(Configure::read('DatabaseBackup.target') . DS . 'text.txt');

        $createdFiles = $this->createSomeBackups();
        $files = $this->BackupManager->index();

        //Checks compressions
        $compressions = $files->extract('compression')->toList();
        $this->assertEquals(['gzip', 'bzip2', false], $compressions);

        //Checks filenames
        $filenames = $files->extract('filename')->toList();
        $this->assertEquals(array_reverse(array_map('basename', $createdFiles)), $filenames);

        //Checks extensions
        $extensions = $files->extract('extension')->toList();
        $this->assertEquals(['sql.gz', 'sql.bz2', 'sql'], $extensions);

        //Checks for properties of each backup object
        foreach ($files as $file) {
            $this->assertInstanceOf(Entity::class, $file);
            $this->assertIsPositive($file->get('size'));
            $this->assertInstanceOf(FrozenTime::class, $file->get('datetime'));
        }
    }

    /**
     * Test for `rotate()` method
     * @test
     */
    public function testRotate()
    {
        $this->assertEquals([], BackupManager::rotate(1));

        $this->createSomeBackups();

        $initialFiles = $this->BackupManager->index();

        //Keeps 2 backups. Only 1 backup was deleted
        $rotate = $this->BackupManager->rotate(2);
        $this->assertCount(1, $rotate);

        //Now there are two files. Only uncompressed file was deleted
        $filesAfterRotate = $this->BackupManager->index();
        $this->assertCount(2, $filesAfterRotate);
        $this->assertEquals(['gzip', 'bzip2'], $filesAfterRotate->extract('compression')->toList());

        //Gets the difference
        $diff = array_udiff($initialFiles->toList(), $filesAfterRotate->toList(), function (Entity $first, Entity $second) {
            return strcmp($first->get('filename'), $second->get('filename'));
        });

        //Again, only 1 backup was deleted. The difference is the same
        $this->assertCount(1, $diff);
        $this->assertEquals(collection($diff)->first(), collection($rotate)->first());

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
        $recipient = 'recipient@example.com';

        $this->_email = $this->invokeMethod($this->BackupManager, 'getEmailInstance', [$file, $recipient]);
        $this->assertInstanceof(Email::class, $this->_email);
        $this->assertEmailFrom(Configure::read('DatabaseBackup.mailSender'));
        $this->assertEmailTo($recipient);
        $this->assertEmailSubject('Database backup ' . basename($file) . ' from localhost');
        $this->assertEmailAttachmentsContains(basename($file), compact('file', 'mimetype'));

        //With an invalid sender
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email set for "from". You passed "invalidSender".');
        @unlink($file);
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
        $this->expectExceptionMessage('File or directory `' . Configure::read('DatabaseBackup.target') . DS . 'noExistingFile` does not exist');
        $this->BackupManager->send('noExistingFile', 'recipient@example.com');
    }
}
