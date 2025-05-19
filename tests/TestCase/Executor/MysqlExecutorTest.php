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
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * MysqlExecutorTest.
 */
#[CoversClass(MysqlExecutor::class)]
class MysqlExecutorTest extends TestCase
{
    #[Test]
    public function testAfterExport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy(MysqlExecutor::class . '[deleteAuthFile]');
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
        $MysqlExecutor = Mockery::spy(MysqlExecutor::class . '[deleteAuthFile]');
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
        $MysqlExecutor = Mockery::spy(MysqlExecutor::class . '[writeAuthFile]');
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
        $MysqlExecutor = Mockery::spy(MysqlExecutor::class . '[writeAuthFile]');
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

        $MysqlExecutor = new MysqlExecutor();
        $MysqlExecutor->Connection = new FakeConnection();

        $result = $MysqlExecutor->writeAuthFile($content);
        $this->assertTrue($result);

        $this->assertStringEqualsFile($MysqlExecutor->authFile, $expectedContent);
        unlink($MysqlExecutor->authFile);
    }

    #[Test]
    public function testDeleteAuthFile(): void
    {
        $MysqlExecutor = new MysqlExecutor();
        $authFile = $MysqlExecutor->authFile;
        touch($authFile);

        $this->assertFileExists($authFile);

        $MysqlExecutor->deleteAuthFile();

        $this->assertFileDoesNotExist($authFile);

        $this->assertNotEquals($MysqlExecutor->authFile, $authFile);
    }
}
