# Configuration

The plugin uses some configuration parameters and you can set them using the  `\Cake\Core\Configure` class, **before** loading the plugin.

For example, you can do this at the bottom of the file `APP/config/app.php` of your application.

If you want to send backup files by email, remember to set up your application correctly so that it can send emails.  
For more information on how to configure your application, see the [CakePHP documentation](https://book.cakephp.org/5/en/core-libraries/email.html#configuring-transports).

- [Configuration values](#configuration-values)
    + [`DatabaseBackup.chmod`](#-databasebackupchmod-)
    + [`DatabaseBackup.connection`](#-databasebackupconnection-)
    + [~~`DatabaseBackup.mailSender`~~](#---databasebackupmailsender---)
    + [`DatabaseBackup.processTimeout`](#-databasebackupprocesstimeout-)
    + [`DatabaseBackup.target`](#-databasebackuptarget-)
    * [Binaries](#binaries)
    * [Customize export/import commands](#customize-export-import-commands)

***

# Configuration values

### `DatabaseBackup.chmod`
```php
Configure::write('DatabaseBackup.chmod', 0664);
```    
Setting `DatabaseBackup.chmod`, you can choose the permissions that will be applied to new backup files.  
Note that you must set an octal value. For more information, please refer to the [PHP manual](http://php.net/manual/en/function.chmod.php).  
Note that this works only on Unix systems.

### `DatabaseBackup.connection`
```php
Configure::write('DatabaseBackup.connection', 'default');
```
Setting `DatabaseBackup.connection`, you can choose which database connection you want to use.  
For more information about database connections, please refer to the
[CakePHP documentation](https://book.cakephp.org/5/en/orm/database-basics.html#configuration).

### ~~`DatabaseBackup.mailSender`~~
_(deprecated since 2.13.4, removed in 2.14.0)_
```php
Configure::write('DatabaseBackup.mailSender', 'sender@example.it');
```
~~Setting `DatabaseBackup.mailSender`, you can choose the email address that will be the sender when you send the backup files via email.~~

### `DatabaseBackup.processTimeout`
_(from 2.12.0 version)_
```php
Configure::write('DatabaseBackup.processTimeout', 60);
```
Setting `DatabaseBackup.processTimeout`, you can choose the general timeout time (in seconds) for commands to be executed in the shell.

### `DatabaseBackup.target`
```php
Configure::write('DatabaseBackup.target', ROOT . DS . 'backups');
```    
Setting `DatabaseBackup.target`, you can use another directory where the plugin will save backup files.

## Binaries
The plugin uses several binary files:
* `bzip2`
* `gzip`
* `mariadb` (previously `mysql`)
* `mariadb-dump` (previously `mysqldump`)
* `pg_dump`
* `pg_restore`
* `sqlite3`

By default, all binaries will be detected automatically.
If a binary is not found or if you want to set a different path for the bynary, you can use these configuration values.

Just an example for UNIX:
```php
Configure::write('DatabaseBackup.binaries.bzip2', '/full/path/to/bzip2');
```
And an example for Windows:

```php
Configure::write('DatabaseBackup.binaries.mysql', 'C:\\xampp\\mysql\\bin\\mysql.exe');
Configure::write('DatabaseBackup.binaries.mysqldump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe');
```

The same applies to all other binaries, just change the binary name shown in the example.

## Customize export/import commands
_(from 2.10.0 version)_

By default, commands (to export/imports backups) are executed with generic options that are valid for almost all environments.
However, in some particular environments or conditions it may be necessary to execute commands with particular options.

The default commands are defined in the `config/bootstrap.php` file and placeholders (such as `{{BINARY}}` or `{{DB_HOST}}`) are replaced and escaped before the command is executed.

It is therefore possible to use custom commands by acting on the configuration, before loading the plugin.

An example: suppose you want to run the command to export mysql databases with the `--column-statistics=0` option. Then in the bootstrap of your application:
```php
Configure::write('DatabaseBackup.mysql.export', '{{BINARY}} --defaults-file={{AUTH_FILE}} {{DB_NAME}} --column-statistics=0');
```

However, remember that some values should be escaped and that incorrect customization of the commands could make the plugin unusable or otherwise cause unwanted effects.
