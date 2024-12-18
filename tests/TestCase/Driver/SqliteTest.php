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

namespace DatabaseBackup\Test\TestCase\Driver;

use DatabaseBackup\TestSuite\DriverTestCase;

/**
 * SqliteTest class
 *
 * @uses \DatabaseBackup\Driver\Sqlite
 */
class SqliteTest extends DriverTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->getConnection()->config()['scheme'] !== 'sqlite') {
            $this->markTestSkipped('Skipping tests for sqlite, current driver is `' . $this->getConnection()->config()['scheme'] . '`');
        }
    }
}
