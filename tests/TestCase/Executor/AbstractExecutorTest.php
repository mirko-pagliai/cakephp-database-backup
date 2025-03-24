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

namespace DatabaseBackup\Test\TestCase\Executor;

use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * AbstractExecutorTest.
 */
#[CoversClass(AbstractExecutor::class)]
class AbstractExecutorTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Executor\AbstractExecutor&\PHPUnit\Framework\MockObject\MockObject
     */
    protected AbstractExecutor $Executor;

    /**
     * {@inheritDoc}
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->Executor = $this->getMockBuilder(AbstractExecutor::class)
            ->setConstructorArgs([ConnectionManager::get('test')])
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * @uses \DatabaseBackup\Executor\AbstractExecutor::implementedEvents()
     */
    #[Test]
    public function testImplementedEvents(): void
    {
        $this->assertNotEmpty($this->Executor->implementedEvents());
    }

    /**
     * @uses \DatabaseBackup\Executor\AbstractExecutor::getBinary()
     */
    #[Test]
    #[TestWith(['mysql'])]
    #[TestWith(['gzip'])]
    #[TestWith([Compression::Gzip])]
    #[TestWith([Compression::Bzip2])]
    public function testGetBinary(string|Compression $binaryName): void
    {
        $this->assertNotEmpty($this->Executor->getBinary($binaryName));
    }

    /**
     * @uses \DatabaseBackup\Executor\AbstractExecutor::getBinary()
     */
    #[Test]
    #[TestWith(['noExistingBinary', 'noExistingBinary'])]
    #[TestWith(['none', Compression::None])]
    public function testGetBinaryNoExistingBinary(string $expectedBinaryName, string|Compression $binaryName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Binary for `' . $expectedBinaryName . '` could not be found. You have to set its path manually');
        $this->Executor->getBinary($binaryName);
    }

    /**
     * @uses \DatabaseBackup\Executor\AbstractExecutor::getConfig()
     */
    #[Test]
    #[TestWith(['test', 'name'])]
    #[TestWith([null, 'noExisting'])]
    public function testGetConfig(?string $expectedConfig, string $configKey): void
    {
        $result = $this->Executor->getConfig($configKey);
        $this->assertSame($expectedConfig, $result);
    }
}
