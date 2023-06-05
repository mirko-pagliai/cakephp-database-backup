<?php
/** @noinspection PhpUnhandledExceptionInspection */
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

use DatabaseBackup\Driver\Driver;
use DatabaseBackup\TestSuite\TestCase;

/**
 * DriverTest class.
 *
 * Performs test that are valid for each driver class, thus covering the methods of the abstract `Driver` class.
 * @covers \DatabaseBackup\Driver\Driver
 */
class DriverTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Driver\Driver
     */
    protected Driver $Driver;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Driver ??= $this->getMockForAbstractDriver();
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Driver::getBinary()
     */
    public function testGetBinary(): void
    {
        $this->assertStringEndsWith('mysql' . (IS_WIN ? '.exe' : ''), $this->Driver->getBinary('mysql'));

        //With a binary not available
        $this->expectExceptionMessage('Binary for `noExisting` could not be found. You have to set its path manually');
        $this->Driver->getBinary('noExisting');
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Driver::getConfig()
     */
    public function testGetConfig(): void
    {
        $this->assertNotEmpty($this->Driver->getConfig('name'));
        $this->assertNull($this->Driver->getConfig('noExistingKey'));
    }
}
