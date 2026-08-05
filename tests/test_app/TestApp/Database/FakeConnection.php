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

namespace App\Database;

use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Schema\Collection;
use Cake\Database\Schema\CollectionInterface as SchemaCollectionInterface;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;

/**
 * A fake connection for tests.
 *
 * This fake connection tries to simulate a connection with Sqlite, but without actually using its driver.
 */
class FakeConnection extends Connection
{
    protected Driver $Driver;

    public function __construct(array $config = [])
    {
        $this->Driver = new class extends Sqlite {
            public function enabled(): bool
            {
                return true;
            }
        };

        $config += [
            'name' => 'test',
            'driver' => 'Cake\Database\Driver\Sqlite',
            'database' => 'my_database',
            'host' => 'my_hostname',
            'username' => 'my_username',
            'password' => 'my_password',
        ];

        parent::__construct($config);
    }

    public function createDrivers(array $config): array
    {
        return [
            'read' => $this->Driver,
            'write' => $this->Driver,
        ];
    }

    public function getDriver(string $role = self::ROLE_WRITE): Driver
    {
        return $this->Driver;
    }

    public function getSchemaCollection(): SchemaCollectionInterface
    {
        return new class ($this) extends Collection
        {
            public function listTables(): array
            {
                return ['articles', 'comments'];
            }

            public function describe(string $name, array $options = []): TableSchemaInterface
            {
                return new class ($name) extends TableSchema {
                    /**
                     * @param \Cake\Database\Connection $connection
                     * @return array<int, string>
                     */
                    public function dropSql(Connection $connection): array
                    {
                        return ['DROP TABLE "' . $this->name() . '"'];
                    }
                };
            }
        };
    }
}
