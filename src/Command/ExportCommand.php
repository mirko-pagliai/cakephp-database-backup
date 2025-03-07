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
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupManager;
use Exception;

/**
 * Command to export a database backup.
 *
 * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-commands#export
 */
final class ExportCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription(__d('database_backup', 'Exports a database backup'))
            ->addOptions([
                'compression' => [
                    'choices' => $this->getValidCompressions(),
                    'help' => __d('database_backup', 'Compression type. By default, no compression will be used'),
                    'short' => 'c',
                ],
                'filename' => [
                    'help' => __d('database_backup', 'Filename. It can be an absolute path and may contain ' .
                        'patterns. The compression type will be automatically set'),
                    'short' => 'f',
                ],
                'rotate' => [
                    'help' => __d('database_backup', 'Rotates backups. You have to indicate the number of backups ' .
                        'you want to keep. So, it will delete all backups that are older. By default, no backup ' .
                        'will be deleted'),
                    'short' => 'r',
                ],
                'send' => [
                    'help' => __d('database_backup', 'Sends the backup file via email. You have ' .
                        'to indicate the recipient\'s email address'),
                    'short' => 's',
                ],
                'timeout' => [
                    'help' => __d(
                        'database_backup',
                        'Timeout for shell commands. Default value: {0} seconds',
                        Configure::readOrFail('DatabaseBackup.processTimeout')
                    ),
                    'short' => 't',
                ],
            ]);
    }

    /**
     * Internal method to get a `BackupExport` instance.
     *
     * @return \DatabaseBackup\Utility\BackupExport
     */
    protected function getBackupExport(): BackupExport
    {
        return new BackupExport();
    }

    /**
     * Exports a database backup.
     *
     * This command uses the `SendCommand`.
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @throws \Cake\Console\Exception\StopException
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        parent::execute($args, $io);

        try {
            $BackupExport = $this->getBackupExport();

            //Sets the output filename or the compression type. Regarding the `rotate` option, the
            //`BackupShell::rotate()` method will be called at the end, instead of `BackupExport::rotate()`
            if ($args->getOption('filename')) {
                $BackupExport->filename((string)$args->getOption('filename'));
            } elseif ($args->getOption('compression')) {
                $BackupExport->compression((string)$args->getOption('compression'));
            }
            //Sets the timeout
            if ($args->getOption('timeout')) {
                $BackupExport->timeout((int)$args->getOption('timeout'));
            }

            $file = $BackupExport->export();
            if (!$file) {
                throw new StopException(
                    __d('database_backup', 'The `{0}` event stopped the operation', 'Backup.beforeExport')
                );
            }
            $io->success(__d('database_backup', 'Backup `{0}` has been exported', rtr($file)));

            //Sends via email and/or rotates. It keeps options `verbose` and `quiet`.
            $extraOptions = [];
            foreach (['verbose', 'quiet'] as $option) {
                if ($args->getOption($option)) {
                    $extraOptions[] = '--' . $option;
                }
            }
            if ($args->getOption('send')) {
                deprecationWarning(
                    '2.13.4',
                    'The `send` option for the `ExportCommand` is deprecated. Will be removed in a future release'
                );

                $this->executeCommand(
                    SendCommand::class,
                    array_merge([$file, (string)$args->getOption('send')], $extraOptions),
                    $io
                );
            }
            if ($args->getOption('rotate')) {
                $files = BackupManager::rotate((int)$args->getOption('rotate'));

                if ($files) {
                    foreach ($files as $file) {
                        $io->verbose(__d('database_backup', 'Backup `{0}` has been deleted', $file['filename']));
                    }

                    $io->success(__d('database_backup', 'Deleted backup files: {0}', count($files)));
                } else {
                    $io->verbose(__d('database_backup', 'No backup has been deleted'));
                }
            }
        } catch (Exception $e) {
            $io->abort($e->getMessage());
        }
    }
}
