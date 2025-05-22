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

namespace App\Database\Driver;

use Cake\Database\Driver\Sqlite;
use Cake\Database\Schema\SchemaDialect;
use Cake\Database\Schema\SqliteSchemaDialect;
use Cake\Database\Schema\TableSchema;
use Mockery;
use PHPUnit\Framework\MockObject\MockBuilder;
use ReflectionClass;

/**
 * A fake driver for tests.
 */
class FakeDriver extends Sqlite
{
    public function enabled(): bool
    {
        return true;
    }

    /**
     * @throws \ReflectionException
     */
    public function schemaDialect(): SchemaDialect
    {
        return new ReflectionClass(SqliteSchemaDialect::class)->newInstanceWithoutConstructor();
    }
}
