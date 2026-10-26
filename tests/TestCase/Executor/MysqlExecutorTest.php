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
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

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

        $this->MysqlExecutor = new MysqlExecutor(
            Connection: new FakeConnection(),
            OperationType: OperationType::Export,
        );
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
        Mockery::mock('overload:' . ExecutableFinder::class)
            ->shouldReceive('find')
            ->andReturn('/usr/bin/mariadb-dump');

        $Process = Mockery::mock('overload:' . Process::class)->shouldIgnoreMissing();
        $Process
            ->shouldReceive('fromShellCommandline')
            ->andReturnSelf();
        $Process
            ->shouldReceive('isSuccessful')
            ->andReturnTrue();

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('remove')
            ->with($this->MysqlExecutor->authFile)
            ->once();

        $this->MysqlExecutor->runProcess('backup.sql');
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::runProcess()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testRunProcessDeletesAuthFileOnFailure(): void
    {
        Mockery::mock('overload:' . ExecutableFinder::class)
            ->shouldReceive('find')
            ->andReturn('/usr/bin/mariadb-dump');

        $Process = Mockery::mock('overload:' . Process::class)->shouldIgnoreMissing();
        $Process
            ->shouldReceive('fromShellCommandline')
            ->andReturnSelf();
        $Process
            ->shouldReceive('isSuccessful')
            ->andReturnFalse();

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldReceive('remove')
            ->with($this->MysqlExecutor->authFile)
            ->once();

        $this->expectException(ProcessFailedException::class);
        $this->MysqlExecutor->runProcess('backup.sql');
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::afterExport()
     * @link \DatabaseBackup\Executor\MysqlExecutor::afterImport()
     */
    #[Test]
    #[TestWith([OperationType::Export, 'Backup.afterExport'])]
    #[TestWith([OperationType::Import, 'Backup.afterImport'])]
    public function testAfterEvents(OperationType $OperationType, string $eventName): void
    {
        $MysqlExecutor = new MysqlExecutor(Connection: new FakeConnection(), OperationType: $OperationType);
        $authFile = $MysqlExecutor->authFile;

        $MysqlExecutor->dispatchEvent($eventName);

        // `deleteAuthFile()` resets the property, so the path of the file with the authentication data changes
        $this->assertNotSame($authFile, $MysqlExecutor->authFile);
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::beforeExport()
     * @link \DatabaseBackup\Executor\MysqlExecutor::beforeImport()
     */
    #[Test]
    #[TestWith([OperationType::Export, 'Backup.beforeExport', '[mysqldump]'])]
    #[TestWith([OperationType::Import, 'Backup.beforeImport', '[client]'])]
    public function testBeforeEvents(OperationType $OperationType, string $eventName, string $expectedSection): void
    {
        $MysqlExecutor = new class (new FakeConnection(), $OperationType) extends MysqlExecutor {
            /**
             * @var array<string>
             */
            public array $contents = [];

            public function writeAuthFile(string $content): bool
            {
                $this->contents[] = $content;

                return true;
            }
        };

        $MysqlExecutor->dispatchEvent($eventName);

        $expectedContent = $expectedSection . PHP_EOL .
            'user="{{USER}}"' . PHP_EOL .
            'password="{{PASSWORD}}"' . PHP_EOL .
            'host="{{HOST}}"';
        $this->assertSame([$expectedContent], $MysqlExecutor->contents);
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
            ->withArgs(function (string $filename): bool {
                $this->assertStringStartsWith(TMP . 'auth', $filename);

                return true;
            })
            ->once();

        $Filesystem
            ->shouldReceive('chmod')
            ->withArgs(function (string $filename, $mode): bool {
                $this->assertStringStartsWith(TMP . 'auth', $filename);
                $this->assertSame(0600, $mode);

                return true;
            })
            ->once();

        $Filesystem
            ->shouldReceive('dumpFile')
            ->withArgs(function (string $filename, $content): bool {
                $this->assertStringStartsWith(TMP . 'auth', $filename);
                $this->assertSame('my_username_my_password_my_hostname', $content);

                return true;
            })
            ->once();

        $Filesystem
            ->shouldReceive('exists')
            ->withArgs(function (string $filename): bool {
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
        $MysqlExecutor->getEventManager()->on($eventName, static function (EventInterface $Event): void {
            $Event->stopPropagation();
        });

        $MysqlExecutor->dispatchEvent($eventName);

        $this->assertFileDoesNotExist($MysqlExecutor->authFile);
    }

    /**
     * @link \DatabaseBackup\Executor\MysqlExecutor::writeAuthFile()
     */
    #[Test]
    #[RunInSeparateProcess]
    public function testWriteAuthFileWithPlaceholdersInValues(): void
    {
        $MysqlExecutor = new MysqlExecutor(
            Connection: new FakeConnection(['password' => 'pass{{HOST}}{{USER}}']),
            OperationType: OperationType::Export,
        );

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldIgnoreMissing(true)
            ->shouldReceive('dumpFile')
            ->with($MysqlExecutor->authFile, 'my_username_pass{{HOST}}{{USER}}_my_hostname')
            ->once();

        $MysqlExecutor->writeAuthFile('{{USER}}_{{PASSWORD}}_{{HOST}}');
    }

    /**
     * The password is written between double quotes, so backslashes, double quotes and line breaks must be escaped.
     *
     * @link \DatabaseBackup\Executor\MysqlExecutor::writeAuthFile()
     */
    #[Test]
    #[RunInSeparateProcess]
    #[TestWith(['pass\\word', 'pass\\\\word'])]
    #[TestWith(['pass\\nword', 'pass\\\\nword'])]
    #[TestWith(['pass"word', 'pass\\"word'])]
    #[TestWith(["pass\nword", 'pass\\nword'])]
    #[TestWith(["pass\rword", 'pass\\rword'])]
    #[TestWith(['pa#ss word', 'pa#ss word'])]
    #[TestWith([null, ''])]
    public function testWriteAuthFileWithSpecialCharactersInPassword(?string $password, string $expectedPassword): void
    {
        $MysqlExecutor = new MysqlExecutor(
            Connection: new FakeConnection(compact('password')),
            OperationType: OperationType::Export,
        );

        Mockery::mock('overload:' . Filesystem::class)
            ->shouldIgnoreMissing(true)
            ->shouldReceive('dumpFile')
            ->with($MysqlExecutor->authFile, "password=\"{$expectedPassword}\"")
            ->once();

        $MysqlExecutor->writeAuthFile('password="{{PASSWORD}}"');
    }

    /**
     * Like the password, the username and the host are written between double quotes, so a `#` must not truncate them
     * and the characters that are special between double quotes must be escaped.
     *
     * @link \DatabaseBackup\Executor\MysqlExecutor::writeAuthFile()
     */
    #[Test]
    #[RunInSeparateProcess]
    #[TestWith(['username', 'my#user', 'my#user'])]
    #[TestWith(['username', 'my user', 'my user'])]
    #[TestWith(['username', 'my"user', 'my\\"user'])]
    #[TestWith(['username', 'my\\user', 'my\\\\user'])]
    #[TestWith(['host', 'my#host', 'my#host'])]
    #[TestWith(['host', 'my host', 'my host'])]
    #[TestWith(['host', 'my"host', 'my\\"host'])]
    #[TestWith(['host', 'my\\host', 'my\\\\host'])]
    public function testWriteAuthFileWithSpecialCharactersInUsernameAndHost(string $key, string $value, string $expectedValue): void
    {
        $MysqlExecutor = new MysqlExecutor(
            Connection: new FakeConnection([$key => $value]),
            OperationType: OperationType::Export,
        );

        $expected = [$key => $expectedValue] + ['username' => 'my_username', 'host' => 'my_hostname'];
        Mockery::mock('overload:' . Filesystem::class)
            ->shouldIgnoreMissing(true)
            ->shouldReceive('dumpFile')
            ->with(
                $MysqlExecutor->authFile,
                "user=\"{$expected['username']}\"" . PHP_EOL . "host=\"{$expected['host']}\"",
            )
            ->once();

        $MysqlExecutor->writeAuthFile('user="{{USER}}"' . PHP_EOL . 'host="{{HOST}}"');
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
