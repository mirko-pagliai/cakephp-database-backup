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
 * @since       2.0.0
 */

namespace DatabaseBackup\Driver;

/**
 * Sqlite driver to export/import database backups.
 */
class Sqlite extends AbstractExecutor
{
    /**
     * @inheritDoc
     */
    public function beforeImport(): bool
    {
        /** @var \Cake\Database\Connection $Connection */
        $Connection = $this->Connection;
        /** @var \Cake\Database\Schema\Collection $Schema */
        $Schema = $Connection->getSchemaCollection();

        //Drops each table
        foreach ($Schema->listTables() as $table) {
            /** @var \Cake\Database\Schema\TableSchema $TableSchema */
            $TableSchema = $Schema->describe($table);
            array_map([$Connection, 'execute'], $TableSchema->dropSql($Connection));
        }

        //Needs disconnect and re-connect because the database schema has changed
        $Connection->getDriver()->disconnect();
        $Connection->getDriver()->connect();

        return true;
    }
}
