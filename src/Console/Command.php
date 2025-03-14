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
 * @since       2.6.0
 */

namespace DatabaseBackup\Console;

use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Symfony\Component\Filesystem\Path;

/**
 * Base class for console commands.
 */
abstract class Command extends BaseCommand
{
    /**
     * Gets the `Connection`.
     *
     * @return \Cake\Datasource\ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return ConnectionManager::get(Configure::readOrFail('DatabaseBackup.connection'));
    }

    /**
     * Makes the relative path for a filename (relative to `ROOT`).
     *
     * @param string $filename
     * @return string
     * @since 2.13.5
     */
    public function makeRelativeFilename(string $filename): string
    {
        return Path::isBasePath(ROOT, $filename) ? Path::makeRelative($filename, ROOT) : $filename;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        $io->out(__d('database_backup', 'Connection: {0}', $this->getConnection()->config()['name']));
        $io->out(__d('database_backup', 'Driver: {0}', $this->getConnection()->config()['driver']));

        if ($args->getOption('timeout')) {
            $io->verbose(
                __d('database_backup', 'Timeout for shell commands: {0} seconds', $args->getOption('timeout'))
            );
        }

        $io->hr();
    }
}
