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

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\TestSuite\EmailTrait;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use InvalidArgumentException;
use Tools\Filesystem;

/**
 * BackupManagerTest class
 */
class BackupManagerTest extends TestCase
{
    use EmailTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected BackupExport $BackupExport;

    /**
     * @var \DatabaseBackup\Utility\BackupManager
     */
    protected BackupManager $BackupManager;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupExport ??= new BackupExport();
        $this->BackupManager ??= new BackupManager();
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupManager::delete()
     */
    public function testDelete(): void
    {
        $filename = $this->BackupExport->export() ?: '';
        $this->assertFileExists($filename);
        $this->assertSame($filename, $this->BackupManager->delete($filename));
        $this->assertFileDoesNotExist($filename);

        //With a relative path
        $filename = $this->BackupExport->export() ?: '';
        $this->assertFileExists($filename);
        $this->assertSame($filename, $this->BackupManager->delete(basename($filename)));
        $this->assertFileDoesNotExist($filename);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupManager::deleteAll()
     */
    public function testDeleteAll(): void
    {
        $createdFiles = createSomeBackups();
        $this->assertSame(array_reverse($createdFiles), $this->BackupManager->deleteAll());
        $this->assertEmpty($this->BackupManager->index()->toList());

        $this->expectExceptionMessage('File or directory `' . $this->getAbsolutePath('noExistingFile') . '` is not writable');
        $this->BackupManager->delete('noExistingFile');
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupManager::index()
     */
    public function testIndex(): void
    {
        //Creates a text file. This file should be ignored
        Filesystem::createFile(Configure::read('DatabaseBackup.target') . DS . 'text.txt');

        $createdFiles = createSomeBackups();
        $files = $this->BackupManager->index();

        //Checks compressions
        $compressions = $files->extract('compression')->toList();
        $this->assertSame(['bzip2', 'gzip', null], $compressions);

        //Checks filenames
        $filenames = $files->extract('filename')->toList();
        $this->assertSame(array_reverse(array_map('basename', $createdFiles)), $filenames);

        //Checks extensions
        $extensions = $files->extract('extension')->toList();
        $this->assertSame(['sql.bz2', 'sql.gz', 'sql'], $extensions);

        //Checks for properties of each backup object
        foreach ($files as $file) {
            $this->assertInstanceOf(Entity::class, $file);
            $this->assertGreaterThan(0, $file->get('size'));
            $this->assertInstanceOf(FrozenTime::class, $file->get('datetime'));
        }
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupManager::rotate()
     */
    public function testRotate(): void
    {
        $this->assertSame([], BackupManager::rotate(1));

        createSomeBackups();

        $initialFiles = $this->BackupManager->index();

        //Keeps 2 backups. Only 1 backup was deleted
        $rotate = $this->BackupManager->rotate(2);
        $this->assertCount(1, $rotate);

        //Now there are two files. Only uncompressed file was deleted
        $filesAfterRotate = $this->BackupManager->index();
        $this->assertCount(2, $filesAfterRotate);
        $this->assertSame(['bzip2', 'gzip'], $filesAfterRotate->extract('compression')->toList());

        //Gets the difference
        $diff = array_udiff($initialFiles->toList(), $filesAfterRotate->toList(), fn(Entity $first, Entity $second): int => strcmp($first->get('filename'), $second->get('filename')));

        //Again, only 1 backup was deleted. The difference is the same
        $this->assertCount(1, $diff);
        $this->assertEquals(collection($diff)->first(), collection($rotate)->first());

        $this->expectExceptionMessage('Invalid rotate value');
        $this->BackupManager->rotate(-1);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupManager::send()
     */
    public function testSend(): void
    {
        $file = createBackup();
        $recipient = 'recipient@example.com';
        $this->BackupManager->send($file, $recipient);
        $this->assertMailSentFrom(Configure::read('DatabaseBackup.mailSender'));
        $this->assertMailSentTo($recipient);
        $this->assertMailSentWith('Database backup ' . basename($file) . ' from localhost', 'subject');
        $this->assertMailContainsAttachment(basename($file), compact('file') + ['mimetype' => mime_content_type($file)]);

        //With an invalid sender
        $this->expectException(InvalidArgumentException::class);
        unlink($file);
        Configure::write('DatabaseBackup.mailSender', 'invalidSender');
        $this->BackupManager->send(createBackup(), 'recipient@example.com');
    }

    /**
     * Test for `send()` method, with an invalid file
     * @test
     * @uses \DatabaseBackup\Utility\BackupManager::send()
     */
    public function testSendWithInvalidFile(): void
    {
        $this->expectExceptionMessage('File or directory `' . Configure::readOrFail('DatabaseBackup.target') . 'noExistingFile` is not readable');
        $this->BackupManager->send('noExistingFile', 'recipient@example.com');
    }
}
