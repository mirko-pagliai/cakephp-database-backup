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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility
 */

namespace DatabaseBackup\Utility;

use BadMethodCallException;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Executor\AbstractExecutor;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

/**
 * AbstractBackupUtility.
 *
 * Provides the code common to the `BackupExport` and `BackupImport` classes.
 *
 * @method \Cake\Datasource\ConnectionInterface getConnection()
 * @method string getFilename()
 * @method int getTimeout()
 */
abstract class AbstractBackupUtility
{
    /**
     * @var string
     */
    protected string $filename;

    /**
     * @var int
     */
    protected int $timeout = 0;

    /**
     * @var \DatabaseBackup\Executor\AbstractExecutor
     */
    private AbstractExecutor $Executor;

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected ConnectionInterface $Connection;

    /**
     * Construct.
     *
     * @param \Cake\Datasource\ConnectionInterface|string|null $Connection
     * @since 2.14.2
     */
    public function __construct(ConnectionInterface|string|null $Connection = null)
    {
        if (!$Connection instanceof ConnectionInterface) {
            $Connection = ConnectionManager::get($Connection ?: Configure::read(var: 'DatabaseBackup.connection', default: 'default'));
        }

        $this->Connection = $Connection;
    }

    /**
     * Magic `__call()` method.
     *
     * It provides all `getX()` methods to get properties.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @since 2.14.0
     * @phpstan-ignore missingType.iterableValue
     */
    public function __call(string $method, array $args): mixed
    {
        if (str_starts_with($method, 'get')) {
            $property = substr($method, 3);
            foreach ([$property, lcfirst($property)] as $property) {
                if (property_exists($this, $property)) {
                    return $this->{$property};
                }
            }
        }

        throw new BadMethodCallException('Method `' . $this::class . '::' . $method . '()` does not exist.');
    }

    /**
     * Magic method for reading data from inaccessible (protected or private).
     *
     * @param string $name Property name
     * @return mixed
     * @since 2.12.0
     * @throw \InvalidArgumentException With an undefined property.
     * @deprecated 2.14.0 accessing properties via the `__get()` method is deprecated. Will be removed in a future release
     */
    public function __get(string $name): mixed
    {
        deprecationWarning(
            '2.14.0',
            'Accessing properties via the `__get()` method is deprecated. Will be removed in a future release'
        );

        if (!property_exists($this, $name)) {
            throw new InvalidArgumentException('Undefined property: ' . $this::class . '::$' . $name);
        }

        return $this->{$name};
    }

    /**
     * Makes the absolute path.
     *
     * @param string $path
     * @return string
     * @since 2.13.5
     */
    public function makeAbsolutePath(string $path): string
    {
        return Path::makeAbsolute($path, Configure::readOrFail('DatabaseBackup.target'));
    }

    /**
     * Sets the filename.
     *
     * @param string $filename Filename. It can be an absolute path
     * @return self
     */
    abstract public function filename(string $filename): self;

    /**
     * Sets the timeout for shell commands.
     *
     * @param int $timeout Timeout in seconds
     * @return self
     * @since 2.12.0
     */
    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Gets the `Executor` instance according to the connection.
     *
     * @return \DatabaseBackup\Executor\AbstractExecutor
     * @since 2.14.0
     */
    public function getExecutor(): AbstractExecutor
    {
        if (empty($this->Executor)) {
            //For example `$driverName` is `Mysql`
            $driverName = substr(strrchr($this->Connection->getDriver()::class, '\\') ?: '', 1);

            /** @var class-string<\DatabaseBackup\Executor\AbstractExecutor> $executorClassName */
            $executorClassName = App::classname('DatabaseBackup.' . $driverName . 'Executor', 'Executor');
            if (!$executorClassName) {
                throw new InvalidArgumentException(__d('database_backup', 'The Executor class for the `{0}` driver does not exist', $driverName));
            }

            $this->Executor = new $executorClassName(Connection: $this->Connection);
        }

        return $this->Executor;
    }

    /**
     * Gets the driver instance.
     *
     * @return \DatabaseBackup\Executor\AbstractExecutor A driver instance
     * @since 2.0.0
     * @deprecated 2.14.0 the `AbstractExecutor::getDriver()` method is deprecated and will be removed in a future release. Use instead `getExecutor()`
     */
    public function getDriver(): AbstractExecutor
    {
        deprecationWarning(
            '2.14.0',
            'The `AbstractExecutor::getDriver()` method is deprecated and will be removed in a future release. Use instead `getExecutor()`'
        );

        return $this->getExecutor();
    }

    /**
     * Internal method to run and get a `Process` instance as a command-line to be run in a shell wrapper.
     *
     * @param string $command The command line to pass to the shell of the OS
     * @return \Symfony\Component\Process\Process
     * @see https://symfony.com/doc/current/components/process.html
     * @since 2.8.7
     */
    protected function getProcess(string $command): Process
    {
        $Process = Process::fromShellCommandline(command: $command);
        $Process->setTimeout(timeout: $this->getTimeout() ?: Configure::readOrFail('DatabaseBackup.processTimeout'));
        $Process->run();

        return $Process;
    }
}
