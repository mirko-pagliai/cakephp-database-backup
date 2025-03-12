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
use Symfony\Component\Filesystem\Path;

/**
 * Command to delete all backup files.
 *
 * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-commands#delete_all
 * @deprecated 2.13.4 The `DeleteAllCommand` is deprecated. Will be removed in a future release
 */
class DeleteAllCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->setDescription(__d('database_backup', 'Deletes all database backups'));
    }

    /**
     * Deletes all backup files.
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        deprecationWarning(
            '2.13.4',
            'The `DeleteAllCommand` is deprecated. Will be removed in a future release'
        );

        $deletedFiles = BackupManager::deleteAll();
        if (!$deletedFiles) {
            $io->verbose(__d('database_backup', 'No backup has been deleted'));

            return;
        }

        foreach ($deletedFiles as $filename) {
            $filename = Path::isBasePath(ROOT, $filename) ? Path::makeRelative($filename, ROOT) : $filename;

            $io->verbose(__d('database_backup', 'Backup `{0}` has been deleted', $filename));
        }

        $io->success(__d('database_backup', 'Deleted backup files: {0}', count($deletedFiles)));
    }
}
