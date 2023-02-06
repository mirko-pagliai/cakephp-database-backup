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
use DatabaseBackup\BackupTrait;

/**
 * Base class for console commands
 */
class Command extends BaseCommand
{
    use BackupTrait;

    /**
     * Implement this method with your command's logic
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @throws \ReflectionException
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        $io->out(__d('database_backup', 'Connection: {0}', $this->getConnection()->config()['name']));
        $io->out(__d('database_backup', 'Driver: {0}', $this->getDriverName()));
        $io->hr();
    }
}
