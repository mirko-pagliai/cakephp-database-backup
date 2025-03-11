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

use DatabaseBackup\Compression;
use DatabaseBackup\Driver\AbstractDriver;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * AbstractDriverTest.
 *
 * @uses \DatabaseBackup\Driver\AbstractDriver
 */
class AbstractDriverTest extends TestCase
{
    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Driver\AbstractDriver::getBinary()
     */
    #[Test]
    #[TestWith(['mysql'])]
    #[TestWith(['gzip'])]
    #[TestWith([Compression::Gzip])]
    public function testGetBinary(string|Compression $binaryName): void
    {
        $Driver = $this->createPartialMock(AbstractDriver::class, []);

        $this->assertNotEmpty($Driver->getBinary($binaryName));
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Driver\AbstractDriver::getBinary()
     */
    #[Test]
    #[TestWith(['noExistingBinary', 'noExistingBinary'])]
    #[TestWith(['none', Compression::None])]
    public function testGetBinaryNoExistingBinary(string $expectedBinaryName, string|Compression $binaryName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Binary for `' . $expectedBinaryName . '` could not be found. You have to set its path manually');
        $this->createPartialMock(AbstractDriver::class, [])
            ->getBinary($binaryName);
    }
}
