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

use Cake\Core\Configure;

$defaults = [
    'DatabaseBackup.Mysql.export' => '"${:BINARY}" --defaults-file="${:AUTH_FILE}" "${:DB_NAME}"',
    'DatabaseBackup.Mysql.import' => '"${:BINARY}" --defaults-extra-file="${:AUTH_FILE}" "${:DB_NAME}"',
    'DatabaseBackup.Postgres.export' => '"${:BINARY}" --format=c -b --dbname=\'postgresql://"${:DB_USERNAME}":"${:DB_PASSWORD}"@"${:DB_HOST}"/"${:DB_NAME}"\'',
    'DatabaseBackup.Postgres.import' => '"${:BINARY}" --format=c -c -e --dbname=\'postgresql://"${:DB_USERNAME}":"${:DB_PASSWORD}"@"${:DB_HOST}"/"${:DB_NAME}"\'',
    'DatabaseBackup.Sqlite.export' => '"${:BINARY}" "${:DB_NAME}" .dump',
    'DatabaseBackup.Sqlite.import' => '"${:BINARY}" "${:DB_NAME}"',
];

foreach ($defaults as $key => $value) {
    if (!Configure::check($key)) {
        Configure::write($key, $value);
    }
}
