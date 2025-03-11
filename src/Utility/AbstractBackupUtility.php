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

use Cake\Core\App;
use Cake\Core\Configure;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\AbstractDriver;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

/**
 * AbstractBackupUtility.
 *
 * Provides the code common to the `BackupExport` and `BackupImport` classes.
 *
 * @property string $filename
 * @property int $timeout
 */
abstract class AbstractBackupUtility
{
    use BackupTrait;

    /**
     * @var string
     */
    protected string $filename;

    /**
     * @var int
     */
    protected int $timeout;

    /**
     * @var \DatabaseBackup\Driver\AbstractDriver
     */
    private AbstractDriver $Driver;

    /**
     * Magic method for reading data from inaccessible (protected or private).
     *
     * @param string $name Property name
     * @return mixed
     * @since 2.12.0
     * @throw \InvalidArgumentException With an undefined property.
     */
    public function __get(string $name): mixed
    {
        if (!property_exists($this, $name)) {
            throw new InvalidArgumentException('Undefined property: ' . $this::class . '::$' . $name);
        }

        return $this->{$name};
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
     * Gets the driver instance.
     *
     * @return \DatabaseBackup\Driver\AbstractDriver A driver instance
     * @throws \LogicException
     * @since 2.0.0
     */
    public function getDriver(): AbstractDriver
    {
        if (empty($this->Driver)) {
            $name = $this->getDriverName();
            /** @var class-string<\DatabaseBackup\Driver\AbstractDriver> $className */
            $className = App::classname('DatabaseBackup.' . $name, 'Driver');
            if (!$className) {
                throw new LogicException(__d('database_backup', 'The `{0}` driver does not exist', $name));
            }

            $this->Driver = new $className($this->getConnection());
        }

        return $this->Driver;
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
        $Process = Process::fromShellCommandline($command);
        $Process->setTimeout($this->timeout ?? Configure::readOrFail('DatabaseBackup.processTimeout'));
        $Process->run();

        return $Process;
    }
}
