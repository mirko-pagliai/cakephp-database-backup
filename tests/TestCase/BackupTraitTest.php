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

namespace DatabaseBackup\Test\TestCase;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * BackupTraitTest class.
 */
class BackupTraitTest extends TestCase
{
    #[Test]
    #[TestWith(['test', ''])]
    #[TestWith(['test', 'test'])]
    #[TestWith(['fake', 'fake'])]
    public function testGetConnection(string $expectedConnectionName, string $connectionName): void
    {
        if ($connectionName == 'fake') {
            ConnectionManager::setConfig('fake', ['url' => 'mysql://root:password@localhost/my_database']);
        }

        $Trait = new class {
            use BackupTrait;
        };
        $Connection = $Trait->getConnection($connectionName);
        $this->assertInstanceof(Connection::class, $Connection);
        $this->assertSame($expectedConnectionName, $Connection->configName());
    }

    #[Test]
    public function testGetConnectionWithNoExistingConnection(): void
    {
        $Trait = new class {
            use BackupTrait;
        };

        $this->expectException(MissingDatasourceConfigException::class);
        $Trait->getConnection('noExisting');
    }

    #[Test]
    #[WithoutErrorHandler]
    public function testGetConnectionIsDeprecated(): void
    {
        $Trait = new class {
            use BackupTrait;
        };

        $this->deprecated(function () use ($Trait): void {
            $Trait->getConnection('default');
        });
    }

    #[Test]
    #[WithoutErrorHandler]
    public function testGetDriverNameIsDeprecated(): void
    {
        $Trait = new class {
            use BackupTrait;
        };

        $this->deprecated(function () use ($Trait): void {
            $Trait->getDriverName();
        });
    }
}
