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
use DatabaseBackup\Utility\BackupImport;
use Exception;
use Tools\Filesystem;

/**
 * Imports a database backup
 */
class ImportCommand extends Command
{
    /**
     * Hook method for defining this command's option parser
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->setDescription(__d('database_backup', 'Imports a database backup'))
            ->addArgument('filename', [
                'help' => __d('database_backup', 'Filename. It can be an absolute path'),
                'required' => true,
            ]);
    }

    /**
     * Imports a database backup
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#import
     * @throws \Cake\Console\Exception\StopException|\ReflectionException
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        parent::execute($args, $io);

        try {
            $file = (new BackupImport())->filename((string)$args->getArgument('filename'))->import();
            $io->success(__d('database_backup', 'Backup `{0}` has been imported', Filesystem::instance()->rtr($file)));
        } catch (Exception $e) {
            $io->error($e->getMessage());
            $this->abort();
        }
    }
}
