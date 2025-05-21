# How to use the BackupImport utility

* [Basic operation](#basic-operation)
* [Methods](#methods)
   * [filename()](#filename)
   * [timeout()](#timeout)
* [Import the database](#import-the-database)
   * [import()](#import)

***

# Basic operation
To import a database, you have to follow three steps:

**1) Instantiate the class**  
Example:
```php
$backup = new BackupImport();
```
**2) Set the filename of the backup you want to import**  
Example:
```php
$backup = new BackupImport();
$backup->filename('my_backup.sql');
```
Note that **you must call** the `filename()` method and set the backup filename.  
See [how to use the `filename()` method](#filename) below.

**3) Import the database, calling the `import()` method**  
Example:
```php
$backup = new BackupImport();
$backup->filename('my_backup.sql');
$filename = $backup->import();
```
Note that the `import()` method returns the path of the backup that you have imported.  
See [how to use the `import()` method](#import) below.

# Methods
## `filename()`
```php
filename(string $filename)
```
It sets the filename of the backup you want to import.

**Parameters for `filename()`**  
*string $filename*  
Filename. It can be an absolute path.

## `timeout()`
```php
timeout(int $timeout)
```
Sets the timeout (in seconds) for commands to be executed in the shell.
This is useful when working with very large databases that take a long time to export and import.

**Parameters for `timeout()`**  
*int $timeout*
Timeout in seconds

# Import the database
## `import()`
```php
import()
```
Imports the database and returns its filename path on success or `false` if the `Backup.beforeImport` event is stopped.
