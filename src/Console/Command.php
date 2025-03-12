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
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\BackupTrait;
use Symfony\Component\Filesystem\Path;

/**
 * Base class for console commands.
 */
abstract class Command extends BaseCommand
{
    use BackupTrait;

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
        $Connection = ConnectionManager::get(Configure::readOrFail('DatabaseBackup.connection'));

        $io->out(__d('database_backup', 'Connection: {0}', $Connection->config()['name']));
        $io->out(__d('database_backup', 'Driver: {0}', $Connection->config()['driver']));

        if ($args->getOption('timeout')) {
            $io->verbose(
                __d('database_backup', 'Timeout for shell commands: {0} seconds', $args->getOption('timeout'))
            );
        }

        $io->hr();
    }
}
