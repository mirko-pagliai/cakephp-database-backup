# Migration from branch 2.15 to 3.0

These instructions allow you to migrate from the `2.14.x`/`2.15.x` branch to the `3.0.x` branch.

In particular and more precisely, they are intended to be valid when moving from the `2.14.x` or `2.15.x` branch to the `3.0.x` branch.
Therefore, make sure you have first updated, preferably, to one of the latest versions of the `2.14.x` or `2.15.x` branches.

This document was previously named `Migration from version 2.md`.

If you only use the plugin through the `database_backup.export` and `database_backup.import` commands and you have not
changed its configuration, you probably don't have to do much beyond checking the [requirements](#requirements) and
the [binaries](#from-mysqlmysql-dump-to-mariadbmariadb-dump).

The following sections are relevant if you use the plugin classes in your code, if you have overridden its
configuration, or if you have extended its classes.

- [Requirements](#requirements)
- [Can databases already exported with `2.x` branch versions be imported with `3.x` branch versions?](#can-databases-already-exported-with-2x-branch-versions-be-imported-with-3x-branch-versions)
- [From `mysql`/`mysql-dump` to `mariadb`/`mariadb-dump`](#from-mysqlmysql-dump-to-mariadbmariadb-dump)
- [Configuration](#configuration)
  * [Configuring export/import commands](#configuring-exportimport-commands)
  * [Other configuration values](#other-configuration-values)
- [If you use `BackupExport` and `BackupImport` in your code](#if-you-use-backupexport-and-backupimport-in-your-code)
- [If you use the commands in your code, or if you have extended them](#if-you-use-the-commands-in-your-code-or-if-you-have-extended-them)
- [If you have created your own executors, or if you use the existing ones directly](#if-you-have-created-your-own-executors-or-if-you-use-the-existing-ones-directly)
- [If your tests extend the plugin `TestCase`](#if-your-tests-extend-the-plugin-testcase)
- [Removed classes and methods](#removed-classes-and-methods)

## Requirements

* PHP `>=8.4` (it was `>=8.1`);
* CakePHP `^5.2.10` (it was `^5.0`);
* `symfony/process` `^7.4.5` (it was `^6.0|^7.1.7|^7.4.5`, so Symfony 6 is no longer supported) and `symfony/filesystem`
  `^6.4.1|^7.2`;
* `league/container` `^4.2.3|^5.1` is now explicitly required;
* `ext-fileinfo`, `symfony/finder` and `symfony/polyfill-php83` are no longer required. If your app was relying on them
  through this plugin, require them directly.

## Can databases already exported with `2.x` branch versions be imported with `3.x` branch versions?

Presumably yes.

It is not possible to write concrete unit tests to verify this.

However, there is nothing to suggest otherwise (provided that the plugin has been correctly reconfigured if necessary, as explained below) and empirical tests performed on real databases have given positive results.

## From `mysql`/`mysql-dump` to `mariadb`/`mariadb-dump`

If you use a MySql/MariaDB database, make sure you have the `mariadb` and `mariadb-dump` executables (just make sure, if present the plugin will detect and use them automatically), which have replaced the old `mysql` and `mysql-dump`.

You can also do a simple shell check using the unix command `where`:

```bash
$ whereis mariadb && whereis mariadb-dump
mariadb: /usr/bin/mariadb /usr/share/man/man1/mariadb.1.gz
mariadb-dump: /usr/bin/mariadb-dump /usr/share/man/man1/mariadb-dump.1.gz
```

If you had set the path of the old executables in the configuration, the keys have changed accordingly:

| In `2.x` branch                    | From `3.0.x` branch                      |
|------------------------------------|------------------------------------------|
| `DatabaseBackup.binaries.mysql`     | `DatabaseBackup.binaries.mariadb`        |
| `DatabaseBackup.binaries.mysqldump` | `DatabaseBackup.binaries.mariadb-dump`   |

The other keys (`bzip2`, `gzip`, `pg_dump`, `pg_restore`, `sqlite3`) are unchanged.

If you still only have the old executables available, see [this](Common%20issues.md#transition-from-mysql-and-mysqldump-to-mariadb-and-mariadb-dump).

## Configuration

If you haven't overridden the default configuration, you almost certainly shouldn't have to do anything.

However, please note the following.

### Configuring export/import commands

As for the export/import commands, in previous versions they looked like this (see the `config/bootstrap.php` file if necessary):
```php
'DatabaseBackup.Mysql.export' => '{{BINARY}} --defaults-file={{AUTH_FILE}} {{DB_NAME}}',
'DatabaseBackup.Mysql.import' => '{{BINARY}} --defaults-extra-file={{AUTH_FILE}} {{DB_NAME}}',
'DatabaseBackup.Postgres.export' => '{{BINARY}} --format=c -b --dbname=\'postgresql://{{DB_USER}}{{DB_PASSWORD}}@{{DB_HOST}}/{{DB_NAME}}\'',
'DatabaseBackup.Postgres.import' => '{{BINARY}} --format=c -c -e --dbname=\'postgresql://{{DB_USER}}{{DB_PASSWORD}}@{{DB_HOST}}/{{DB_NAME}}\'',
'DatabaseBackup.Sqlite.export' => '{{BINARY}} {{DB_NAME}} .dump',
'DatabaseBackup.Sqlite.import' => '{{BINARY}} {{DB_NAME}}',
```

Even earlier (i.e., up to `2.13.x`), driver names also had lowercase letters, e.g. `DatabaseBackup.mysql.export`.
The `2.14.x`/`2.15.x` branches still accepted these names, converting them and reporting a deprecation.
From the `3.0.x` branch they are no longer converted (nor reported): they are simply ignored, and the default command
is used instead. Rename them to `DatabaseBackup.Mysql.export` and so on.

From the `3.0.x` branch, they appear as follows (here too, if necessary, refer to the `config/bootstrap.php` file):
```php
'DatabaseBackup.Mysql.export' => '"${:BINARY}" --defaults-file="${:AUTH_FILE}" "${:DB_NAME}"',
'DatabaseBackup.Mysql.import' => '"${:BINARY}" --defaults-extra-file="${:AUTH_FILE}" "${:DB_NAME}"',
'DatabaseBackup.Postgres.export' => '"${:BINARY}" --format=c -b --dbname=\'postgresql://"${:DB_USERNAME}":"${:DB_PASSWORD}"@"${:DB_HOST}"/"${:DB_NAME}"\'',
'DatabaseBackup.Postgres.import' => '"${:BINARY}" --format=c -c -e --dbname=\'postgresql://"${:DB_USERNAME}":"${:DB_PASSWORD}"@"${:DB_HOST}"/"${:DB_NAME}"\'',
'DatabaseBackup.Sqlite.export' => '"${:BINARY}" "${:DB_NAME}" .dump',
'DatabaseBackup.Sqlite.import' => '"${:BINARY}" "${:DB_NAME}"',
```

As you can see, the significant change is given by the syntax used to indicate the patterns that will then be replaced in the command. If before it was `{{VARIABLE}}`, now they are `"${:VARIABLE}"`

This is because, starting from the `3.0.x` branch, both the execution of commands and the escaping of variables has been entirely delegated to the (external) `Process` component (possibly [read here](https://symfony.com/doc/current/components/process.html#using-features-from-the-os-shell)).

As mentioned at the beginning, if these configuration values have not been overridden in your app, you should not do anything.
If, however, for some particular need, you have overridden them, then you need to update the syntax of the variables.

These are the patterns available, with the old and the new syntax:

| In `2.x` branch    | From `3.0.x` branch   | Notes                                                                                                                                                      |
|--------------------|-----------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `{{BINARY}}`       | `"${:BINARY}"`        |                                                                                                                                                            |
| `{{AUTH_FILE}}`    | `"${:AUTH_FILE}"`     |                                                                                                                                                            |
| `{{DB_USER}}`      | `"${:DB_USERNAME}"`   | The name has changed.                                                                                                                                      |
| `{{DB_PASSWORD}}`  | `"${:DB_PASSWORD}"`   | In `2.x` branch the value already included the leading `:` (and it was empty without a password). Now it is the password only: write the `:` in the command. |
| `{{DB_HOST}}`      | `"${:DB_HOST}"`       |                                                                                                                                                            |
| `{{DB_NAME}}`      | `"${:DB_NAME}"`       |                                                                                                                                                            |

The redirection to/from the backup file and the compression are still added by the plugin, so they must not be part of
these commands.

### Other configuration values

First of all, the `DatabaseBackup.chmod` configuration value no longer exists.
For chmod of exported backups, you can implement this yourself in your code if you need to.
Previously the exported files were set to `0664` by default. Now the plugin no longer changes their mode: it depends on
the `umask` of the process that runs the export.

The `DatabaseBackup.connection` configuration had already been deprecated starting from version `2.14.2` and other "mechanics" had already been implemented to use connections other than the `default` one.
From the `3.0.x` branch it is no longer read at all: if you still have it set, the `default` connection will be used.
To use another connection, pass it to the constructor of `BackupExport`/`BackupImport` (see below) or use the
`--connection` option of the commands.

Instead, the `DatabaseBackup.processTimeout` and `DatabaseBackup.target` values remain unchanged.

The `DATABASE_BACKUP_EXECUTABLES` constant, which was defined by the plugin, no longer exists.

If the target directory is not writable (or is not a directory), the plugin still reports it with `trigger_error()`,
but now with the default level (`E_USER_NOTICE`) instead of `E_USER_ERROR`.

## If you use `BackupExport` and `BackupImport` in your code

The two classes keep the same names and the same constructor argument (a connection or its name), but they now extend
`DatabaseBackup\Utility\Utility` (previously `DatabaseBackup\Utility\AbstractBackupUtility`) and use property hooks.

Setting values with methods still works (`filename()`, `compression()`, `timeout()` and `connection()`, which can be
chained) thanks to the magic `__call()` method. Getting them does not: the magic `getX()` methods have been replaced by
public properties.

| In `2.x` branch                                                         | From `3.0.x` branch                                       |
|-------------------------------------------------------------------------|-----------------------------------------------------------|
| `$BackupExport->getFilename()` / `$BackupImport->getFilename()`          | `$BackupExport->filename` / `$BackupImport->filename`     |
| `$BackupExport->getCompression()`                                       | `$BackupExport->compression`                              |
| `$BackupExport->getTimeout()` / `$BackupImport->getTimeout()`            | `$BackupExport->timeout` / `$BackupImport->timeout`       |
| `$BackupExport->getConnection()` / `$BackupImport->getConnection()`      | `$BackupExport->Connection` / `$BackupImport->Connection` |
| `$BackupExport->getExecutor()` / `$BackupImport->getExecutor()`          | `$BackupExport->Executor` / `$BackupImport->Executor`     |
| `$BackupExport->rotate(int $keep)`                                      | Removed (it had been deprecated in `2.15.0`)              |

Further changes:

* `compression()` also accepts a string (e.g. `'gzip'`) or `null`;
* `timeout` must be greater than or equal to `0`, otherwise an `InvalidArgumentException` is thrown. `0` is passed to
  `Process`, which means that no timeout is applied; previously, `0` meant "use `DatabaseBackup.processTimeout`";
* a failed export or import now throws a `Symfony\Component\Process\Exception\ProcessFailedException` (which is still a
  `RuntimeException`), instead of a `RuntimeException` with the message `Export failed with error message: ...` /
  `Import failed with error message: ...`. If you relied on the text of the message, check it;
* if `BackupImport::import()` is called without having set the filename, it now throws a `LogicException`
  (previously a `BadMethodCallException`, which is a subclass of it);
* `BackupImport::filename()` with a missing file throws an `IOException` saying that the file does not exist (previously
  that the file or directory was not readable);
* the internal methods `getFilesystem()` and `getProcess()` no longer exist. Commands are now run by
  `Executor::runProcess()`.

## If you use the commands in your code, or if you have extended them

* the abstract class `Command` has been moved from `DatabaseBackup\Console` to `DatabaseBackup\Command`;
* `execute()` of `ExportCommand` and `ImportCommand` now returns an `int` (previously `void`);
* the base command no longer has its own `execute()` method: so the `Connection: ...`, `Driver: ...` and
  `Timeout for shell commands: ...` lines are no longer printed before the operation;
* the protected methods `getBackupExport()` and `getBackupImport()` no longer exist;
* the `--timeout` option must be an integer greater than `0`, otherwise the command fails. Previously `0` and invalid values
  were ignored, and the value of `DatabaseBackup.processTimeout` was used;
* `ImportCommand::makeAbsoluteFilename()` (static) no longer exists. Now there is `Command::makeAbsolutePath()`
  (not static), which is available to both the commands, as `makeRelativePath()` already was.

## If you have created your own executors, or if you use the existing ones directly

* `DatabaseBackup\Executor\AbstractExecutor` has become `DatabaseBackup\Executor\Executor`, and the constructor is now
  `__construct(Cake\Database\Connection $Connection, DatabaseBackup\OperationType $OperationType)`: an executor is
  created for a single type of operation (export or import). The `$name` argument no longer exists, since the name of the
  driver is obtained from the connection;
* you have to implement the new abstract method `getBinaryName()`, which returns the name of the binary to use for the
  operation (e.g. `pg_dump` for export and `pg_restore` for import);
* `getBinary()` has become `findBinary()`. If no path is set in the configuration, it now also looks for the binary
  using the `ExecutableFinder` of `Process`, since the binaries are no longer all discovered by the bootstrap;
* `getExportCommand()`, `getImportCommand()` (and the deprecated `getExportExecutable()` and `getImportExecutable()`),
  `getConfig()` and the magic `__call()` method have been removed. There is now `getCommand(Compression $Compression)`,
  which returns the command to run, and `runProcess(string $filename, int $timeout = 60)`, which runs it. To read a
  value of the connection, use `$this->Connection->config()`;
* the `beforeExport()` and `beforeImport()` methods must now be declared with the `void` return type. If you override
  them without it, PHP raises a fatal error. The names of the events (`Backup.beforeExport`, `Backup.afterExport`,
  `Backup.beforeImport`, `Backup.afterImport`) are unchanged;
* in `MysqlExecutor` the `getAuthFilePath()` method no longer exists: the path of the file is the `$authFile`
  property, and the `writeAuthFile()` and `deleteAuthFile()` methods are now public.

## If your tests extend the plugin `TestCase`

`DatabaseBackup\TestSuite\TestCase` is no longer abstract and only adds the integration with _Mockery_, which is
therefore required (`mockery/mockery`, as a development dependency). The `createBackup()`, `createSomeBackups()` and
`getBackupExport()` methods, and the `tearDown()` that deleted the `.sql`, `.sql.gz` and `.sql.bz2` files from the
target directory, have been removed.

Also, with the `3.x` branch, tests normally do not use the real drivers and do not write or read files on the filesystem
([see here](../README.md#testing)).

## Removed classes and methods

For convenience, here is the list of everything that no longer exists in the `3.0.x` branch:

* the `DatabaseBackup\Utility\BackupManager` class (its `index()` and `rotate()` methods had been deprecated);
* the `DatabaseBackup\Plugin` class (deprecated in `2.15.2`), use `DatabaseBackup\DatabaseBackupPlugin`;
* the aliases of the old `DatabaseBackup\Driver\*` classes (`AbstractDriver`, `Mysql`, `Postgres` and `Sqlite`), which
  the `2.x` branch kept for backward compatibility; use the classes of the `DatabaseBackup\Executor` namespace;
* the `DatabaseBackup\Utility\AbstractBackupUtility` class, now `DatabaseBackup\Utility\Utility`;
* the `DatabaseBackup\Executor\AbstractExecutor` class, now `DatabaseBackup\Executor\Executor`;
* the `DatabaseBackup\Console\Command` class, now `DatabaseBackup\Command\Command`;
* `BackupExport::rotate()`;
* the `DATABASE_BACKUP_EXECUTABLES` constant;
* the `DatabaseBackup.chmod` and `DatabaseBackup.connection` configuration values.

New in the `3.0.x` branch: `Compression::isValid()` (it is `true` for any case different from `Compression::None`) and
`Compression::validCases()`.
