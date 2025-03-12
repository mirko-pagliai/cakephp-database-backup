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

use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\DriverTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MysqlTest class.
 */
#[CoversClass(Mysql::class)]
class MysqlTest extends DriverTestCase
{
    /**
     * Internal method to get a mock of `Mysql`.
     *
     * @param array<int, non-empty-string> $methods Methods you want to mock
     * @return \DatabaseBackup\Driver\Mysql&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMysqlMock(array $methods = []): Mysql
    {
        return $this->getMockBuilder(Mysql::class)
            ->setConstructorArgs([ConnectionManager::get('test')])
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        if ($this->getConnection()->config()['scheme'] !== 'mysql') {
            $this->markTestSkipped('Skipping tests for mysql, current driver is `' . $this->getConnection()->config()['scheme'] . '`');
        }

        parent::setUp();
    }

    /**
     * @uses \DatabaseBackup\Driver\Mysql::afterExport()
     */
    #[Test]
    public function testAfterExport(): void
    {
        $Mysql = $this->getMysqlMock(['deleteAuthFile']);
        $Mysql->expects($this->once())
            ->method('deleteAuthFile');

        $Mysql->dispatchEvent('Backup.afterExport');
    }

    /**
     * @uses \DatabaseBackup\Driver\Mysql::afterImport()
     */
    #[Test]
    public function testAfterImport(): void
    {
        $Mysql = $this->getMysqlMock(['deleteAuthFile']);
        $Mysql->expects($this->once())
            ->method('deleteAuthFile');

        $Mysql->dispatchEvent('Backup.afterImport');
    }

    /**
     * @uses \DatabaseBackup\Driver\Mysql::beforeExport()
     */
    #[Test]
    public function testBeforeExport(): void
    {
        $Mysql = $this->getMysqlMock(['writeAuthFile']);
        $Mysql->expects($this->once())
            ->method('writeAuthFile')
            ->with('[mysqldump]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}');

        $Mysql->dispatchEvent('Backup.beforeExport');
    }

    /**
     * @uses \DatabaseBackup\Driver\Mysql::beforeImport()
     */
    #[Test]
    public function testBeforeImport(): void
    {
        $Mysql = $this->getMysqlMock(['writeAuthFile']);
        $Mysql->expects($this->once())
            ->method('writeAuthFile')
            ->with('[client]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}');

        $Mysql->dispatchEvent('Backup.beforeImport');
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Driver\Mysql::deleteAuthFile()
     */
    #[Test]
    public function testDeleteAuthFile(): void
    {
        $expectedAuthFile = TMP . 'myAuthFile';

        $Filesystem = $this->createPartialMock(Filesystem::class, ['remove']);

        $Filesystem->expects($this->once())
            ->method('remove')
            ->with($expectedAuthFile);

        $Mysql = $this->getMysqlMock(['getFilesystem', 'getAuthFilePath']);
        $Mysql->method('getFilesystem')
            ->willReturn($Filesystem);
        $Mysql->method('getAuthFilePath')
            ->willReturn($expectedAuthFile);

        //Dispatches an event (any) that we are sure will call and return the `deleteAuthFile()` method.
        $result = $Mysql->dispatchEvent('Backup.afterExport');

        $this->assertNull($result->getResult());
    }
}
