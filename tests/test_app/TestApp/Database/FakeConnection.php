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
        ];

        parent::__construct($config);
    }
}
