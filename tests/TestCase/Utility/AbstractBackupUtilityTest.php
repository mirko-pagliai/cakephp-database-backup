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

use DatabaseBackup\Driver\AbstractDriver;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\AbstractBackupUtility;

/**
 * AbstractBackupUtilityTest
 *
 * @uses \DatabaseBackup\Utility\AbstractBackupUtility
 */
class AbstractBackupUtilityTest extends TestCase
{
    /**
     * @test
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::getDriver()
     */
    public function testGetDriver(): void
    {
        $Utility = $this->getMockForAbstractClass(AbstractBackupUtility::class);
        $this->assertInstanceOf(AbstractDriver::class, $Utility->getDriver());

        $this->expectExceptionMessage('The `noExistingDriver` driver does not exist');
        $Utility = $this->getMockForAbstractClass(
            AbstractBackupUtility::class,
            [],
            '',
            true,
            true,
            true,
            ['getDriverName']
        );
        $Utility->method('getDriverName')->willReturn('noExistingDriver');
        $Utility->getDriver();
    }
}
