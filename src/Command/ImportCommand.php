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
 */

namespace DatabaseBackup\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Exception\StopException;
use Cake\Core\Configure;
use DatabaseBackup\Utility\BackupImport;
use Exception;
use Override;
use ValueError;
use function Cake\I18n\__d;

/**
 * Command to import a database backup.
 *
 * @since 2.6.0
 */
class ImportCommand extends Command
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->setDescription(__d('database_backup', 'Imports a database backup'));

        $parser->addArgument('filename', [
            'help' => implode(' ', [
                __d('database_backup', 'Filename. It can be an absolute path.'),
                __d(
                    'database_backup',
                    'Filenames can be relative to {0} (root of your app) or {1} (default target directory).',
                    '<comment>' . ROOT . '</comment>',
                    '<comment>' . Configure::readOrFail('DatabaseBackup.target'). '</comment>',
                ),
            ]),
            'required' => true,
        ]);

        return $parser;
    }

    /**
     * Imports a database backup.
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int Returns the status code of the operation. `static::CODE_SUCCESS` indicates success.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $BackupImport = new BackupImport((string)$args->getOption('connection') ?: '');

            if ($args->getOption('timeout')) {
                $BackupImport->timeout((int)$args->getOption('timeout'));
            }

            $BackupImport->filename($this->makeAbsolutePath((string)$args->getArgument('filename')));

            $filename = $BackupImport->import();

            if (!$filename) {
                throw new StopException(
                    __d('database_backup', 'The `{0}` event stopped the operation', 'Backup.beforeImport')
                );
            }
            $io->success(__d('database_backup', 'Backup `{0}` has been imported', $this->makeRelativePath($filename)));
        } catch (Exception|ValueError $e) {
            $io->abort($e->getMessage());
        }

        return static::CODE_SUCCESS;
    }
}
