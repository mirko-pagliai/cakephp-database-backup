<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 * @use         https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell
 */
namespace MysqlBackup\Shell;

use Cake\Console\Shell;
use Cake\I18n\Number;
use MysqlBackup\Utility\BackupExport;
use MysqlBackup\Utility\BackupImport;
use MysqlBackup\Utility\BackupManager;

/**
 * Shell to handle database backups
 */
class BackupShell extends Shell
{
    /**
     * Deletes all backup files
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell#deleteAll
     * @since 1.0.1
     * @uses MysqlBackup\Utility\BackupManager::deleteAll()
     */
    public function deleteAll()
    {
        $deleted = BackupManager::deleteAll();

        if (!$deleted) {
            $this->verbose(__d('mysql_backup', 'No backup has been deleted'));

            return;
        }

        foreach ($deleted as $file) {
            $this->verbose(__d('mysql_backup', 'Backup `{0}` has been deleted', $file));
        }

        $this->success(__d('mysql_backup', 'Deleted backup files: {0}', count($deleted)));
    }

    /**
     * Exports a database backup
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell#export
     * @uses MysqlBackup\Utility\BackupExport::compression()
     * @uses MysqlBackup\Utility\BackupExport::export()
     * @uses MysqlBackup\Utility\BackupExport::filename()
     * @uses rotate()
     */
    public function export()
    {
        try {
            $instance = new BackupExport();

            //Sets the output filename or the compression type.
            //Regarding the `rotate` option, the `BackupShell::rotate()` method
            //  will be called at the end, instead of `BackupExport::rotate()`
            if ($this->param('filename')) {
                $instance->filename($this->param('filename'));
            } elseif ($this->param('compression')) {
                //The `compression` option takes the value `none`, while the
                //  `BackupExport::compression` method takes the argument `false`
                if ($this->param('compression') === 'none') {
                    $this->params['compression'] = false;
                }

                $instance->compression($this->param('compression'));
            }

            //Exports
            $backup = $instance->export();

            $this->success(__d('mysql_backup', 'Backup `{0}` has been exported', $backup));

            //Rotates
            if ($this->param('rotate')) {
                $this->rotate($this->param('rotate'));
            }
        } catch (\Exception $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Imports a database backup
     * @param string $filename Filename. It can be an absolute path
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell#import
     * @uses MysqlBackup\Utility\BackupImport::filename()
     * @uses MysqlBackup\Utility\BackupImport::import()
     */
    public function import($filename)
    {
        try {
            $backup = (new BackupImport())->filename($filename)->import();

            $this->success(__d('mysql_backup', 'Backup `{0}` has been imported', $backup));
        } catch (\Exception $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Lists database backups
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell#index
     * @uses MysqlBackup\Utility\BackupManager::index()
     */
    public function index()
    {
        //Gets all files
        $files = BackupManager::index();

        $this->out(__d('mysql_backup', 'Backup files found: {0}', count($files)));

        if (!empty($files)) {
            //Parses files
            $files = array_map(function ($file) {
                if (isset($file->compression) && !$file->compression) {
                    $file->compression = __d('mysql_backup', 'none');
                }

                $file->size = Number::toReadableSize($file->size);

                return array_values((array)$file);
            }, $files);

            //Table headers
            $headers = [
                __d('mysql_backup', 'Filename'),
                __d('mysql_backup', 'Extension'),
                __d('mysql_backup', 'Compression'),
                __d('mysql_backup', 'Size'),
                __d('mysql_backup', 'Datetime'),
            ];

            $this->helper('table')->output(array_merge([$headers], $files));
        }
    }

    /**
     * Main command. Alias for `index()`
     * @return void
     * @uses index()
     */
    public function main()
    {
        $this->index();
    }

    /**
     * Rotates backups.
     *
     * You have to indicate the number of backups you want to keep. So, it will
     *  delete all backups that are older. By default, no backup will be deleted
     * @param int $keep Number of backups you want to keep
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell#rotate
     * @uses MysqlBackup\Utility\BackupManager::rotate()
     */
    public function rotate($keep)
    {
        try {
            //Gets deleted files
            $deleted = BackupManager::rotate($keep);

            if (empty($deleted)) {
                $this->verbose(__d('mysql_backup', 'No backup has been deleted'));

                return;
            }

            foreach ($deleted as $file) {
                $this->verbose(__d('mysql_backup', 'Backup `{0}` has been deleted', $file->filename));
            }

            $this->success(__d('mysql_backup', 'Deleted backup files: {0}', count($deleted)));
        } catch (\Exception $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Gets the option parser instance and configures it
     * @return ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->setDescription(__d('mysql_backup', 'Shell to handle database backups'));

        $parser->addSubcommand('deleteAll', ['help' => __d('mysql_backup', 'Deletes all database backups')]);

        $parser->addSubcommand('export', [
            'help' => __d('mysql_backup', 'Exports a database backup'),
            'parser' => [
                'options' => [
                    'filename' => [
                        'help' => __d('mysql_backup', 'Filename. It can be an absolute path and may contain ' .
                            'patterns. The compression type will be automatically setted'),
                        'short' => 'f',
                    ],
                    'compression' => [
                        'choices' => ['gzip', 'bzip2', 'none'],
                        'help' => __d('mysql_backup', 'Compression type. By default, no compression will be used'),
                        'short' => 'c'
                    ],
                    'rotate' => [
                        'help' => __d('mysql_backup', 'Rotates backups. You have to indicate the number of backups you ' .
                            'want to keep. So, it will delete all backups that are older. By default, no backup will be deleted'),
                        'short' => 'r'
                    ]
                ],
            ],
        ]);

        $parser->addSubcommand('index', ['help' => __d('mysql_backup', 'Lists database backups')]);

        $parser->addSubcommand('import', [
            'help' => __d('mysql_backup', 'Imports a database backup'),
            'parser' => [
                'arguments' => [
                    'filename' => [
                        'help' => __d('mysql_backup', 'Filename. It can be an absolute path'),
                        'required' => true,
                    ],
                ],
            ],
        ]);

        $parser->addSubcommand('rotate', [
            'help' => __d('mysql_backup', 'Rotates backups'),
            'parser' => [
                'arguments' => [
                    'keep' => [
                        'help' => __d('mysql_backup', 'Number of backups you want to keep. So, it ' .
                            'will delete all backups that are older'),
                        'required' => true,
                    ],
                ],
            ],
        ]);

        return $parser;
    }
}
