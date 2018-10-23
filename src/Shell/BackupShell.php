<?php
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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell
 */
namespace DatabaseBackup\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\Shell;
use Cake\I18n\Number;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupImport;
use DatabaseBackup\Utility\BackupManager;
use Exception;

/**
 * Shell to handle database backups
 */
class BackupShell extends Shell
{
    use BackupTrait;

    /**
     * @var \DatabaseBackup\Utility\BackupManager
     */
    protected $BackupManager;

    /**
     * Database configuration
     * @since 2.0.0
     * @var array
     */
    protected $config;

    /**
     * Driver containing all methods to export/import database backups
     *  according to the database engine
     * @since 2.0.0
     * @var object
     */
    protected $driver;

    /**
     * Constructor
     * @param \Cake\Console\ConsoleIo|null $io An io instance
     * @uses $BackupManager
     * @uses $config
     * @uses $driver
     */
    public function __construct(ConsoleIo $io = null)
    {
        parent::__construct($io);

        $this->BackupManager = new BackupManager;
        $this->config = $this->getConnection()->config();
        $this->driver = $this->getDriver($this->getConnection());
    }

    /**
     * Displays a header for the shell
     * @return void
     * @since 2.0.0
     * @uses $config
     * @uses $driver
     */
    protected function _welcome()
    {
        parent::_welcome();

        $this->out(__d('database_backup', 'Connection: {0}', $this->config['name']));
        $this->out(__d('database_backup', 'Driver: {0}', get_class_short_name($this->driver)));
        $this->hr();
    }

    /**
     * Deletes all backup files
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#deleteAll
     * @since 1.0.1
     * @uses DatabaseBackup\Utility\BackupManager::deleteAll()
     * @uses $BackupManager
     */
    public function deleteAll()
    {
        $deleted = $this->BackupManager->deleteAll();

        if (!$deleted) {
            $this->verbose(__d('database_backup', 'No backup has been deleted'));

            return;
        }

        foreach ($deleted as $file) {
            $this->verbose(__d('database_backup', 'Backup `{0}` has been deleted', rtr($file)));
        }

        $this->success(__d('database_backup', 'Deleted backup files: {0}', count($deleted)));
    }

    /**
     * Exports a database backup
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#export
     * @uses DatabaseBackup\Utility\BackupExport::compression()
     * @uses DatabaseBackup\Utility\BackupExport::export()
     * @uses DatabaseBackup\Utility\BackupExport::filename()
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
                $instance->compression($this->param('compression'));
            }

            //Exports
            $file = $instance->export();

            $this->success(__d('database_backup', 'Backup `{0}` has been exported', rtr($file)));

            //Sends via email
            if ($this->param('send')) {
                $this->send($file, $this->param('send'));
            }

            //Rotates
            if ($this->param('rotate')) {
                $this->rotate($this->param('rotate'));
            }
        } catch (Exception $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Imports a database backup
     * @param string $filename Filename. It can be an absolute path
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#import
     * @uses DatabaseBackup\Utility\BackupImport::filename()
     * @uses DatabaseBackup\Utility\BackupImport::import()
     */
    public function import($filename)
    {
        try {
            $file = (new BackupImport)->filename($filename)->import();

            $this->success(__d('database_backup', 'Backup `{0}` has been imported', rtr($file)));
        } catch (Exception $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Lists database backups
     * @return void
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#index
     * @uses DatabaseBackup\Utility\BackupManager::index()
     * @uses $BackupManager
     */
    public function index()
    {
        //Gets all backups
        $backups = $this->BackupManager->index();

        $this->out(__d('database_backup', 'Backup files found: {0}', count($backups)));

        if ($backups) {
            //Parses backups
            $backups = array_map(function ($backup) {
                $backup->size = Number::toReadableSize($backup->size);

                return array_values($backup->toArray());
            }, $backups);

            //Table headers
            $headers = [
                __d('database_backup', 'Filename'),
                __d('database_backup', 'Extension'),
                __d('database_backup', 'Compression'),
                __d('database_backup', 'Size'),
                __d('database_backup', 'Datetime'),
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
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#rotate
     * @uses DatabaseBackup\Utility\BackupManager::rotate()
     * @uses $BackupManager
     */
    public function rotate($keep)
    {
        try {
            //Gets deleted files
            $deleted = $this->BackupManager->rotate($keep);

            if (empty($deleted)) {
                $this->verbose(__d('database_backup', 'No backup has been deleted'));

                return;
            }

            foreach ($deleted as $file) {
                $this->verbose(__d('database_backup', 'Backup `{0}` has been deleted', $file->filename));
            }

            $this->success(__d('database_backup', 'Deleted backup files: {0}', count($deleted)));
        } catch (Exception $e) {
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
     * @uses DatabaseBackup\Utility\BackupManager::send()
     * @uses $BackupManager
     */
    public function send($filename, $recipient)
    {
        try {
            $this->BackupManager->send($filename, $recipient);

            $this->success(__d('database_backup', 'Backup `{0}` was sent via mail', rtr($filename)));
        } catch (Exception $e) {
            $this->abort($e->getMessage());
        }
    }

    /**
     * Gets the option parser instance and configures it
     * @return ConsoleOptionParser
     * @uses getValidCompressions()
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->setDescription(__d('database_backup', 'Shell to handle database backups'));

        $parser->addSubcommand('deleteAll', ['help' => __d('database_backup', 'Deletes all database backups')]);

        $parser->addSubcommand('export', [
            'help' => __d('database_backup', 'Exports a database backup'),
            'parser' => [
                'options' => [
                    'compression' => [
                        'choices' => $this->getValidCompressions(),
                        'help' => __d('database_backup', 'Compression type. By default, no compression will be used'),
                        'short' => 'c',
                    ],
                    'filename' => [
                        'help' => __d('database_backup', 'Filename. It can be an absolute path and may contain ' .
                            'patterns. The compression type will be automatically setted'),
                        'short' => 'f',
                    ],
                    'rotate' => [
                        'help' => __d('database_backup', 'Rotates backups. You have to indicate the number of backups you ' .
                            'want to keep. So, it will delete all backups that are older. By default, no backup will be deleted'),
                        'short' => 'r',
                    ],
                    'send' => [
                        'help' => __d('database_backup', 'Sends the backup file via email. You have ' .
                            'to indicate the recipient\'s email address'),
                        'short' => 's',
                    ],
                ],
            ],
        ]);

        $parser->addSubcommand('index', ['help' => __d('database_backup', 'Lists database backups')]);

        $parser->addSubcommand('import', [
            'help' => __d('database_backup', 'Imports a database backup'),
            'parser' => [
                'arguments' => [
                    'filename' => [
                        'help' => __d('database_backup', 'Filename. It can be an absolute path'),
                        'required' => true,
                    ],
                ],
            ],
        ]);

        $parser->addSubcommand('rotate', [
            'help' => __d('database_backup', 'Rotates backups'),
            'parser' => [
                'arguments' => [
                    'keep' => [
                        'help' => __d('database_backup', 'Number of backups you want to keep. So, it ' .
                            'will delete all backups that are older'),
                        'required' => true,
                    ],
                ],
            ],
        ]);

        $parser->addSubcommand('send', [
            'help' => __d('database_backup', 'Send a database backup via mail'),
            'parser' => [
                'arguments' => [
                    'filename' => [
                        'help' => __d('database_backup', 'Filename. It can be an absolute path'),
                        'required' => true,
                    ],
                    'recipient' => [
                        'help' => __d('database_backup', 'Recipient\'s email address'),
                        'required' => true,
                    ],
                ],
            ],
        ]);

        return $parser;
    }
}
