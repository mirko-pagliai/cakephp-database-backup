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

use DatabaseBackup\Driver\Sqlite;
use DatabaseBackup\TestSuite\DriverTestCase;

/**
 * SqliteTest class
 */
class SqliteTest extends DriverTestCase
{
    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!$this->Driver instanceof Sqlite) {
            $this->markTestIncomplete();
        }
    }

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable(): void
    {
        $expected = escapeshellarg($this->Driver->getBinary('sqlite3')) . ' ' . TMP . 'test.sq3 .dump > ' . escapeshellarg('backup.sql');
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable', ['backup.sql']));
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable(): void
    {
        $expected = escapeshellarg($this->Driver->getBinary('sqlite3')) . ' ' . TMP . 'test.sq3 < ' . escapeshellarg('backup.sql');
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable', ['backup.sql']));
    }
}
