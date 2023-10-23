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
use DatabaseBackup\Utility\BackupManager;
use Exception;

/**
 * Sends a backup file via email
 * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-commands#send
 */
class SendCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->setDescription(__d('database_backup', 'Send a database backup via mail'))
            ->addArguments([
                'filename' => [
                    'help' => __d('database_backup', 'Filename. It can be an absolute path'),
                    'required' => true,
                ],
                'recipient' => [
                    'help' => __d('database_backup', 'Recipient\'s email address'),
                    'required' => true,
                ],
            ]);
    }

    /**
     * Sends a backup file via email
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @throws \Cake\Console\Exception\StopException
     * @throws \ReflectionException
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        parent::execute($args, $io);

        try {
            BackupManager::send($args->getArgument('filename') ?: '', $args->getArgument('recipient') ?: '');
            $io->success(__d('database_backup', 'Backup `{0}` was sent via mail', rtr($args->getArgument('filename') ?: '')));
        } catch (Exception $e) {
            $io->error($e->getMessage());
            $this->abort();
        }
    }
}
