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

namespace DatabaseBackup\Executor;

/**
 *
 */
class SqliteExecutor extends Executor
{
    /**
     * Drops all tables.
     *
     * @return array<\Cake\Database\StatementInterface>
     * @since 2.14.1
     */
    public function dropAllTables(): array
    {
        $statements = [];

        /** @var \Cake\Database\Connection $Connection */
        $Connection = $this->Connection;

        $SchemaCollection = $Connection->getSchemaCollection();
        foreach ($SchemaCollection->listTables() as $tableName) {
            /** @var \Cake\Database\Schema\TableSchema $TableSchema */
            $TableSchema = $SchemaCollection->describe($tableName);

            foreach ($TableSchema->dropSql($Connection) as $dropSql) {
                $statements[] = $Connection->execute($dropSql);
            }
        }

        return $statements;
    }
}
