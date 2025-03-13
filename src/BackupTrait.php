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

namespace DatabaseBackup;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;

/**
 * A trait that provides some methods used by all other classes.
 */
trait BackupTrait
{
    /**
     * Gets the `Connection` instance.
     *
     * You can pass the name of the connection. By default, the connection set in the configuration will be used.
     *
     * @param string $name Connection name
     * @return \Cake\Datasource\ConnectionInterface
     */
    public static function getConnection(string $name = ''): ConnectionInterface
    {
        return ConnectionManager::get($name ?: Configure::readOrFail('DatabaseBackup.connection'));
    }

    /**
     * Gets the driver name, according to the connection.
     *
     * @return string Driver name
     * @since 2.9.2
     * @deprecated 2.14.0 the `BackupTrait::getDriverName()` method is deprecated. Will be removed in a future release
     */
    public function getDriverName(): string
    {
        deprecationWarning(
            '2.14.0',
            'The `BackupTrait::getDriverName()` method is deprecated. Will be removed in a future release'
        );

        $className = get_class($this->getConnection()->getDriver());

        return substr(strrchr($className, '\\') ?: '', 1);
    }
}
