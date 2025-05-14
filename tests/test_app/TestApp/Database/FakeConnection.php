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
        if ($config) {
            parent::__construct($config);
        }
    }

    public function config(): array
    {
        return [
            'name' => 'test',
        ];
    }

    protected function createDrivers(array $config): array
    {
        return parent::createDrivers(['driver' => FakeDriver::class] + $config);
    }

    public function getDriver(string $role = self::ROLE_WRITE): Driver
    {
        if (!empty($this->_config['driver'])) {
            return new ($this->_config['driver'])();
        }

        return new FakeDriver();
    }
}
