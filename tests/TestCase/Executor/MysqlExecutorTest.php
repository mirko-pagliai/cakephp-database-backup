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
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

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

    #[Test]
    #[TestWith(['mariadb-dump', OperationType::Export])]
    #[TestWith(['mariadb', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinarName, OperationType $OperationType): void
    {
        $MysqlExecutor = new MysqlExecutor(Connection: new FakeConnection(), OperationType: $OperationType);
        $this->assertSame($expectedBinarName, $MysqlExecutor->getBinaryName());
    }

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

    #[Test]
    public function testWriteAuthFile(): void
    {
        $content = '{{USER}}_{{PASSWORD}}_{{HOST}}';
        $expectedContent = 'my_username_my_password_my_hostname';

        $result = $this->MysqlExecutor->writeAuthFile($content);
        $this->assertTrue($result);

        $this->assertStringEqualsFile($this->MysqlExecutor->authFile, $expectedContent);
        unlink($this->MysqlExecutor->authFile);
    }

    #[Test]
    public function testDeleteAuthFile(): void
    {
        $authFile = $this->MysqlExecutor->authFile;
        touch($authFile);

        $this->assertFileExists($authFile);

        $this->MysqlExecutor->deleteAuthFile();

        $this->assertFileDoesNotExist($authFile);

        $this->assertNotEquals($this->MysqlExecutor->authFile, $authFile);
    }
}
