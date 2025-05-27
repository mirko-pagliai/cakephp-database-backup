# Migration from version 2

These instructions allow you to migrate from the `2.14.x`/`2.15.x` branch to the `3.0.x` branch.

In particular and more precisely, they are intended to be valid when moving from the `2.14.x` or `2.15.x` branch to the `3.0.x` branch.
Therefore, make sure you have first updated, preferably, to one of the latest versions of the `2.14.x` or `2.15.x` branches.

- [Can databases already exported with `2.x` branch versions be imported with `3.x` branch versions?](#can-databases-already-exported-with--2x--branch-versions-be-imported-with--3x--branch-versions-)
- [From `mysql`/`mysql-dump` to `mariadb`/`mariadb-dump`](#from--mysql---mysql-dump--to--mariadb---mariadb-dump-)
- [Configuration](#configuration)
  * [Configuring export/import commands](#configuring-export-import-commands)
  * [Other configuration values](#other-configuration-values)

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

Even earlier (i.e. up to `2.13.x`), driver names also had lowercase letters, e.g. `DatabaseBackup.mysql.export`.

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

### Other configuration values

First of all, the `DatabaseBackup.chmod` configuration value no longer exist.
For chmods of exported backups, you can implement this yourself in your code, if you need to.

The `DatabaseBackup.connection` configuration had already been deprecated starting from version `2.14.2` and other "mechanics" had already been implemented to use connections other than the `default` one.

Instead, the `DatabaseBackup.processTimeout` and `DatabaseBackup.target` values remain unchanged.
