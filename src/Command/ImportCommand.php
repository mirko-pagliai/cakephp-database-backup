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
use Cake\Console\Exception\StopException;
use Cake\Core\Configure;
use DatabaseBackup\Console\Command;
use DatabaseBackup\Utility\BackupImport;
use Exception;

/**
 * Command to import a database backup.
 *
 * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-commands#import
 */
class ImportCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription(__d('database_backup', 'Imports a database backup'))
            ->addArgument('filename', [
                'help' => __d('database_backup', 'Filename. It can be an absolute path'),
                'required' => true,
            ])
            ->addOption('timeout', [
                'help' => __d(
                    'database_backup',
                    'Timeout for shell commands. Default value: {0} seconds',
                    Configure::readOrFail('DatabaseBackup.processTimeout')
                ),
                'short' => 't',
            ]);
    }

    /**
     * Internal method to get a `BackupImport` instance.
     *
     * @return \DatabaseBackup\Utility\BackupImport
     */
    protected function getBackupImport(): BackupImport
    {
        return new BackupImport();
    }

    /**
     * Imports a database backup.
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        parent::execute($args, $io);

        try {
            $BackupImport = $this->getBackupImport();

            $BackupImport->filename((string)$args->getArgument('filename'));

            //Sets the timeout
            if ($args->getOption('timeout')) {
                $BackupImport->timeout((int)$args->getOption('timeout'));
            }

            $file = $BackupImport->import();
            if (!$file) {
                throw new StopException(
                    __d('database_backup', 'The `{0}` event stopped the operation', 'Backup.beforeImport')
                );
            }
            $io->success(__d('database_backup', 'Backup `{0}` has been imported', rtr($file)));
        } catch (Exception $e) {
            $io->abort($e->getMessage());
        }
    }
}
