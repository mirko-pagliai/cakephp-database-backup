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

use Cake\Command\Command;
use Cake\Console\ConsoleOptionParser;
use function Cake\I18n\__d;

/**
 * Command to import a database backup.
 *
 * @since 2.6.0
 */
class ImportCommand extends Command
{
    /**
     * Configures and returns the console option parser for a command.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The console option parser instance to configure.
     * @return \Cake\Console\ConsoleOptionParser The configured console option parser.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(__d('database_backup', 'Imports a database backup'));

        return $parser;
    }
}
