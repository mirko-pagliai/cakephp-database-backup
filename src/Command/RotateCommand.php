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
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use DatabaseBackup\Utility\BackupManager;
use Exception;

/**
 * Command to rotate backups.
 *
 * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-commands#rotate
 * @deprecated 2.13.5 The `RotateCommand` is deprecated. Will be removed in a future release
 */
class RotateCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription(__d('database_backup', 'Rotates backups'))
            ->addArgument('keep', [
                'help' => __d(
                    'database_backup',
                    'Number of backups you want to keep. So, it will delete all backups that are older'
                ),
                'required' => true,
            ]);
    }

    /**
     * Rotates backups.
     *
     * You have to indicate the number of backups you want to keep. So, it will
     *  delete all backups that are older. By default, no backup will be deleted.
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @throws \Cake\Console\Exception\StopException
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        deprecationWarning(
            '2.13.5',
            'The `RotateCommand` is deprecated. Will be removed in a future release'
        );

        try {
            //Gets deleted files
            $files = BackupManager::rotate((int)$args->getArgument('keep'));

            if (!$files) {
                $io->verbose(__d('database_backup', 'No backup has been deleted'));

                return;
            }

            foreach ($files as $file) {
                $io->verbose(__d('database_backup', 'Backup `{0}` has been deleted', $file['filename']));
            }

            $io->success(__d('database_backup', 'Deleted backup files: {0}', count($files)));
        } catch (Exception $e) {
            $io->error($e->getMessage());
            $this->abort();
        }
    }
}
