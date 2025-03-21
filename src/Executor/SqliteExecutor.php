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

namespace DatabaseBackup\Executor;

/**
 * Sqlite executor to export/import database backups.
 */
class SqliteExecutor extends AbstractExecutor
{
    /**
     * Internal method to drop all tables.
     *
     * @return void
     * @since 2.14.1
     */
    protected function dropAllTables(): void
    {
        /** @var \Cake\Database\Connection $Connection */
        $Connection = $this->Connection;

        $SchemaCollection = $Connection->getSchemaCollection();
        foreach ($SchemaCollection->listTables() as $tableName) {
            /** @var \Cake\Database\Schema\TableSchema $tableSchema */
            $tableSchema = $SchemaCollection->describe($tableName);
            foreach ($tableSchema->dropSql($Connection) as $dropSql) {
                $Connection->execute($dropSql);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeImport(): bool
    {
        /** @var \Cake\Database\Connection $Connection */
        $Connection = $this->Connection;

        //For each table, drops the table
        $this->dropAllTables();

        //Needs disconnect and re-connect because the database schema has changed
        $Connection->getDriver()->disconnect();
        $Connection->getDriver()->connect();

        return true;
    }
}
