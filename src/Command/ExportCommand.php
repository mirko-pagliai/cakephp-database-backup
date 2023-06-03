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
use DatabaseBackup\Console\Command;
use DatabaseBackup\Utility\BackupExport;
use Exception;
use Tools\Exceptionist;

/**
 * Exports a database backup
 */
class ExportCommand extends Command
{
    /**
     * Hook method for defining this command's option parser
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->setDescription(__d('database_backup', 'Exports a database backup'))
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
            ]);
    }

    /**
     * Exports a database backup.
     *
     * This command uses `RotateCommand` and `SendCommand`.
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @throws \Cake\Console\Exception\StopException
     * @throws \ReflectionException
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#export
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        parent::execute($args, $io);

        try {
            $BackupExport = new BackupExport();
            //Sets the output filename or the compression type. Regarding the `rotate` option, the
            //`BackupShell::rotate()` method will be called at the end, instead of `BackupExport::rotate()`
            if ($args->getOption('filename')) {
                $BackupExport->filename((string)$args->getOption('filename'));
            } elseif ($args->getOption('compression')) {
                $BackupExport->compression((string)$args->getOption('compression'));
            }

            /**
             * Exports
             * @var string $file
             */
            $file = $BackupExport->export();
            Exceptionist::isTrue($file, __d('database_backup', 'The `{0}` event stopped the operation', 'Backup.beforeExport'));
            $io->success(__d('database_backup', 'Backup `{0}` has been exported', rtr($file)));

            $verbose = $args->getOption('verbose');
            $quiet = $args->getOption('quiet');

            //Sends via email
            if ($args->getOption('send')) {
                $SendCommand = new SendCommand();
                $SendCommand->execute(new Arguments(
                    [$file, (string)$args->getOption('send')],
                    compact('verbose', 'quiet'),
                    $SendCommand->getOptionParser()->argumentNames()
                ), $io);
            }

            //Rotates
            if ($args->getOption('rotate')) {
                $RotateCommand = new RotateCommand();
                $RotateCommand->execute(new Arguments(
                    [(string)$args->getOption('rotate')],
                    compact('verbose', 'quiet'),
                    $RotateCommand->getOptionParser()->argumentNames()
                ), $io);
            }
        } catch (Exception $e) {
            $io->error($e->getMessage());
            $this->abort();
        }
    }
}
