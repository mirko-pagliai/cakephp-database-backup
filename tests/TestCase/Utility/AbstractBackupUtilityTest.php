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

use BadMethodCallException;
use DatabaseBackup\Driver\AbstractDriver;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\AbstractBackupUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * AbstractBackupUtilityTest.
 *
 * @uses \DatabaseBackup\Utility\AbstractBackupUtility
 */
class AbstractBackupUtilityTest extends TestCase
{
    /**
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::__call()
     */
    #[Test]
    public function testMagicCallMethod(): void
    {
        $Utility = new class extends AbstractBackupUtility {
            public function __construct()
            {
                $this->filename = 'thisIsAFilename';
            }

            public function filename(string $filename): AbstractBackupUtility
            {
                $this->filename = $filename;

                return $this;
            }
        };

        $this->assertSame('thisIsAFilename', $Utility->getFilename());
    }

    /**
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::__call()
     */
    #[Test]
    #[TestWith(['getNoExistingProperty'])]
    #[TestWith(['noExistingMethod'])]
    public function testMagicCallMethodWithNoExistingMethod(string $noExistingMethod): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches('/^Method `MockObject_AbstractBackupUtility_\w+::' . $noExistingMethod . '\(\)` does not exist\.$/');
        $Utility = $this->getMockBuilder(AbstractBackupUtility::class)
            ->onlyMethods(['filename'])
            ->getMock();
        $Utility->{$noExistingMethod}();
    }

    /**
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::__get()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testMagicGetMethodIsDeprecated(): void
    {
        $Utility = $this->createPartialMock(AbstractBackupUtility::class, ['filename']);
        $Utility->timeout(3);

        $this->deprecated(function () use ($Utility): void {
            $Utility->timeout;
        });
    }

    /**
     * @test
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::getDriver()
     */
    public function testGetDriver(): void
    {
        $Utility = $this->getMockBuilder(AbstractBackupUtility::class)
            ->getMock();
        $this->assertInstanceOf(AbstractDriver::class, $Utility->getDriver());

        $this->expectExceptionMessage('The `noExistingDriver` driver does not exist');
        $Utility = $this->getMockBuilder(AbstractBackupUtility::class)
            ->onlyMethods(['getDriverName', 'filename'])
            ->getMock();
        $Utility->method('getDriverName')->willReturn('noExistingDriver');
        $Utility->getDriver();
    }
}
