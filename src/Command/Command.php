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

use Cake\Command\Command as CakeCommand;
use Cake\Console\ConsoleOptionParser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use function Cake\I18n\__d;

/**
 * Base class for console commands.
 *
 * @since 2.6.0
 */
abstract class Command extends CakeCommand
{
    /**
     * Configures and returns the console option parser for a command.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The console option parser instance to configure.
     * @return \Cake\Console\ConsoleOptionParser The configured console option parser.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption(name: 'connection', options: [
            'help' => __d(
                'database_backup',
                'Name of the alternative connection to use, for example if you are not using the default connection',
            ),
        ]);

        $parser->addOption(name: 'timeout', options: [
            'help' => __d('database_backup', 'Timeout for shell commands'),
            'short' => 't',
        ]);

        return $parser;
    }

    /**
     * Converts a relative file path to an absolute path.
     *
     * This allows you to use a path relative to ROOT, thus taking advantage of the shell's autocompletion.
     *
     * For example,
     * ```
     * $ bin/cake database_backup.import backups/backup_myapp_20250305160001.sql.gz
     * ```
     *
     * @param string $path
     * @return string
     * @since 2.13.5
     */
    public function makeAbsolutePath(string $path): string
    {
        if (Path::isAbsolute($path)) {
            return $path;
        }

        $absolutePath = Path::makeAbsolute($path, ROOT);
        $absolutePath = DS == '\\' ? str_replace(search: '/', replace: DS, subject: $absolutePath) : $absolutePath;

        return new Filesystem()->exists($absolutePath) ? $absolutePath : $path;
    }

    /**
     * Makes the relative path (relative to `ROOT`).
     *
     * @param string $path
     * @return string
     * @since 2.13.5
     */
    public function makeRelativePath(string $path): string
    {
        return Path::isBasePath(ROOT, $path) ? Path::makeRelative($path, ROOT) : $path;
    }
}
