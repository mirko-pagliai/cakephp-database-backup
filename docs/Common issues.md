Before opening an issue, check this list of common issues.

Also, make sure [you have configured the plugin correctly](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Configuration).

## Transition from `mysql` and `mysqldump` to `mariadb` and `mariadb-dump`

As reported in [issue #111](https://github.com/mirko-pagliai/cakephp-database-backup/issues/111
) and already reported in [issue #110](https://github.com/mirko-pagliai/cakephp-database-backup/issues/110
), the `mysql` and `mysql-dump` binaries have been deprecated and will be replaced with `mariadb` and `mariadb-dump`.

The deprecation and subsequent replacement may vary from operating system to operating system, but as of today (_February 8, 2025_) the old binaries are generally still present, which simply link to the new ones:

```bash
$ whereis mysqldump
mysqldump: /usr/bin/mysqldump /usr/share/man/man1/mysqldump.1.gz
$ ls -l /usr/bin/mysqldump
lrwxrwxrwx 1 root root 12 30 nov  2023 /usr/bin/mysqldump -> mariadb-dump
$ whereis mariadb-dump
mariadb-dump: /usr/bin/mariadb-dump /usr/share/man/man1/mariadb-dump.1.gz
$ whereis mysql
mysql: /usr/bin/mysql /usr/lib/mysql /etc/mysql /usr/share/mysql /usr/share/man/man1/mysql.1.gz
$ whereis mysql^C
$ ls -l /usr/bin/mysql
lrwxrwxrwx 1 root root 7 30 nov  2023 /usr/bin/mysql -> mariadb
```

This, still today, makes it possible to use any pair of these binaries, without any particular problems. At most, some systems may issue a warning about the deprecation:
```bash
 `/usr/bin/mysqldump: Deprecated program name. It will be removed in a future release, use '/usr/bin/mariadb-dump' instead
```

This warns the user that the binaries have already been deprecated and will be replaced in the future, but export and import still work without problems.

Starting from version `2.13.4`, by default `cakephp-database-backup` will first look for the presence of `mariadb` and `mariadb-dump`, then for `mysql` and `mysqldump` as backwards compatibility.

Before this version, except that deprecation should not cause problems, it is possible to solve by [manually setting the executables](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Configuration#binaries), making sure that they point to `mariadb` and `mariadb-dump`:

```php
Configure::write('DatabaseBackup.binaries.mysql', '/usr/bin/mariadb');
Configure::write('DatabaseBackup.binaries.mysqldump', '/usr/bin/mariadb-dump');
```

## Errors about certificate verification

When using MariaDB, you may receive an error (related to certificate verification) like this (see [issue #112](https://github.com/mirko-pagliai/cakephp-database-backup/issues/112) and [issue #110](https://github.com/mirko-pagliai/cakephp-database-backup/issues/110)):

```
mysqldump: Got error: 2026: "TLS/SSL error: Certificate verification failure: The certificate is NOT trusted." when trying to connect`
```

The problem may be caused by upgrading to MariaDB 11.4 (refer to [this](https://mariadb.com/kb/en/securing-connections-for-client-and-server/#)):
> Starting from 11.4 MariaDB encrypts the transmitted data between the server and clients by default unless the server and client run on the same host.

So, the error occurs because MariaDB by default wants to use the TLS protocol to transmit data in encrypted form, but at the same time your certificate is not valid.  
**The advice is to check and fix the configuration of your system.**

In any case, it is possible to make the plugin ignore this function when exporting databases, as explained [here](https://github.com/mirko-pagliai/cakephp-database-backup/wiki/Configuration#customize-exportimport-commands) and making the export command use the `--skip-ssl` option. So, before the plugin is loaded:

```php
Configure::write('DatabaseBackup.mysql.export', '{{BINARY}} --defaults-file={{AUTH_FILE}} --skip-ssl {{DB_NAME}}');
```

However, it is important to note that **this can expose your system to malicious attacks if used incorrectly**.

## `LOCK TABLES` permission

When exporting, an error similar to this occurs:

```
mysqldump: Got error: 1044: "Access denied for user 'myuser'@'localhost' to database 'mydb'" when using LOCK TABLES
```
The user accessing the database must have the correct permissions, depending on the driver you're using.

In this specific case, for `mysql` the user must have the `LOCK TABLES` permission.