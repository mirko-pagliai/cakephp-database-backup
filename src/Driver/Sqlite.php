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
 * Sqlite driver to export/import database backups
 */
class Sqlite extends AbstractDriver
{
    /**
     * Called before import
     * @return bool
     * @since 2.1.0
     */
    public function beforeImport(): bool
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = $this->getConnection();
        /** @var \Cake\Database\Schema\Collection $schemaCollection */
        $schemaCollection = $connection->getSchemaCollection();

        //Drops each table
        foreach ($schemaCollection->listTables() as $table) {
            array_map([$connection, 'execute'], $schemaCollection->describe($table)->dropSql($connection));
        }

        //Needs disconnect and re-connect because the database schema has changed
        $connection->disconnect();
        $connection->connect();

        return true;
    }
}
