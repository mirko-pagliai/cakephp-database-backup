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
use Cake\Core\Configure;
use DatabaseBackup\Driver\AbstractDriver;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\AbstractBackupUtility;
use DatabaseBackup\Utility\BackupExport;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $Utility = new BackupExport();
        $this->assertIsInt($Utility->getRotate());
    }

    /**
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::__call()
     */
    #[Test]
    #[TestWith(['getNoExistingProperty'])]
    #[TestWith(['noExistingMethod'])]
    public function testMagicCallMethodWithNoExistingMethod(string $noExistingMethod): void
    {
        $Utility = $this->getMockBuilder(AbstractBackupUtility::class)
            ->onlyMethods(['filename'])
            ->getMock();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method `' . $Utility::class . '::' . $noExistingMethod . '()` does not exist.');
        $Utility->{$noExistingMethod}();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::__get()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testMagicGetMethodIsDeprecated(): void
    {
        $Utility = $this->createPartialMock(AbstractBackupUtility::class, ['filename']);
        $Utility->timeout(3);

        $this->deprecated(function () use ($Utility): void {
            // @phpstan-ignore property.protected, expr.resultUnused
            $Utility->timeout;
        });
    }

    public static function makeAbsoluteFilenameProvider(): Generator
    {
        yield [
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
            'file.txt',
        ];

        yield [
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
        ];

        yield [
            TMP . 'tmp_file',
            TMP . 'tmp_file',
        ];
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::makeAbsoluteFilename()
     */
    #[Test]
    #[DataProvider('makeAbsoluteFilenameProvider')]
    public function testMakeAbsoluteFilename(string $expectedAbsolutePath, string $path): void
    {
        $result = $this->createPartialMock(AbstractBackupUtility::class, ['filename'])
            ->makeAbsoluteFilename($path);
        $this->assertSame($expectedAbsolutePath, $result);
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
