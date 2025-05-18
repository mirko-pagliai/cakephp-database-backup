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

use App\Database\FakeConnection;
use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\Utility;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * UtilityTest class.
 */
#[CoversClass(Utility::class)]
class UtilityTest extends TestCase
{
    protected Utility $Utility;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Utility = new class extends Utility {
        };
    }

    #[Test]
    #[TestWith([new FakeConnection()])]
    #[TestWith(['test'])]
    #[TestWith([null])]
    public function testConnectionProperty(mixed $connection): void
    {
        $this->Utility->Connection = $connection;

        $result = $this->Utility->Connection;
        $this->assertInstanceOf(ConnectionInterface::class, $result);
        $this->assertSame('test', $result->config()['name']);
    }

    #[Test]
    #[TestWith([0])]
    #[TestWith([10])]
    public function testTimeOutProperty(int $timeOut): void
    {
        $this->assertSame(60, $this->Utility->timeOut);

        $this->Utility->timeOut = $timeOut;
        $this->assertSame($timeOut, $this->Utility->timeOut);
    }

    #[Test]
    public function testTimeOutPropertyWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `timeOut` property must be greater than or equal to 0');
        $this->Utility->timeOut = -1;
    }
}
