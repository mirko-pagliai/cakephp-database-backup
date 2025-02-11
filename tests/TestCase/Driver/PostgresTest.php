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
 * PostgresTest class.
 *
 * @uses \DatabaseBackup\Driver\Postgres
 */
class PostgresTest extends DriverTestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->getConnection()->config()['scheme'] !== 'postgres') {
            $this->markTestSkipped('Skipping tests for postgres, current driver is `' . $this->getConnection()->config()['scheme'] . '`');
        }
    }
}
