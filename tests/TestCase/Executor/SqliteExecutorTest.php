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

use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\TestSuite\DriverTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Cake\Database\Driver\Sqlite;
use PHPUnit\Framework\Attributes\Test;

/**
 * SqliteExecutorTest class.
 */
#[CoversClass(SqliteExecutor::class)]
class SqliteExecutorTest extends DriverTestCase
{
    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Executor\SqliteExecutor::beforeImport()
     */
    #[Test]
    public function testBeforeImport(): void
    {
        $Driver = $this->createPartialMock(Sqlite::class, ['connect', 'disconnect']);
        $Driver
            ->expects($this->once())
            ->method('connect');
        $Driver
            ->expects($this->once())
            ->method('disconnect');

        $SqliteExecutor = $this->getMockBuilder(SqliteExecutor::class)
            ->setConstructorArgs([
                $this->createConfiguredStub(ConnectionInterface::class, ['getDriver' => $Driver]),
            ])
            ->onlyMethods(['dropAllTables'])
            ->getMock();

        $SqliteExecutor
            ->expects($this->once())
            ->method('dropAllTables');

        $SqliteExecutor->dispatchEvent('Backup.beforeImport');
    }
}
