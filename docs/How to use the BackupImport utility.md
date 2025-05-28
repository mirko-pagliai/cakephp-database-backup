# How to use the `BackupImport` utility

- [Basic operation](#basic-operation)
- [Methods](#methods)
    * [filename()](#filename--)
    * [timeout()](#timeout--)
- [Import the database](#import-the-database)
    * [import()](#import--)

## Basic operation
To import a database, you have to follow three steps:

**1) Instantiate the class**  
Example:
```php
$BackupImport = new BackupImport();
```

By default, the plugin will always use the default connection set by your app.

The `BackupImport` constructor can however accept as an optional argument a connection, either as a string (connection name) or as an instance of `ConnectionInterface`, if you need to use a connection that is not the default one.

**2) Set the filename of the backup you want to import**  
Example:
```php
$BackupImport = new BackupImport();
$BackupImport->filename('my_backup.sql');
```
Note that **you must call** the `filename()` method and set the backup filename.  
See [how to use the `filename()` method](#filename) below.

**3) Import the database, calling the `import()` method**  
Example:
```php
$BackupImport = new BackupImport();
$BackupImport->filename('my_backup.sql');
$filename = $backup->import();
```
Note that the `import()` method returns the path of the backup that you have imported.  
See [how to use the `import()` method](#import) below.

## Methods
### filename()
```php
filename(string $filename)
```

Sets the filename of the backup you want to import.
The filename can be an absolute path or a relative path. Relative paths will be considered relative to the default directory set with the `DatabaseBackup.target` configuration.

### timeout()
```php
timeout(int $timeout)
```

It sets the timeout (in seconds) to use for the import process.  
If not set, the default timeout value defined by the `DatabaseBackup.processTimeout` configuration value will be used by default. By calling this method, you can override the value for the current instance.

This is useful when working with very large databases that take a long time to export and import.

## Import the database
### import()
```php
import()
```
Imports the database and returns its filename path on success or `false` if the `Backup.beforeImport` event is stopped.