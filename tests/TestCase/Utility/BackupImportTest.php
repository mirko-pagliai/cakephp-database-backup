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

use App\Database\FakeConnection;
use App\Executor\FakeExecutor;
use Cake\Event\EventInterface;
use Cake\Event\EventList;
use DatabaseBackup\Executor\Executor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupImport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * BackupImportTest.
 */
#[CoversClass(BackupImport::class)]
class BackupImportTest extends TestCase
{
    protected BackupImport $BackupImport;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupImport = new BackupImport();
        $this->BackupImport->Connection = new FakeConnection();
    }

    #[Test]
    public function testFilename(): void
    {
        $filename = TMP . 'backup.sql';
        file_put_contents($filename, '');

        $this->BackupImport->filename = $filename;
        unlink($filename);

        $result = $this->BackupImport->filename;
        $this->assertSame($filename, $result);
    }

    #[Test]
    public function testFilenamePropertyNoReadableFile(): void
    {
        $filename = TMP . 'noExistingDir/backup.sql';

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('File or directory `' . $filename . '` is not readable');
        $this->BackupImport->filename = $filename;
    }

    #[Test]
    public function testFilenamePropertyNotSetted(): void
    {
        $this->expectExceptionMessage('You must first set the filename');
        /** @phpstan-ignore-next-line */
        $this->BackupImport->filename;
    }

    #[Test]
    public function testImport(): void
    {
        $filename = TMP . 'backup.sql';
        file_put_contents($filename, '');

        $this->BackupImport->Executor = new FakeExecutor();
        $this->BackupImport->Executor->getEventManager()->setEventList(new EventList());
        $this->BackupImport->filename = $filename;

        $result = $this->BackupImport->import();
        $this->assertSame($filename, $result);
        $this->assertEventFired('Backup.beforeImport', $this->BackupImport->Executor->getEventManager());
        $this->assertEventFired('Backup.afterImport', $this->BackupImport->Executor->getEventManager());

        $this->markTestIncomplete();
    }

    /**
     * `import()` is stopped by the `Backup.beforeImport` event (implemented by the `Executor` class)
     */
    #[Test]
    public function testImportStoppedByBeforeImport(): void
    {
        $this->BackupImport->Executor = new class extends FakeExecutor
        {
            public function beforeImport(EventInterface $Event): void
            {
                $Event->stopPropagation();
            }
        };

        $result = $this->BackupImport->import();
        $this->assertFalse($result);
    }
}
