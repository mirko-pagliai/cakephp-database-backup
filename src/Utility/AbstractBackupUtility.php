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
use function Cake\I18n\__d;

/**
 * AbstractBackupUtility.
 *
 * Provides the code common to the `BackupExport` and `BackupImport` classes.
 *
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
     * Magic `__call()` method.
     *
     * It provides all `getX()` methods to get properties.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @since 2.14.0
     * @throws \BadMethodCallException With a no existing property or method.
     * @phpstan-ignore missingType.iterableValue
     */
    public function __call(string $name, array $arguments = []): mixed
    {
        if (str_starts_with($name, 'get')) {
            $property = lcfirst(substr($name, 3));
            if (property_exists($this, $property)) {
                return $this->{$property};
            }
        }

        throw new BadMethodCallException('Method `' . $this::class . '::' . $name . '()` does not exist.');
    }

    /**
     * Makes the absolute path for a filename.
     *
     * @param string $filename
     * @return string
     * @since 2.13.5
     */
    public function makeAbsoluteFilename(string $filename): string
    {
        return Path::makeAbsolute($filename, Configure::readOrFail('DatabaseBackup.target'));
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
     * @param \Cake\Datasource\ConnectionInterface|null $Connection
     * @return \DatabaseBackup\Executor\AbstractExecutor
     * @since 2.14.0
     * @throws \InvalidArgumentException If the Executor class does not exist
     */
    public function getExecutor(?ConnectionInterface $Connection = null): AbstractExecutor
    {
        if (empty($this->Executor)) {
            $Connection = $Connection ?: ConnectionManager::get(Configure::readOrFail('DatabaseBackup.connection'));

            /**
             * For example, for `Cake\Database\Driver\Mysql` the name will be `MySql`.
             */
            $name = substr(strrchr($Connection->getDriver()::class, '\\') ?: '', 1);

            /** @var class-string<\DatabaseBackup\Executor\AbstractExecutor> $className */
            $className = App::classname('DatabaseBackup.' . $name . 'Executor', 'Executor');
            if (!$className) {
                throw new InvalidArgumentException(__d('database_backup', 'The Executor class for the `{0}` driver does not exist', $name));
            }

            $this->Executor = new $className(Connection: $Connection, name: $name);
        }

        return $this->Executor;
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
        $Process->setTimeout(timeout: $this->getTimeout() ?? Configure::readOrFail('DatabaseBackup.processTimeout'));
        $Process->run();

        return $Process;
    }
}
