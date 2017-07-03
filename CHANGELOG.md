# 2.x branch
## 2.1 branch
### 2.1.0
* added support for Postgres databases;
* all `export()` and `import()` methods have been moved to the `Driver` class;
* added `afterExport()`, `afterImport()`, `beforeExport()` and `beforeImport`
    methods to the `Driver` class;
* `getCompression()` and `getExtension()` moved from `Driver` to `BackupTrait`
    class, because these methods are not strictly related to the database engine
    you are using;
* removed `getValidExtensions()` and `getValidCompressions()` methods from
    `Driver` class, because extensions and compressions are the same for any
    database engine;
* removed `getDefaultExtension()` method from `Driver` class, because the
    default extension is the same for any database engine.

## 2.0 branch
### 2.0.0
* the plugin has been renamed as `DatabaseBackup` (`cakephp-database-backup`);
* the code has been completely rewritten to work with drivers, so it can also
    work with other database engines;
* added support for Sqlite database;
* checks the return status code when it runs `mysql` and `mysqldump` from the
    command line.

# 1.x branch
## 1.1 branch
### 1.1.1
* fixed bugs in the table output for the `BackupShell::index()` method;
* `_storeAuth()` methods from `BackupExport` and `BackupImport` classes are
    now private;
* added `BackupTrait::getConnection()` method;
* added `BackupTrait::getValidCompressions()` method.

### 1.1.0
* added `BackupShell::send()` and `BackupManager::send()` methods to send backup
    files via mail;
* added `--send` option to `BackupShell::export()`;
* added `BackupExport::send()` to set up the email recipient, so by calling the
    `export()` method the backup file will be sent via email;
* added `BackupTrait`,  that provides some methods used by all other classes;
* added `rtr()` (relative to root) global function. This simplifies the output
    of some methods provided by `BackupShell`;
* global functions `compressionFromFile()`, `extensionFromCompression()` and
    `extensionFromFile()` have been replaced with the `getCompression()` and
    `getExtension()` methods provided by the `BackupTrait`;
* all methods belonging to the `BackupManager` class are no longer static.

## 1.0 branch
### 1.0.3
* the target directory is created automatically, if it does not exist;
* `BackupManager::index()` returns an array of entities;
* added `MYSQL_BACKUP` constant.

### 1.0.2
* methods that have been deprecated with CakePHP 3.4 have been replaced;
* updated for CakePHP 3.4.

### 1.0.1
* added `BackupManager::deleteAll()` and `BackupShell::deleteAll()` methods;
* improved tests. Also errors in the shell are checked.

### 1.0.0
* first release.