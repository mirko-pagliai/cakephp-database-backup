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
mysqldump: Got error: 2026: "TLS/SSL error: Certificate verification failure: The certificate is NOT trusted." when trying to connect
```

Starting from version `11.4`, the `mariadb` and `mariadb-dump` clients require TLS by default and verify the certificate of the server for TCP connections, while connections through a Unix socket are trusted (refer to [this](https://mariadb.com/docs/server/security/encryption/data-in-transit-encryption/zero-configuration-ssl)).

So, the error depends on the version of the client installed (for example, after a system upgrade) and not only on the version of the server. A certificate is accepted if it is signed by a certification authority trusted by the client or, even if it is self-signed, if the account uses the `mysql_native_password`, `ed25519` or `parsec` authentication plugin and has a non-empty password. With other plugins (for example PAM, GSSAPI or `caching_sha2_password`), a certificate that the client does not trust causes the error.

Note that the plugin runs the export command with the `--defaults-file` option, so the client options set in the global configuration files of MariaDB are ignored when exporting (the import uses `--defaults-extra-file`, which reads them).

You can solve it in these ways, in order of preference:
1. make the client trust the certificate of the server, adding the `--ssl-ca` option (path of the certification authority) to the commands, as explained [here](Configuration.md#customize-export-and-import-commands);
2. if the database is on the same machine, use `localhost` as host (instead of `127.0.0.1`), so that the client uses the Unix socket;
3. disable only the verification of the certificate, with the `--skip-ssl-verify-server-cert` option. The connection remains encrypted, but it is no longer protected against impersonation of the server (man-in-the-middle attacks).

For the third way, before the plugin is loaded (`--defaults-file` and `--defaults-extra-file` must remain the first options):

```php
Configure::write('DatabaseBackup.Mysql.export', '"${:BINARY}" --defaults-file="${:AUTH_FILE}" --skip-ssl-verify-server-cert "${:DB_NAME}"');
Configure::write('DatabaseBackup.Mysql.import', '"${:BINARY}" --defaults-extra-file="${:AUTH_FILE}" --skip-ssl-verify-server-cert "${:DB_NAME}"');
```

As a last resort, the `--skip-ssl` option disables TLS completely.

Use these options only on networks you trust: **they can expose your system to malicious attacks if used incorrectly**.

## `LOCK TABLES` permission

When exporting, an error similar to this occurs:

```bash
mysqldump: Got error: 1044: "Access denied for user 'myuser'@'localhost' to database 'mydb'" when using LOCK TABLES
```
The user accessing the database must have the correct permissions, depending on the driver you're using.

In this specific case, for `mysql` the user must have the `LOCK TABLES` permission.