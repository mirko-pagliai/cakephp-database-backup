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
use BadMethodCallException;
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
    public static function setUpBeforeClass(): void
    {
        ConnectionManager::setConfig('test', new FakeConnection());
        ConnectionManager::setConfig('test_another_connection', new FakeConnection(['name' => 'test_another_connection']));
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        ConnectionManager::drop('test');
        ConnectionManager::drop('test_another_connection');
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Utility = new class (OperationType: OperationType::Export) extends Utility {
        };
    }

    /**
     * @link \DatabaseBackup\Utility\Utility::$connection
     */
    #[Test]
    #[TestWith([new FakeConnection(), 'test'])]
    #[TestWith(['test', 'test'])]
    #[TestWith(['test_another_connection', 'test_another_connection'])]
    public function testConnectionProperty(string|ConnectionInterface $connection, string $expectedNameConnection): void
    {
        //Default value, without calling the setter
        $this->assertSame('test', $this->Utility->Connection->config()['name']);

        $this->Utility->Connection = $connection;

        $result = $this->Utility->Connection;

        $this->assertInstanceOf(ConnectionInterface::class, $result);
        $this->assertSame($expectedNameConnection, $result->config()['name']);
    }

    /**
     * @link \DatabaseBackup\Utility\Utility::$Executor
     */
    #[Test]
    public function testExecutorProperty(): void
    {
        $this->Utility->Connection = new FakeConnection();

        $result = $this->Utility->Executor;
        $this->assertInstanceOf(Executor::class, $result);
    }

    /**
     * @link \DatabaseBackup\Utility\Utility::$Executor
     */
    #[Test]
    public function testExecutorPropertySetExecutor(): void
    {
        $Executor = new FakeExecutor();

        $this->Utility->Executor = $Executor;
        $this->assertSame($Executor, $this->Utility->Executor);
    }

    /**
     * @link \DatabaseBackup\Utility\Utility::$Executor
     */
    #[Test]
    public function testExecutorPropertyNoExistingExecutor(): void
    {
        $this->Utility->Connection = new FakeConnection(['driver' => 'Cake\Driver\NoExistingDriver']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Executor class for the `NoExistingDriver` driver does not exist');
        /** @phpstan-ignore-next-line */
        $this->Utility->Executor;
    }

    /**
     * @link \DatabaseBackup\Utility\Utility::$timeout
     */
    #[Test]
    #[TestWith([0])]
    #[TestWith([10])]
    public function testTimeoutProperty(int $timeout): void
    {
        //The default value of the property is obtained from the configuration
        Configure::write('DatabaseBackup.processTimeout', 45);
        $this->assertSame(45, $this->Utility->timeout);

        //The value set via the setter will take precedence over the general configuration
        $this->Utility->timeout = $timeout;
        $this->assertSame($timeout, $this->Utility->timeout);
    }

    /**
     * @link \DatabaseBackup\Utility\Utility::$timeout
     */
    #[Test]
    public function testTimeoutPropertyWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `timeout` property must be greater than or equal to 0');
        $this->Utility->timeout = -1;
    }

    /**
     * @link \DatabaseBackup\Utility\Utility::__call()
     */
    #[Test]
    public function testCallMagicMethod(): void
    {
        $this->Utility->connection('test_another_connection');

        $result = $this->Utility->Connection;

        $this->assertInstanceOf(ConnectionInterface::class, $result);
        $this->assertSame('test_another_connection', $result->config()['name']);
    }

    /**
     * Tests for `__call()` magic method, with a non-existing method.
     *
     * @link \DatabaseBackup\Utility\Utility::__call()
     */
    #[Test]
    public function testCallMagicMethodWithNoExistingMethod(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches('/^Method `DatabaseBackup\\\\Utility\\\\.+noExistingMethod\(\)` does not exist$/');
        /** @phpstan-ignore-next-line */
        $this->Utility->noExistingMethod();
    }

    /**
     * @return array<array{string, string}>
     */
    public static function makeAbsolutePathDataProvider(): array
    {
        $basePath = Configure::readOrFail('DatabaseBackup.target');

        return [
            [$basePath . 'relative_file_to_target.txt', 'relative_file_to_target.txt'],
            [$basePath . 'absolute_file_to_target.txt', $basePath . 'absolute_file_to_target.txt'],
            [TMP . 'absolute_tmp_file', TMP . 'absolute_tmp_file'],
        ];
    }

    /**
     * @link \DatabaseBackup\Utility\Utility::makeAbsolutePath()
     */
    #[Test]
    #[DataProvider('makeAbsolutePathDataProvider')]
    public function testMakeAbsolutePath(string $expectedAbsolutePath, string $path): void
    {
        $result = $this->Utility->makeAbsolutePath($path);

        $this->assertSame($expectedAbsolutePath, $result);
    }
}
