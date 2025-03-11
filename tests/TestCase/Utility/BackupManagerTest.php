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
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use DatabaseBackup\Compression;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupManager;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * BackupManagerTest class.
 *
 * @uses \DatabaseBackup\Utility\BackupManager
 */
class BackupManagerTest extends TestCase
{
    use EmailTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupManager
     */
    protected BackupManager $BackupManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->BackupManager ??= new BackupManager();
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::delete()
     */
    #[Test]
    public function testDelete(): void
    {
        $filename = $this->createBackup(fakeBackup: true);
        $this->assertSame($filename, $this->BackupManager->delete($filename));
        $this->assertFileDoesNotExist($filename);

        //With a relative path
        $filename = $this->createBackup(fakeBackup: true);
        $this->assertSame($filename, $this->BackupManager->delete(basename($filename)));
        $this->assertFileDoesNotExist($filename);
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::delete()
     */
    #[Test]
    public function testDeleteWithNoExistingFile(): void
    {
        $filename = TMP . 'noExistingFile';
        $this->expectExceptionMessage('File or directory `' . $filename . '` is not writable');
        $this->BackupManager->delete($filename);
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::delete()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testDeleteIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->BackupManager->delete($this->createBackup(fakeBackup: true));
        });
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::deleteAll()
     */
    #[Test]
    public function testDeleteAll(): void
    {
        $createdFiles = $this->createSomeBackups();
        $this->assertSame(array_reverse($createdFiles), $this->BackupManager->deleteAll());
        foreach ($createdFiles as $file) {
            $this->assertFileDoesNotExist($file);
        }
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::deleteAll()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testDeleteAllIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->BackupManager->deleteAll();
        });
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::index()
     */
    #[Test]
    public function testIndex(): void
    {
        //Creates a text file. This file should be ignored
        file_put_contents(Configure::read('DatabaseBackup.target') . DS . 'text.txt', '');

        $createdFiles = array_reverse($this->createSomeBackups());
        $files = $this->BackupManager->index();
        array_map('unlink', $createdFiles);
        $this->assertCount(3, $files);

        foreach ($files as $k => $file) {
            $this->assertSame(['filename', 'basename', 'path', 'compression', 'size', 'datetime'], array_keys($file));
            $this->assertSame(basename($createdFiles[$k]), $file['filename']);
            $this->assertSame(basename($createdFiles[$k]), $file['basename']);
            $this->assertSame($createdFiles[$k], $file['path']);
            $this->assertInstanceOf(Compression::class, $file['compression']);
            $this->assertIsInt($file['size']);
            $this->assertInstanceOf(DateTime::class, $file['datetime']);
        }
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::rotate()
     */
    #[Test]
    public function testRotate(): void
    {
        $this->assertSame([], BackupManager::rotate(1));

        /**
         * Creates 3 backups (`$initialFiles`) and keeps only 2 of them.
         *
         * So only 1 backup was deleted, which was the first one created.
         */
        $initialFiles = $this->createSomeBackups();
        $rotate = $this->BackupManager->rotate(2);
        $this->assertCount(1, $rotate);
        $this->assertSame($initialFiles[0], $rotate[0]['path']);
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::rotate()
     */
    #[Test]
    public function testRotateWithInvalidKeepValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid `$keep` value');
        $this->BackupManager->rotate(-1);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\BackupManager::send()
     */
    public function testSend(): void
    {
        Configure::write('DatabaseBackup.mailSender', 'sender@example.com');

        $file = $this->createBackup(fakeBackup: true);
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
        $this->BackupManager->send($this->createBackup(fakeBackup: true), 'recipient@example.com');
    }

    /**
     * @uses \DatabaseBackup\Utility\BackupManager::send()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testSendIsDeprecated(): void
    {
        Configure::write('DatabaseBackup.mailSender', 'sender@example.com');

        $this->deprecated(function (): void {
            $this->BackupManager->send($this->createBackup(fakeBackup: true), 'recipient@example.com');
        });
    }

    /**
     * Test for `send()` method, with an invalid file.
     *
     * @test
     * @uses \DatabaseBackup\Utility\BackupManager::send()
     */
    public function testSendWithInvalidFile(): void
    {
        $this->expectExceptionMessage('File or directory `' . Configure::readOrFail('DatabaseBackup.target') . 'noExistingFile` is not readable');
        $this->BackupManager->send('noExistingFile', 'recipient@example.com');
    }
}
