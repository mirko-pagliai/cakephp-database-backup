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

namespace DatabaseBackup\Utility;

use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;

/**
 * Abstract utility.
 *
 * Provides methods and properties common to utility classes.
 */
abstract class Utility
{
    public ConnectionInterface $Connection {
        set (ConnectionInterface|string|null $Connection) {
            if (!$Connection instanceof ConnectionInterface) {
                $Connection = ConnectionManager::get($Connection ?: 'default');
            }

            $this->Connection = $Connection;
        }
        get => $this->Connection;
    }
}
