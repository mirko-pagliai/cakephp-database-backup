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
use Cake\Utility\Text;
use DatabaseBackup\Compression;
use DatabaseBackup\Utility\BackupExport;
use Exception;
use Override;
use ValueError;
use function Cake\I18n\__d;

/**
 * Command to export a database backup.
 *
 * @since 2.6.0
 */
class ExportCommand extends Command
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->setDescription(__d('database_backup', 'Exports a database backup'));

        $parser->addOption('compression',  [
            'choices' => array_map(callback: 'lcfirst', array: array_column(
                array: array_filter(
                    array: Compression::cases(),
                    callback: fn (Compression $Compression): bool => $Compression->isValid(),
                ),
                column_key: 'name',
            )),
            'help' => __d('database_backup', 'Compression type. By default, no compression will be used'),
            'short' => 'c',
        ]);

        $parser->addOption('filename', [
            'help' => implode(' ', [
                __d(
                    'database_backup',
                    'Filename. It can be an absolute path and may contain patterns. The compression type will be automatically set.'
                ),
                __d(
                    'database_backup',
                    'Filenames can be relative to {0} (root of your app) or {1} (default target directory).',
                    '<comment>' . ROOT . '</comment>',
                    '<comment>' . Configure::readOrFail('DatabaseBackup.target'). '</comment>',
                ),
            ]),
            'short' => 'f',
        ]);

        return $parser;
    }

    /**
     * Executes the database backup export process.
     *
     * @param \Cake\Console\Arguments $args
     * @param \Cake\Console\ConsoleIo $io
     * @return int Returns the status code of the operation. `static::CODE_SUCCESS` indicates success.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $BackupExport = new BackupExport((string)$args->getOption('connection') ?: '');

            if ($args->getOption('timeout')) {
                $BackupExport->timeout((int)$args->getOption('timeout'));
            }

            if ($args->getOption('filename')) {
                $BackupExport->filename((string)$args->getOption('filename'));
            } elseif ($args->getOption('compression')) {
                $BackupExport->compression((string)$args->getOption('compression'));
            }

            $filename = $BackupExport->export();

            if (!$filename) {
                throw new StopException(
                    __d('database_backup', 'The `{0}` event stopped the operation', 'Backup.beforeExport')
                );
            }
            $io->success(__d('database_backup', 'Backup `{0}` has been exported', $this->makeRelativePath($filename)));
        } catch (Exception|ValueError $e) {
            $io->abort($e->getMessage());
        }

        return static::CODE_SUCCESS;
    }
}
