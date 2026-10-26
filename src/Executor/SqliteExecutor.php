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

use Cake\Database\Schema\TableSchemaInterface;
use Cake\Event\EventInterface;
use Override;

/**
 * SqliteExecutor to export/import database backups.
 */
class SqliteExecutor extends Executor
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function getBinaryName(): string
    {
        return 'sqlite3';
    }

    /**
     * Gets all tables schemas
     *
     * @return array<\Cake\Database\Schema\TableSchemaInterface>
     * @since 3.0.0
     */
    public function getAllTableSchemas(): array
    {
        return array_map(
            callback: fn(string $tableName): TableSchemaInterface => $this->Connection->getSchemaCollection()->describe($tableName),
            array: $this->Connection->getSchemaCollection()->listTables(),
        );
    }

    /**
     * Drops all tables.
     *
     * @return array<\Cake\Database\StatementInterface>
     * @since 2.14.1
     */
    public function dropAllTables(): array
    {
        $statements = [];

        /** @var array<\Cake\Database\Schema\TableSchema> $tableSchemas */
        $tableSchemas = $this->getAllTableSchemas();

        foreach ($tableSchemas as $TableSchema) {
            foreach ($TableSchema->dropSql($this->Connection) as $dropSql) {
                $statements[] = $this->Connection->execute($dropSql);
            }
        }

        return $statements;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeImport(EventInterface $Event): void
    {
        //For each table, drops the table
        $this->dropAllTables();

        //Needs to disconnect and re-connect because the database schema has changed
        $this->Connection->getDriver()->disconnect();
        $this->Connection->getDriver()->connect();
    }
}
