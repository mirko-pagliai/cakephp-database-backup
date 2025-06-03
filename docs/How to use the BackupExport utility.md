# How to use the BackupExport utility

- [Basic operation](#basic-operation)
- [Optional methods](#optional-methods)
  * [`compression()`](#-compression---)
  * [`filename()`](#-filename---)
  * [~~`rotate()`~~](#-rotate---)
  * [~~`send()`~~](#---send-----)
  * [`timeout()`](#-timeout---)
- [Export the database](#export-the-database)
  * [`export()`](#-export---)

***

# Basic operation
To export a database, you have to follow three steps:

**1) Instantiate the class**  
Example:

```php
$backup = new BackupExport();
```
**2) Call one (or more) optional methods**  
Example:
```php
$backup = new BackupExport();
$backup->compression('gzip');
$backup->filename('my_custom_name');
```
Note that all these methods are **optional**.  
See the [methods list](#optional-methods) below.

**3) Export the database, calling the `export()` method**  
Example:
```php
$backup = new BackupExport();
$backup->compression('gzip');
$backup->filename('my_custom_name');
$filename = $backup->export();
```
Note that the `export()` method returns the path of the backup.  
See [how to use the `export()` method](#export) below.

# Optional methods
You can call one (or more) optional methods.

## `compression()`
```php
compression(?string $compression)
```
Sets the compression.

**Parameters for `compression()`**  
*null|string $compression*  
Compression. Supported values are `bzip2`, `gzip` and `null` (if you don't want to use compression).

## `filename()`
```php
filename(string $filename)
```
Sets the filename.  
Note that using this method, the compression type will be automatically setted by the filename.

**Parameters for `filename()`**  
*string $filename*  
Filename. It can be an absolute path and may contain patterns.

Patterns are `{$DATABASE}` (database name), `{$DATETIME}` (datetime), `{$HOSTNAME}` (hostname) and `{$TIMESTAMP}` (timestamp).

## ~~`rotate()`~~
_(deprecated since 2.15.0)_
```php
rotate(int $rotate)
```
Sets the number of backups you want to keep. So, it will delete all backups that are older.  
See also [BackupManager::rotate()](How%20to%20use%20the%20BackupManager%20utility.md#rotate).

**Parameters for `rotate()`**
*int $rotate*  
Number of backups you want to keep.

## ~~`send()`~~
_(deprecated since 2.13.4, removed in 2.14.0)_
```php
send(?string $recipient = null)
```
~~Sets the recipient's email address to send the backup file via mail.~~

**Parameters for `send()`**  
*null|string $recipient*  
Recipient's email address or `null` to disable.

## `timeout()`
```php
timeout(int $timeout)
```
Sets the timeout (in seconds) for commands to be executed in the shell.
This is useful when working with very large databases that take a long time to export and import.

**Parameters for `timeout()`**  
*int $timeout*
Timeout in seconds

# Export the database
## `export()`
```php
export()
```
Exports the database and returns its filename path on success or `false` if the `Backup.beforeExport` event is stopped.
