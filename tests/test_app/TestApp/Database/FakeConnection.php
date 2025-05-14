<?php
declare(strict_types=1);

namespace App\Database;

use App\Database\Driver\FakeDriver;
use Cake\Database\Connection;
use Cake\Database\Driver;

/**
 * A fake Connection for tests.
 */
class FakeConnection extends Connection
{
    public function __construct(array $config = [])
    {
    }

    public function getDriver(string $role = self::ROLE_WRITE): Driver
    {
        return new FakeDriver();
    }
}