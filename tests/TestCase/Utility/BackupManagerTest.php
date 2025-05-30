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
use DatabaseBackup\Compression;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupManager;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * BackupManagerTest class.
 */
#[CoversClass(BackupManager::class)]
class BackupManagerTest extends TestCase
{
    #[Test]
    #[WithoutErrorHandler]
    public function testIndex(): void
    {
        //Creates a text file. This file should be ignored
        file_put_contents(Configure::read('DatabaseBackup.target') . DS . 'text.txt', '');

        $createdFiles = $this->createSomeBackups();

        $this->deprecated(function () use ($createdFiles) {
            $files = BackupManager::index();
            $this->assertCount(3, $files);

            foreach ($files as $k => $file) {
                $this->assertSame(['basename', 'path', 'compression', 'size', 'datetime'], array_keys($file));
                $this->assertSame(basename($createdFiles[$k]), $file['basename']);
                $this->assertSame($createdFiles[$k], $file['path']);
                $this->assertInstanceOf(Compression::class, $file['compression']);
                $this->assertIsInt($file['size']);
                $this->assertInstanceOf(DateTime::class, $file['datetime']);
            }
        });

        array_map('unlink', $createdFiles);
    }

    #[Test]
    #[WithoutErrorHandler]
    public function testRotate(): void
    {
        $this->deprecated(fn () => $this->assertSame([], BackupManager::rotate(1)));

        /**
         * Creates 3 backups (`$initialFiles`) and keeps only 2 of them.
         *
         * So only 1 backup was deleted, which was the last one created.
         */
        $initialFiles = $this->createSomeBackups();

        $this->deprecated(function () use ($initialFiles) {
            $rotate = BackupManager::rotate(2);
            $this->assertCount(1, $rotate);
            $this->assertSame($initialFiles[2], $rotate[0]['path']);
        });
    }

    #[Test]
    #[WithoutErrorHandler]
    public function testRotateWithInvalidKeepValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid `$keep` value');
        $this->deprecated(fn () => BackupManager::rotate(-1));
    }
}
