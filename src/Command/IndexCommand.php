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
use Cake\I18n\Number;
use DatabaseBackup\Compression;
use DatabaseBackup\Utility\BackupManager;

/**
 * Command to list database backups.
 *
 * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-commands#index
 */
class IndexCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription(__d('database_backup', 'Lists database backups'))
            ->addOption('reverse', [
                'boolean' => true,
                'help' => __d('database_backup', 'List database backups in reverse order (oldest first, then newest)'),
            ]);
    }

    /**
     * Lists database backups.
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        $backups = BackupManager::index();
        $io->out(__d('database_backup', 'Backup files found: {0}', $backups->count()));

        if ($backups->isEmpty()) {
            return;
        }

        $headers = [
            __d('database_backup', 'Filename'),
            __d('database_backup', 'Compression'),
            __d('database_backup', 'Size'),
            __d('database_backup', 'Datetime'),
        ];

        $rows = $backups
            ->map(fn (array $backup): array => [
                'basename' => $backup['filename'],
                'compression' => match ($backup['compression']) {
                    Compression::None => '',
                    default => lcfirst($backup['compression']->name)
                },
                'size' => Number::toReadableSize($backup['size']),
                'datetime' => $backup['datetime']->nice(),
            ])
            ->toList();

        if ($args->getOption('reverse')) {
            $rows = array_reverse($rows);
        }

        $io->helper('table')->output(array_merge([$headers], $rows));
    }
}
