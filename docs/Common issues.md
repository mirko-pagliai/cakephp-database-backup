# Common issues

Before opening an issue, check this list of common issues.

Also, make sure [you have configured the plugin correctly](Configuration.md)

## Transition from `mysql` and `mysqldump` to `mariadb` and `mariadb-dump`

Before version `2.13.4`, for MySql databases the plugin used the `mysql` and `mysqldump` executables to export/import databases.

As reported in [issue #111](https://github.com/mirko-pagliai/cakephp-database-backup/issues/111) and already reported in [issue #110](https://github.com/mirko-pagliai/cakephp-database-backup/issues/110), the `mysql` and `mysql-dump` binaries have been deprecated and will be replaced with `mariadb` and `mariadb-dump`.

Note that on many systems `mysql` and `mysqldump` are still present, but they are just links to the newer `mariadb` and `mariadb-dump` (issuing a warning to abandon the deprecated executables).

So, starting with version `2.13.4`, the plugin prefers to use `mariadb` and `mariadb-dump`, but it also recognizes (as aliases) `mysql` and `mysqldump`.

Starting with the `3.0.x` branch, however, the plugin directly and exclusively uses `mariadb` and `mariadb-dump`.

However, if for some reason you are using the newer version of the plugin, but still have only the old executables available, you should configure the plugin like this:

```php
Configure::write('DatabaseBackup.binaries.mariadb', '/full/path/to/mysql');
Configure::write('DatabaseBackup.binaries.mariadb-dump', '/full/path/to/mysqldump');
```

## Errors about certificate verification

When using MariaDB, you may receive an error (related to certificate verification) like this (see [issue #112](https://github.com/mirko-pagliai/cakephp-database-backup/issues/112) and [issue #110](https://github.com/mirko-pagliai/cakephp-database-backup/issues/110)):

```bash
mysqldump: Got error: 2026: "TLS/SSL error: Certificate verification failure: The certificate is NOT trusted." when trying to connect`
```

The problem may be caused by upgrading to MariaDB 11.4 (refer to [this](https://mariadb.com/kb/en/securing-connections-for-client-and-server/#)):
> Starting from 11.4 MariaDB encrypts the transmitted data between the server and clients by default unless the server and client run on the same host.

So, the error occurs because MariaDB by default wants to use the TLS protocol to transmit data in encrypted form, but at the same time your certificate is not valid.  
**The advice is to check and fix the configuration of your system.**

In any case, it is possible to make the plugin ignore this function when exporting databases, as explained [here](Configuration.md#customize-exportimport-commands) and making the export command use the `--skip-ssl` option. So, before the plugin is loaded:

```php
Configure::write('DatabaseBackup.Mysql.export', '"${:BINARY}" --defaults-file="${:AUTH_FILE}" --skip-ssl "${:DB_NAME}"');
```

However, it is important to note that **this can expose your system to malicious attacks if used incorrectly**.

## `LOCK TABLES` permission

When exporting, an error similar to this occurs:

```bash
mysqldump: Got error: 1044: "Access denied for user 'myuser'@'localhost' to database 'mydb'" when using LOCK TABLES
```
The user accessing the database must have the correct permissions, depending on the driver you're using.

In this specific case, for `mysql` the user must have the `LOCK TABLES` permission.