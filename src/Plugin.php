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

namespace DatabaseBackup;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use DatabaseBackup\Command\DeleteAllCommand;
use DatabaseBackup\Command\ExportCommand;
use DatabaseBackup\Command\ImportCommand;
use DatabaseBackup\Command\IndexCommand;
use DatabaseBackup\Command\RotateCommand;
use DatabaseBackup\Command\SendCommand;

/**
 * Plugin class
 */
class Plugin extends BasePlugin
{
    /**
     * Add console commands for the plugin
     * @param \Cake\Console\CommandCollection $commands The command collection to update
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->add('database_backup.delete_all', DeleteAllCommand::class)
            ->add('database_backup.export', ExportCommand::class)
            ->add('database_backup.import', ImportCommand::class)
            ->add('database_backup.index', IndexCommand::class)
            ->add('database_backup.rotate', RotateCommand::class)
            ->add('database_backup.send', SendCommand::class);
    }
}
