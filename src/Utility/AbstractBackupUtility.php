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
use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Driver;
use Symfony\Component\Process\Process;
use Tools\Exceptionist;

/**
 * AbstractBackupUtility.
 *
 * Provides the code common to the `BackupExport` and `BackupImport` classes.
 */
abstract class AbstractBackupUtility
{
    use BackupTrait;

    /**
     * Filename where to export/import the database
     * @var string
     */
    protected string $filename;

    /**
     * Driver containing all methods to export/import database backups according to the connection
     * @var \DatabaseBackup\Driver\Driver
     */
    public Driver $Driver;

    /**
     * Sets the filename
     * @param string $filename Filename. It can be an absolute path
     * @return $this
     */
    abstract function filename(string $filename);

    /**
     * Gets the `Driver` instance, containing all methods to export/import database backups.
     *
     * You can pass a `Connection` instance. By default, the connection set in the configuration will be used.
     * @param \Cake\Datasource\ConnectionInterface|null $connection A `Connection` instance
     * @return \DatabaseBackup\Driver\Driver A `Driver` instance
     * @throws \ErrorException|\ReflectionException
     * @since 2.0.0
     */
    public function getDriver(?ConnectionInterface $connection = null): Driver
    {
        $connection = $connection ?: $this->getConnection();
        $name = $this->getDriverName($connection);
        /** @var class-string<\DatabaseBackup\Driver\Driver> $Driver */
        $Driver = App::classname('DatabaseBackup.' . $name, 'Driver');
        Exceptionist::isTrue($Driver, __d('database_backup', 'The `{0}` driver does not exist', $name));

        return new $Driver($connection);
    }

    /**
     * Internal method to run and get a `Process` instance as a command-line to be run in a shell wrapper.
     * @param string $command The command line to pass to the shell of the OS
     * @return \Symfony\Component\Process\Process
     * @since 2.8.7
     */
    protected function getProcess(string $command): Process
    {
        $Process = Process::fromShellCommandline($command);
        $Process->setTimeout(Configure::read('DatabaseBackup.processTimeout', 60));
        $Process->run();

        return $Process;
    }
}
