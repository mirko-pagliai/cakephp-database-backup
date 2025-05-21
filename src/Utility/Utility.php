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

use Cake\Core\App;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Executor\Executor;
use DatabaseBackup\OperationType;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;
use function Cake\I18n\__d;

/**
 * Abstract utility.
 *
 * Provides methods and properties common to utility classes.
 */
abstract class Utility
{
    public ConnectionInterface $Connection {
        set (ConnectionInterface|string $Connection) {
            if (!$Connection instanceof ConnectionInterface) {
                $Connection = ConnectionManager::get($Connection);
            }

            $this->Connection = $Connection;
        }
        get => $this->Connection ?? ConnectionManager::get('default');
    }

    public Executor $Executor {
        get {
            if (empty($this->Executor)) {
                /**
                 * For example, for `Cake\Database\Driver\Mysql` the name will be `MySql`.
                 */
                $name = substr(strrchr($this->Connection->config()['driver'], '\\') ?: '', 1);

                /** @var class-string<\DatabaseBackup\Executor\Executor> $className */
                $className = App::classname('DatabaseBackup.' . $name . 'Executor', 'Executor');
                if (!$className) {
                    throw new InvalidArgumentException(__d('database_backup', 'The Executor class for the `{0}` driver does not exist', $name));
                }

                $this->Executor = new $className(Connection: $this->Connection, OperationType: $this->OperationType);
            }

            return $this->Executor;
        }
    }

    public int $timeOut = 60 {
        set (int $timeOut) {
            if ($timeOut < 0) {
                throw new InvalidArgumentException(__d('database_backup', 'The `timeOut` property must be greater than or equal to 0'));
            }
            $this->timeOut = $timeOut;
        }
    }

    /**
     * Construct.
     *
     * @param \DatabaseBackup\OperationType $OperationType
     */
    public function __construct(readonly protected OperationType $OperationType) {}

    /**
     * Converts a relative file path to an absolute path based on the specified target directory.
     *
     * @param string $path
     * @return string
     * @since 2.13.5
     */
    public function makeAbsolutePath(string $path): string
    {
        return Path::makeAbsolute($path, rtrim(ROOT, DS) . DS);
    }
}
