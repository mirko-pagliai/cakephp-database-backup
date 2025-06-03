# How to use the BackupManager utility

## Methods

### ~~`delete()`~~
_(deprecated since 2.13.5, removed in 2.14.0)_
```php
delete(string $filename)
```
~~It deletes a backup file.~~

**Parameters for `delete()`**

*string $filename*  
Filename of the backup that you want to delete. The path can be relative to the backup directory.

***

### ~~`deleteAll()`~~
_(deprecated since 2.13.5, removed in 2.14.0)_
```php
deleteAll()
```
~~It deletes all backup files.~~

***

### ~~`index()`~~
_(deprecated since 2.15.0)_
```php
index()
```
It returns a list of database backups.  
Note that it lists only the backups in the backup directory.

Backups are returned as a `Collection` of arrays.
Example (calling `toArray()` method):

```php
[
  (int) 0 => [
    'filename' => 'backup_test_1698229269.sql.bz2',
    'extension' => 'sql.bz2',
    'compression' => 'bzip2',
    'size' => (int) 3175,
    'datetime' => object(Cake\I18n\FrozenTime) id:0 {
      'time' => '2023-10-25 10:21:09.000000+00:00'
      'timezone' => 'UTC'
      'fixedNowTime' => false
    }
  ],
  (int) 1 => [
    'filename' => 'backup_test_1698229268.sql.gz',
    'extension' => 'sql.gz',
    'compression' => 'gzip',
    'size' => (int) 3725,
    'datetime' => object(Cake\I18n\FrozenTime) id:1 {
      'time' => '2023-10-25 10:21:08.000000+00:00'
      'timezone' => 'UTC'
      'fixedNowTime' => false
    }
  ]
]
```
Note that up until version `2.12.1`, it returned a collection of `Entities` (and not arrays)

***

### ~~`rotate()`~~
_(deprecated since 2.15.0)_
```php
rotate(int $rotate)
```
It rotates backups.  
You must indicate the number of backups you want to keep. So, it will delete all backups that are older.

**Parameters for `rotate()`**

*int $rotate*  
Number of backups that you want to keep

***

### ~~`send()`~~
_(deprecated since 2.13.4, removed in 2.14.0)_
```php
send(string $filename, string $to)
```
~~It sends a backup file via email.~~

**Parameters for `send()`**

*string $filename*  
Filename of the backup that you want to send via email. The path can be relative to the backup directory.  

*string $recipient*  
Recipient's email address
