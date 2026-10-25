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

namespace DatabaseBackup\Test\TestCase\Executor;

use App\Database\FakeConnection;
use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Event\EventInterface;
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * MysqlExecutorTest.
 */
#[CoversClass(MysqlExecutor::class)]
class MysqlExecutorTest extends TestCase
{
    protected MysqlExecutor $MysqlExecutor;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->MysqlExecutor = new MysqlExecutor(Connection: new FakeConnection(), OperationType: OperationType::Export);
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::getBinaryName()
     */
    #[Test]
    #[TestWith(['mariadb-dump', OperationType::Export])]
    #[TestWith(['mariadb', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinaryName, OperationType $OperationType): void
    {
        $MysqlExecutor = new MysqlExecutor(Connection: new FakeConnection(), OperationType: $OperationType);
        $this->assertSame($expectedBinaryName, $MysqlExecutor->getBinaryName());
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::runProcess()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testRunProcessDeletesAuthFile(): void
    {
        Configure::write('DatabaseBackup.binaries.mariadb', '/path/to/mariadb');
        // The binary is never run: the command only reads the (empty) file to import
        Configure::write('DatabaseBackup.Mysql.import', 'true');

        $filename = TMP . 'empty_backup.sql';
        touch($filename);

        $MysqlExecutor = new MysqlExecutor(
            Connection: new FakeConnection(['driver' => Mysql::class]),
            OperationType: OperationType::Import,
        );
        $authFile = $MysqlExecutor->authFile;
        touch($authFile);

        $MysqlExecutor->runProcess($filename);

        $this->assertFileDoesNotExist($authFile);

        unlink($filename);
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::runProcess()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testRunProcessDeletesAuthFileOnFailure(): void
    {
        Configure::write('DatabaseBackup.binaries.mariadb', '/path/to/mariadb');
        // The binary is never run: the process fails earlier, because the shell cannot open the file to import
        Configure::write('DatabaseBackup.Mysql.import', 'true');

        $MysqlExecutor = new MysqlExecutor(
            Connection: new FakeConnection(['driver' => Mysql::class]),
            OperationType: OperationType::Import,
        );
        $authFile = $MysqlExecutor->authFile;
        touch($authFile);

        $this->expectException(ProcessFailedException::class);
        $MysqlExecutor->runProcess(TMP . 'no_existing_backup.sql');
        $this->assertFileDoesNotExist($authFile);
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::afterExport()
     */
    #[Test]
    public function testAfterExport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy(MysqlExecutor::class . '[deleteAuthFile]', [new FakeConnection(), OperationType::Export]);
        $MysqlExecutor->dispatchEvent('Backup.afterExport');

        $MysqlExecutor
            ->shouldHaveReceived('deleteAuthFile')
            ->withNoArgs()
            ->once();
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::afterImport()
     */
    #[Test]
    public function testAfterImport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy(MysqlExecutor::class . '[deleteAuthFile]', [new FakeConnection(), OperationType::Export]);
        $MysqlExecutor->dispatchEvent('Backup.afterImport');

        $MysqlExecutor
            ->shouldHaveReceived('deleteAuthFile')
            ->withNoArgs()
            ->once();
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::beforeExport()
     */
    #[Test]
    public function testBeforeExport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy(MysqlExecutor::class . '[writeAuthFile]', [new FakeConnection(), OperationType::Export]);
        $MysqlExecutor->dispatchEvent('Backup.beforeExport');

        $MysqlExecutor
            ->shouldHaveReceived('writeAuthFile')
            ->with('[mysqldump]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}')
            ->once();
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::beforeImport()
     */
    #[Test]
    public function testBeforeImport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy(MysqlExecutor::class . '[writeAuthFile]', [new FakeConnection(), OperationType::Export]);
        $MysqlExecutor->dispatchEvent('Backup.beforeImport');

        $MysqlExecutor
            ->shouldHaveReceived('writeAuthFile')
            ->with('[client]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}')
            ->once();
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::writeAuthFile()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testWriteAuthFile(): void
    {
        $Filesystem = Mockery::mock('overload:' . Filesystem::class);

        $Filesystem
            ->shouldReceive('touch')
            ->withArgs(function ($filename): bool {
                $this->assertStringStartsWith(TMP . 'auth', $filename);

                return true;
            })
            ->once();

        $Filesystem
            ->shouldReceive('chmod')
            ->withArgs(function ($filename, $mode): bool {
                $this->assertStringStartsWith(TMP . 'auth', $filename);
                $this->assertSame(0600, $mode);

                return true;
            })
            ->once();

        $Filesystem
            ->shouldReceive('dumpFile')
            ->withArgs(function ($filename, $content): bool {
                $this->assertStringStartsWith(TMP . 'auth', $filename);
                $this->assertSame('my_username_my_password_my_hostname', $content);

                return true;
            })
            ->once();

        $Filesystem
            ->shouldReceive('exists')
            ->withArgs(function ($filename): bool {
                $this->assertStringStartsWith(TMP . 'auth', $filename);

                return true;
            })
            ->once()
            ->andReturnTrue();

        $result = $this->MysqlExecutor->writeAuthFile('{{USER}}_{{PASSWORD}}_{{HOST}}');

        $this->assertTrue($result);
    }

    /**
     * If another listener stops the `before` event, the file with the authentication data is not written.
     *
     * @link \DatabaseBackup\Executor\MysqlExecutor::beforeExport()
     * @link \DatabaseBackup\Executor\MysqlExecutor::beforeImport()
     */
    #[Test]
    #[TestWith([OperationType::Export, 'Backup.beforeExport'])]
    #[TestWith([OperationType::Import, 'Backup.beforeImport'])]
    public function testAuthFileNotWrittenIfBeforeEventIsStopped(OperationType $OperationType, string $eventName): void
    {
        $MysqlExecutor = new MysqlExecutor(Connection: new FakeConnection(), OperationType: $OperationType);
        $MysqlExecutor->getEventManager()->on($eventName, function (EventInterface $Event): void {
            $Event->stopPropagation();
        });

        $MysqlExecutor->dispatchEvent($eventName);

        $this->assertFileDoesNotExist($MysqlExecutor->authFile);
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::writeAuthFile()
     */
    #[Test]
    public function testWriteAuthFileWithPlaceholdersInValues(): void
    {
        $MysqlExecutor = new MysqlExecutor(
            Connection: new FakeConnection(['password' => 'pass{{HOST}}{{USER}}']),
            OperationType: OperationType::Export,
        );

        $MysqlExecutor->writeAuthFile('{{USER}}_{{PASSWORD}}_{{HOST}}');

        $this->assertStringEqualsFile($MysqlExecutor->authFile, 'my_username_pass{{HOST}}{{USER}}_my_hostname');

        $MysqlExecutor->deleteAuthFile();
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::writeAuthFile()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testWriteAuthFilePermissions(): void
    {
        // With this umask, a file created without an explicit `chmod()` would be readable by other users
        umask(0022);

        $this->MysqlExecutor->writeAuthFile('{{PASSWORD}}');
        $authFile = $this->MysqlExecutor->authFile;

        $this->assertStringEqualsFile($authFile, 'my_password');
        $this->assertSame('0600', substr(sprintf('%o', fileperms($authFile)), -4));

        $this->MysqlExecutor->deleteAuthFile();
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::deleteAuthFile()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testDeleteAuthFile(): void
    {
        $authFile = $this->MysqlExecutor->authFile;

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('remove')
            ->with($authFile)
            ->once();

        $this->MysqlExecutor->deleteAuthFile();

        $this->assertNotEquals($this->MysqlExecutor->authFile, $authFile);
    }
}
