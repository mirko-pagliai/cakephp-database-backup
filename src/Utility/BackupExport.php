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

namespace DatabaseBackup\Utility;

/**
 * Utility to export databases.
 */
class BackupExport extends Utility
{
    /**
     * Replaces filename patterns.
     *
     * @param string $filename
     * @return string
     */
    public function replaceFilenamePatterns(string $filename): string
    {
        return str_replace(
            search: ['{$DATETIME}', '{$TIMESTAMP}'],
            replace: [
                date('YmdHis'),
                (string)time(),
            ],
            subject: $filename
        );
    }
}
