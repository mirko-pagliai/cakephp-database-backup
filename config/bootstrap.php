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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Configuration
 */

use Cake\Core\Configure;
use Tools\Filesystem;

//Auto-discovers binaries
foreach (['bzip2', 'gzip', 'mysql', 'mysqldump', 'pg_dump', 'pg_restore', 'sqlite3'] as $binary) {
    if (!Configure::check('DatabaseBackup.binaries.' . $binary)) {
        try {
            $binaryPath = which($binary);
        } catch (\Exception $e) {
            $binaryPath = null;
        }
        Configure::write('DatabaseBackup.binaries.' . $binary, $binaryPath);
    }
}

//Default chmod for backups. This works only on Unix
if (!Configure::check('DatabaseBackup.chmod')) {
    Configure::write('DatabaseBackup.chmod', 0664);
}

//Database connection
if (!Configure::check('DatabaseBackup.connection')) {
    Configure::write('DatabaseBackup.connection', 'default');
}

//Default target directory
if (!Configure::check('DatabaseBackup.target')) {
    Configure::write('DatabaseBackup.target', Filesystem::instance()->concatenate(ROOT, 'backups'));
}

//Checks for the target directory
$target = Configure::read('DatabaseBackup.target');
if (!file_exists($target)) {
    mkdir($target, 0777);
}
if (!is_dir($target) || !is_writeable($target)) {
    trigger_error(sprintf('The directory `%s` is not writable or is not a directory', $target), E_USER_ERROR);
}
