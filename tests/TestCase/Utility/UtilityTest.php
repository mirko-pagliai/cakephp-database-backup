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
use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Executor\Executor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\Utility;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * UtilityTest class.
 */
#[CoversClass(Utility::class)]
class UtilityTest extends TestCase
{
    protected Utility $Utility;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Utility = new class (OperationType: OperationType::Export) extends Utility {};
    }

    #[Test]
    #[TestWith([new FakeConnection()])]
    #[TestWith(['test'])]
    public function testConnectionProperty(mixed $connection): void
    {
        ConnectionManager::setConfig('test', new FakeConnection());

        //Default value, without calling the setter
        $this->assertSame('test', $this->Utility->Connection->config()['name']);

        $this->Utility->Connection = $connection;

        $result = $this->Utility->Connection;

        ConnectionManager::drop('test');

        $this->assertInstanceOf(ConnectionInterface::class, $result);
        $this->assertSame('test', $result->config()['name']);
    }

    #[Test]
    public function testExecutorProperty(): void
    {
        $this->Utility->Connection = new FakeConnection();

        $result = $this->Utility->Executor;
        $this->assertInstanceOf(Executor::class, $result);
    }

    #[Test]
    public function testExecutorPropertySetExecutor(): void
    {
        $Executor = new FakeExecutor();

        $this->Utility->Executor = $Executor;
        $this->assertSame($Executor, $this->Utility->Executor);
    }

    #[Test]
    public function testExecutorPropertyNoExistingExecutor(): void
    {
        $this->Utility->Connection = new FakeConnection(['driver' => 'Cake\Driver\NoExistingDriver']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Executor class for the `NoExistingDriver` driver does not exist');
        /** @phpstan-ignore-next-line */
        $this->Utility->Executor;
    }

    #[Test]
    #[TestWith([0])]
    #[TestWith([10])]
    public function testTimeoutProperty(int $timeOut): void
    {
        //The default value of the property is obtained from the configuration
        Configure::write('DatabaseBackup.processTimeout', 45);
        $this->assertSame(45, $this->Utility->timeout);

        //The value set via the setter will take precedence over the general configuration
        $this->Utility->timeout = $timeOut;
        $this->assertSame($timeOut, $this->Utility->timeout);
    }

    #[Test]
    public function testTimeoutPropertyWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `timeout` property must be greater than or equal to 0');
        $this->Utility->timeout = -1;
    }

    #[Test]
    public function testCallMagicMethodWithNoExistingMethod(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessageMatches('/^Method `DatabaseBackup\\\\Utility\\\\.+noExistingMethod\(\)` does not exist$/');
        /** @phpstan-ignore-next-line */
        $this->Utility->noExistingMethod();
    }

    /**
     * @return array<array{string, string}>
     */
    public static function testMakeAbsolutePathDataProvider(): array
    {
        $basePath = Configure::readOrFail('DatabaseBackup.target');

        return [
            [$basePath . 'relative_file_to_root.txt', 'relative_file_to_root.txt'],
            [$basePath . 'absolute_file_to_root.txt', $basePath . 'absolute_file_to_root.txt'],
            [TMP . 'absolute_tmp_file', TMP . 'absolute_tmp_file'],
        ];
    }

    #[Test]
    #[DataProvider('testMakeAbsolutePathDataProvider')]
    public function testMakeAbsolutePath(string $expectedAbsolutePath, string $path): void
    {
        $result = $this->Utility->makeAbsolutePath($path);

        $this->assertSame($expectedAbsolutePath, $result);
    }
}
