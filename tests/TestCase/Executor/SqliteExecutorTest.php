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

use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\TestSuite\DriverTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * SqliteExecutorTest class.
 */
#[CoversClass(SqliteExecutor::class)]
class SqliteExecutorTest extends DriverTestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->getConnection()->config()['scheme'] !== 'sqlite') {
            $this->markTestSkipped('Skipping tests for sqlite, current driver is `' . $this->getConnection()->config()['scheme'] . '`');
        }
    }
}
