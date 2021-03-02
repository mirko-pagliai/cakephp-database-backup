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
namespace DatabaseBackup\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Number;
use Cake\ORM\Entity;
use DatabaseBackup\Console\Command;
use DatabaseBackup\Utility\BackupManager;

/**
 * Lists database backups
 */
class IndexCommand extends Command
{
    /**
     * Hook method for defining this command's option parser
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->setDescription(__d('database_backup', 'Lists database backups'));
    }

    /**
     * Lists database backups
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#index
     * @uses \DatabaseBackup\Utility\BackupManager::index()
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        parent::execute($args, $io);

        //Gets all backups
        $backups = BackupManager::index();
        $io->out(__d('database_backup', 'Backup files found: {0}', $backups->count()));
        if ($backups->isEmpty()) {
            return;
        }

        $headers = [
            __d('database_backup', 'Filename'),
            __d('database_backup', 'Extension'),
            __d('database_backup', 'Compression'),
            __d('database_backup', 'Size'),
            __d('database_backup', 'Datetime'),
        ];
        $cells = $backups->map(function (Entity $backup) {
            return $backup->set('compression', $backup->get('compression') ?: '')
                ->set('datetime', $backup->get('datetime')->nice())
                ->set('size', Number::toReadableSize($backup->get('size')))
                ->toArray();
        });
        $io->helper('table')->output(array_merge([$headers], $cells->toList()));
    }
}
