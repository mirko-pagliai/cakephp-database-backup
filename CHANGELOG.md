# 2.x branch
## 2.6 branch
### 2.6.0
* `BackupShell` has been replaced with console commands. Every method of the
    previous class is now a `Command` class;
* `BackupManager::index()` returns a collection of backups;
* `ConsoleIntegrationTestCase` has been replaced by `ConsoleIntegrationTestTrait`.
    `TestCaseTrait` has been removed and its methods moved to `TestCase`;
* removed `DATABASE_BACKUP` constant;
* updated for CakePHP 3.7.

## 2.5 branch
### 2.5.1
* updated for CakePHP 3.6 and 3.7. Added `Plugin` class;
* many small code fixes.

### 2.5.0
* now it uses the `mirko-pagliai/php-tools` package. This also replaces
    `mirko-pagliai/reflection`;
* removed `BackupTrait::getClassShortName()` method. The
    `get_class_short_name()` global function will be used instead.

## 2.4 branch
### 2.4.0
* fixed bug trying to email a nonexistent backup;
* `VALID_COMPRESSIONS` and `VALID_EXTENSIONS` constants have been replaced by
    `getValidCompressions()` and `getValidExtensions()` methods provided by the
    `BackupTrait` class;
* replaced `InternalErrorException` with `InvalidArgumentException` and
    `RuntimeException`. This allows compatibility with CakePHP 3.6 branch.

## 2.3 branch
### 2.3.0
* full support for working under Windows;
* added `Driver::getConfig()` method, removed `Driver::$config` property. This
    allows you to get the configuration values safely;
* fixed a bug for export and import executables with Postgres databases;
* before importing a sqlite backup, each table is dropped, rather than deleting
    the database file;
* added `isWin()` global function;
* tests can have `onlyUnix` or `onlyWindows` group.

## 2.2 branch
### 2.2.0
* added `ConsoleIntegrationTestCase` and `TestCaseTrait` classes. Console tests
    have been simplified;
* updated for CakePHP 3.5.

## 2.1 branch
### 2.1.4
* when a backup is sent by mail, the mimetype is forced;
* fixed some tests.

### 2.1.3
* added `createBackup()` and `createSomeBackups()` to the `TestCase` class;
* `BackupManager::_send()` has become `getEmailInstance()`.

### 2.1.2
* fixed `composer.json`: the plugin requires at least version 3.4 of CakePHP.

### 2.1.1
* `afterExport()`, `afterImport()`, `beforeExport()` and `beforeImport` methods
    are now real events;
* now you can choose if you want to redirects stderr to `/dev/null`. This
    suppresses the output of executed commands.

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
