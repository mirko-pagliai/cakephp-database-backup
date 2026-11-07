# Migration from branch 3.0 to 3.1

These instructions allow you to migrate from the `3.0.x` branch to the `3.1.x` branch.

Make sure you have first updated, preferably, to one of the latest versions of the `3.0.x` branch.

This document is being written while the `3.1.x` branch is in development, and it will be completed as new changes are
made.

- [Requirements](#requirements)
- [The `Mariadb` driver](#the-mariadb-driver)
  * [Configuring export/import commands](#configuring-exportimport-commands)
  * [If you use the executors directly](#if-you-use-the-executors-directly)
- [If you use the commands in your code, or if you have extended them](#if-you-use-the-commands-in-your-code-or-if-you-have-extended-them)

## Requirements

* CakePHP `~5.5.0`;
* phpunit `13.2`.

## The `Mariadb` driver

Starting with CakePHP 5.5, MySQL and MariaDB have separate drivers: `Cake\Database\Driver\Mysql` and
`Cake\Database\Driver\Mariadb`. With a MariaDB server and the `Mysql` driver, CakePHP itself reports a deprecation
(`Since 5.5.0: MySQL and Mariadb now have separate drivers`). So, if your connection uses a MariaDB server, you should
change its driver as CakePHP suggests, either in the configuration:
```php
'driver' => Cake\Database\Driver\Mariadb::class,
```
or in the DSN (`mariadb://` instead of `mysql://`).

Since the plugin chooses the executor and the commands according to the driver of the connection, from the `3.1.x`
branch the `Mariadb` driver uses `DatabaseBackup\Executor\MariadbExecutor` and the `DatabaseBackup.Mariadb.*` commands,
while the `Mysql` driver continues to use `DatabaseBackup\Executor\MysqlExecutor` and the `DatabaseBackup.Mysql.*`
commands. Nothing changes if you keep using the `Mysql` driver.

### Configuring export/import commands

If you have not customized the commands, you don't have to do anything: the default commands of the two drivers are
the same.

If you have customized the `DatabaseBackup.Mysql.export` and/or `DatabaseBackup.Mysql.import` commands and you use the
`Mariadb` driver, you should set the same commands with the new keys:

| In `3.0.x` branch               | From `3.1.x` branch, with the `Mariadb` driver |
|---------------------------------|------------------------------------------------|
| `DatabaseBackup.Mysql.export`   | `DatabaseBackup.Mariadb.export`                |
| `DatabaseBackup.Mysql.import`   | `DatabaseBackup.Mariadb.import`                |

For example:
```php
Configure::write('DatabaseBackup.Mariadb.export', '"${:BINARY}" --defaults-file="${:AUTH_FILE}" "${:DB_NAME}" --single-transaction');
```

To ease the migration, during the `3.1.x` branch, if a `DatabaseBackup.Mariadb.*` key is not set, the corresponding
`DatabaseBackup.Mysql.*` key (if you have customized it) is used instead. This fallback is only meant to ease the
migration and **it will be removed in the `3.2.x` branch**: set the `DatabaseBackup.Mariadb.*` keys explicitly before
upgrading to it.

As usual, the configuration must be set before loading the plugin.

### If you use the executors directly

`DatabaseBackup\Executor\MariadbExecutor` is new, and it is currently an alias of `DatabaseBackup\Executor\MysqlExecutor`
(declared in `config/bootstrap.php`), so the `MysqlExecutor` class is the one actually instantiated.
Don't rely on this: with the `Mariadb` driver, use `MariadbExecutor` (or the executor that you get from
`BackupExport::$Executor` and `BackupImport::$Executor`), since the two classes could become distinct in the future.

## If you use the commands in your code, or if you have extended them

The commands now use `$this->args` and `$this->io` instead of the `execute()` parameters, and
`Command::getTimeoutOption()` no longer takes the `Arguments` parameter
([#230](https://github.com/mirko-pagliai/cakephp-database-backup/issues/230)).
