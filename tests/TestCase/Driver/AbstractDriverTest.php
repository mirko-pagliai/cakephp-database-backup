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

use DatabaseBackup\Driver\AbstractDriver;
use DatabaseBackup\TestSuite\TestCase;

/**
 * AbstractDriverTest
 */
class AbstractDriverTest extends TestCase
{
    /**
     * @test
     * @uses \DatabaseBackup\Driver\AbstractDriver::getBinary()
     */
    public function testGetBinary(): void
    {
        $Driver = $this->getMockForAbstractClass(AbstractDriver::class);
        $this->assertNotEmpty($Driver->getBinary('mysql'));

        $this->expectExceptionMessage('Binary for `noExistingBinary` could not be found. You have to set its path manually');
        $Driver->getBinary('noExistingBinary');
    }
}
