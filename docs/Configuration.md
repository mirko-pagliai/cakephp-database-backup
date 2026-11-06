# Configuration

The plugin uses some configuration parameters, and you can set them using the  `\Cake\Core\Configure` class, **before** loading the plugin.

For example, you can do this at the bottom of the file `APP/config/app.php` of your application.

If you want to send backup files by email, remember to set up your application correctly so that it can send emails.  
For more information on how to configure your application, see the [CakePHP documentation](https://book.cakephp.org/5/en/core-libraries/email.html#configuring-transports).

- [Configuration values](#configuration-values)
    * [`DatabaseBackup.processTimeout`](#-databasebackupprocesstimeout-)
    * [`DatabaseBackup.target`](#-databasebackuptarget-)
    * [Permissions of backup files](#permissions-of-backup-files)
- [Binaries](#binaries)
- [Customize export and import commands](#customize-export-and-import-commands)

***

## Configuration values

### `DatabaseBackup.processTimeout`
_(from the 2.12.0 version)_
```php
Configure::write('DatabaseBackup.processTimeout', 60);
```
Setting `DatabaseBackup.processTimeout`, you can choose the general timeout time (in seconds) for commands to be executed in the shell.

### `DatabaseBackup.target`
```php
Configure::write('DatabaseBackup.target', ROOT . DS . 'backups');
```    
Setting `DatabaseBackup.target`, you can use another directory where the plugin will save backup files.

### Permissions of backup files
A backup contains all the data of your database, so who can read it matters.

The plugin does not change the mode of the exported files: it depends on the `umask` of the process that runs the export
(a cron job, PHP-FPM and so on). With the common `umask` `022`, backups are readable by every user of the system.
If the target directory does not exist, the plugin creates it with mode `0777`, filtered by the same `umask`.

If this is not what you want, you can:
* restrict the access to the target directory (for example `chmod 700 /path/to/backups`), so that other users cannot
  enter it, whatever the mode of the files inside;
* set a stricter `umask` (for example `umask 077`) for the user or the service that runs the export, so that new
  backups are created readable only by their owner.

## Binaries
The plugin uses several binary files:
* `bzip2`
* `gzip`
* `mariadb`
* `mariadb-dump`
* `pg_dump`
* `pg_restore`
* `sqlite3`

By default, all binaries will be detected automatically.
If a binary is not found or if you want to set a different path for the binary, you can use these configuration values.

Just an example for UNIX:
```php
Configure::write('DatabaseBackup.binaries.bzip2', '/full/path/to/bzip2');
```
And an example for Windows:

```php
Configure::write('DatabaseBackup.binaries.mariadb', 'C:\\xampp\\mysql\\bin\\mariadb.exe');
Configure::write('DatabaseBackup.binaries.mariadb-dump', 'C:\\xampp\\mysql\\bin\\mariadb-dump.exe');
```

The same applies to all other binaries, just change the binary name shown in the example.

## Customize export and import commands
_(from the 2.10.0 version)_

By default, commands (to export/import backups) are executed with generic options that are valid for almost all environments.
However, in some particular environments or conditions it may be necessary to execute commands with particular options.

The default commands are defined in the `config/bootstrap.php` file and placeholders (such as `"${:BINARY}"` or `"${:DB_HOST}"`) are replaced and escaped before the command is executed.

It is therefore possible to use custom commands by acting on the configuration before loading the plugin.

An example: suppose you want to run the command to export mysql databases with the `--column-statistics=0` option. Then in the bootstrap of your application:
```php
Configure::write('DatabaseBackup.Mysql.export', '"${:BINARY}" --defaults-file="${:AUTH_FILE}" "${:DB_NAME}" --column-statistics=0');
```

However, remember that some values should be escaped and that incorrect customization of the commands could make the plugin unusable or otherwise cause unwanted effects.

### Default options of `mariadb-dump`
The default export command for *MariaDB*/*MySql* databases does not add any option to `mariadb-dump`, so the defaults of
`mariadb-dump` apply. Among them:
* `--opt` is enabled, which includes `--lock-tables` (the user needs the `LOCK TABLES` permission, see
  [this](Common%20issues.md#lock-tables-permission)): tables are locked while they are exported;
* stored routines (`--routines`) and events (`--events`) are **not** exported, so they will not be in the backup.

If you need a consistent backup of `InnoDB` tables without locking them, or if your database uses stored routines or
events, you can customize the export command:
```php
Configure::write('DatabaseBackup.Mysql.export', '"${:BINARY}" --defaults-file="${:AUTH_FILE}" --single-transaction --routines --events "${:DB_NAME}"');
```
Keep `--defaults-file` as the first option.
