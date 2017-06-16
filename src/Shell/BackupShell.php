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

use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\I18n\Number;
use MysqlBackup\BackupTrait;
use MysqlBackup\Utility\BackupExport;
use MysqlBackup\Utility\BackupImport;
use MysqlBackup\Utility\BackupManager;

/**
 * Shell to handle database backups
 */
class BackupShell extends Shell
{
    use BackupTrait;

    /**
     * @var \MysqlBackup\Utility\BackupManager
     */
    protected $BackupManager;

    /**
     * Constructor
     * @param \Cake\Console\ConsoleIo|null $io An io instance
     * @uses $BackupManager
     */
    public function __construct(ConsoleIo $io = null)
    {
        parent::__construct($io);

        $this->BackupManager = new BackupManager;
    }

    /**
     * Deletes all backup files
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell#deleteAll
     * @since 1.0.1
     * @uses MysqlBackup\Utility\BackupManager::deleteAll()
     * @uses $BackupManager
     */
    public function deleteAll()
    {
        $deleted = $this->BackupManager->deleteAll();

        if (!$deleted) {
            $this->verbose(__d('mysql_backup', 'No backup has been deleted'));

            return;
        }

        foreach ($deleted as $file) {
            $this->verbose(__d('mysql_backup', 'Backup `{0}` has been deleted', rtr($file)));
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
     * @uses send()
     */
    public function export()
    {
        try {
            $instance = new BackupExport;

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
            $file = $instance->export();

            $this->success(__d('mysql_backup', 'Backup `{0}` has been exported', rtr($file)));

            //Sends via email
            if ($this->param('send')) {
                $this->send($file, $this->param('send'));
            }

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
            $file = (new BackupImport)->filename($filename)->import();

            $this->success(__d('mysql_backup', 'Backup `{0}` has been imported', rtr($file)));
        } catch (\Exception $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Lists database backups
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-mysql-backup/wiki/How-to-use-the-BackupShell#index
     * @uses MysqlBackup\Utility\BackupManager::index()
     * @uses $BackupManager
     */
    public function index()
    {
        //Gets all backups
        $backups = $this->BackupManager->index();

        $this->out(__d('mysql_backup', 'Backup files found: {0}', count($backups)));

        if ($backups) {
            //Parses backups
            $backups = collection($backups)
                ->map(function ($backup) {
                    if (isset($backup->compression) && !$backup->compression) {
                        $backup->compression = __d('mysql_backup', 'none');
                    }

                    $backup->size = Number::toReadableSize($backup->size);

                    return array_values($backup->toArray());
                })
                ->toArray();

            //Table headers
            $headers = [
                __d('mysql_backup', 'Filename'),
                __d('mysql_backup', 'Extension'),
                __d('mysql_backup', 'Compression'),
                __d('mysql_backup', 'Size'),
                __d('mysql_backup', 'Datetime'),
            ];

            $this->helper('table')->output(array_merge([$headers], $backups));
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
     * @uses $BackupManager
     */
    public function rotate($keep)
    {
        try {
            //Gets deleted files
            $deleted = $this->BackupManager->rotate($keep);

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
     * Sends a backup file via email
     * @param string $filename Filename of the backup that you want to send via
     *  email. The path can be relative to the backup directory
     * @param string $recipient Recipient's email address
     * @return void
     * @since 1.1.0
     * @uses MysqlBackup\Utility\BackupManager::send()
     * @uses $BackupManager
     */
    public function send($filename, $recipient)
    {
        try {
            $this->BackupManager->send($filename, $recipient);

            $this->success(__d('mysql_backup', 'Backup `{0}` was sent via mail', rtr($filename)));
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
                    'compression' => [
                        'choices' => array_keys($this->getValidCompressions()),
                        'help' => __d('mysql_backup', 'Compression type. By default, no compression will be used'),
                        'short' => 'c',
                    ],
                    'filename' => [
                        'help' => __d('mysql_backup', 'Filename. It can be an absolute path and may contain ' .
                            'patterns. The compression type will be automatically setted'),
                        'short' => 'f',
                    ],
                    'rotate' => [
                        'help' => __d('mysql_backup', 'Rotates backups. You have to indicate the number of backups you ' .
                            'want to keep. So, it will delete all backups that are older. By default, no backup will be deleted'),
                        'short' => 'r',
                    ],
                    'send' => [
                        'help' => __d('mysql_backup', 'Sends the backup file via email. You have ' .
                            'to indicate the recipient\'s email address'),
                        'short' => 's',
                    ],
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

        $parser->addSubcommand('send', [
            'help' => __d('mysql_backup', 'Send a database backup via mail'),
            'parser' => [
                'arguments' => [
                    'filename' => [
                        'help' => __d('mysql_backup', 'Filename. It can be an absolute path'),
                        'required' => true,
                    ],
                    'recipient' => [
                        'help' => __d('mysql_backup', 'Recipient\'s email address'),
                        'required' => true,
                    ],
                ],
            ],
        ]);

        return $parser;
    }
}
