<?php
declare(strict_types=1);

namespace App\Database\Driver;

use Cake\Database\Driver;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Schema\SchemaDialect;

/**
 * A fake Driver for tests.
 */
class FakeDriver extends Driver
{
    public function connect(): void
    {
    }

    public function enabled(): bool
    {
    }

    public function disableForeignKeySQL(): string
    {
    }

    public function enableForeignKeySQL(): string
    {
    }

    public function schemaDialect(): SchemaDialect
    {
    }

    public function supports(DriverFeatureEnum $feature): bool
    {
    }
}
