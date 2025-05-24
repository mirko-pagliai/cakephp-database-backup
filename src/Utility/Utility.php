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

use BadMethodCallException;
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

    public int $timeout = 60 {
        set (int $timeout) {
            if ($timeout < 0) {
                throw new InvalidArgumentException(__d('database_backup', 'The `timeout` property must be greater than or equal to 0'));
            }
            $this->timeout = $timeout;
        }
    }

    /**
     * Construct.
     *
     * @param \DatabaseBackup\OperationType $OperationType
     */
    public function __construct(readonly protected OperationType $OperationType, ConnectionInterface|string $Connection = '')
    {
        if ($Connection) {
            $this->Connection = $Connection;
        }
    }

    /**
     * Magic method. It provides the ability to set properties using methods.
     *
     * For example, `filename(string $filename)` will set the property `$filename`.
     * It also allows you to chain method calls and make the class fluent.
     *
     * These magic methods must be described in child classes, using the `@method` tag.
     *
     * @param string $name
     * @param array<array-key, mixed> $arguments
     * @return $this
     * @throws \BadMethodCallException
     */
    public function __call(string $name, array $arguments)
    {
        if (!property_exists($this, $name)) {
            throw new BadMethodCallException(sprintf('Method `%s::%s()` does not exist', $this::class, $name));
        }

        $this->{$name} = $arguments[0];

        return $this;
    }

    /**
     * Converts a relative file path to an absolute path based on the specified target directory.
     *
     * @param string $path
     * @return string
     * @since 2.13.5
     */
    public function makeAbsolutePath(string $path): string
    {
        if (Path::isAbsolute($path)) {
            return $path;
        }

        $absolutePath = Path::makeAbsolute($path, rtrim(ROOT, DS) . DS);

        return DS == '\\' ? str_replace(search: '/', replace: DS, subject: $absolutePath) : $absolutePath;
    }
}
