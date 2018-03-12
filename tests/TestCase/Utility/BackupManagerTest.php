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
use Cake\TestSuite\EmailAssertTrait;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use Reflection\ReflectionTrait;

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
        $this->createSomeBackups(true);

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
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp /^File or directory `[\s\w\/:\\]+noExistingFile.sql` not writable$/
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
        file_put_contents(Configure::read(DATABASE_BACKUP . '.target') . DS . 'text.txt', null);

        $this->assertEmpty($this->BackupManager->index());

        //Creates some backups
        $this->createSomeBackups(true);
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
        $this->createSomeBackups(true);
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
     * @expectedException InvalidArgumentException
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
        $file = $this->createBackup();

        $instance = new BackupManager;
        $this->_email = $this->invokeMethod($instance, 'getEmailInstance', [$file, $to]);
        $this->assertInstanceof('Cake\Mailer\Email', $this->_email);

        $this->assertEmailFrom(Configure::read(DATABASE_BACKUP . '.mailSender'));
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
     * Test for `send()` method, with empty sender
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The email set for "from" is empty.
     */
    public function testSendEmptySender()
    {
        //Get a backup file
        $file = $this->createBackup();

        Configure::write(DATABASE_BACKUP . '.mailSender', false);

        $this->BackupManager->send($file, 'recipient@example.com');
    }

    /**
     * Test for `send()` method, with an invalid file
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp File or directory `[\s\w\/:\\]+` not readable
     */
    public function testSendInvalidFile()
    {
        $this->BackupManager->send('noExistingFile', 'recipient@example.com');
    }

    /**
     * Test for `send()` method, with an invalid sender
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid email set for "from". You passed "invalidSender".
     */
    public function testSendInvalidSender()
    {
        //Get a backup file
        $file = $this->createBackup();

        Configure::write(DATABASE_BACKUP . '.mailSender', 'invalidSender');

        $this->BackupManager->send($file, 'recipient@example.com');
    }
}
