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

use App\Database\Driver\FakeDriver;
use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\Schema\Collection;
use Cake\Database\Schema\CollectionInterface as SchemaCollectionInterface;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;

/**
 * A fake connection for tests.
 */
class FakeConnection extends Connection
{
    public function __construct(array $config = [])
    {
        $config += [
            'name' => 'test',
            'driver' => FakeDriver::class,
            'database' => 'my_database',
            'host' => 'my_hostname',
            'username' => 'my_username',
            'password' => 'my_password',
        ];

        parent::__construct($config);
    }

    public function getDriver(string $role = self::ROLE_WRITE): Driver
    {
        /** @var \Cake\Database\Driver $Driver */
        $Driver = new $this->_config['driver']();

        return $Driver;
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
                     * @return array<string>
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
