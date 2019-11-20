<?php

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

use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\TestCase;
use RuntimeException;

/**
 * DriverTest class
 */
class DriverTest extends TestCase
{
    /**
     * `Driver` instance
     * @var \DatabaseBackup\Driver\Driver
     */
    protected $Driver;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Driver = new Mysql($this->getConnection('test'));
    }

    /**
     * Test for `getBinary()` method
     * @test
     */
    public function testGetBinary()
    {
        $this->assertEquals(which('mysql'), $this->invokeMethod($this->Driver, 'getBinary', ['mysql']));

        //With a binary not available
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Binary for `noExisting` could not be found. You have to set its path manually');
        $this->invokeMethod($this->Driver, 'getBinary', ['noExisting']);
    }
}
