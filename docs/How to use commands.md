This plugin provides several commands for database management:

```
Available Commands:

- database_backup.delete_all
- database_backup.export
- database_backup.import
- database_backup.index
- database_backup.rotate
- database_backup.send
```

- [Commands](#commands)
    * [~~`delete_all`~~](#---delete-all---)
    * [`index`](#-index-)
    * [`export`](#-export-)
    * [`import`](#-import-)
    * [~~`rotate`~~](#-rotate-)
    * [~~`send`~~](#---send---)

***

# Commands
## ~~`delete_all`~~
_(deprecated since 2.13.4, removed in 2.14.0)_
~~Deletes all database backup files.~~

**Usage:**
```
cake database_backup.delete_all [-h] [-q] [-v]
```

## `index`
Lists database backups.

**Usage:**
```
cake database_backup.index [-h] [-q] [--reverse] [-v]
```

**Example:**
```
$ bin/cake database_backup.index

Backup files found: 2
+-----------------------------------+-----------+-------------+-----------+-----------------+
| Filename                          | Extension | Compression | Size      | Datetime        |
+-----------------------------------+-----------+-------------+-----------+-----------------+
| backup_mydb_20161113110419.sql.gz | sql.gz    | gzip        | 51,05 KB  | 13/11/16, 11:04 |
| backup_mydb_20161113110414.sql    | sql       | none        | 150,93 KB | 13/11/16, 11:04 |
+-----------------------------------+-----------+-------------+-----------+-----------------+
```

***

## `export`
Exports a database backup.

**Usage:**
```
cake database_backup.export [options]
```

**Options:**
```
--compression, -c Compression type. By default, no compression will be  
used (choices: bzip2|gzip)  
--filename, -f Filename. It can be an absolute path and may contain  
patterns. The compression type will be automatically  
set  
--help, -h Display this help.  
--quiet, -q Enable quiet output.  
--rotate, -r Rotates backups. You have to indicate the number of  
backups you want to keep. So, it will delete all  
backups that are older. By default, no backup will be  
deleted  
--send, -s Sends the backup file via email. You have to indicate  
the recipient's email address  
--timeout, -t Timeout for shell commands. Default value: 60 seconds  
--verbose, -v Enable verbose output.
```

**Example:**
```
$ bin/cake database_backup.export -c gzip

Backup `backups/backup_mydb_20161113165059.sql.gz` has been exported
```

***

## `import`
Imports a database backup.

**Usage:**
```
cake database_backup.import [-h] [-q] [-t] [-v] <filename>
```

**Arguments:**
```
filename Filename. It can be an absolute path
```

**Options:**
```  
--help, -h Display this help.  
--quiet, -q Enable quiet output.  
--timeout, -t Timeout for shell commands. Default value: 60 seconds  
--verbose, -v Enable verbose output.
```

**Example:**
```
$ bin/cake database_backup.index

Backup files found: 3
+-----------------------------------+-----------+-------------+-----------+-----------------+
| Filename                          | Extension | Compression | Size      | Datetime        |
+-----------------------------------+-----------+-------------+-----------+-----------------+
| backup_mydb_20161113165059.sql.gz | sql.gz    | gzip        | 51,05 KB  | 13/11/16, 16:50 |
| backup_mydb_20161113110419.sql.gz | sql.gz    | gzip        | 51,05 KB  | 13/11/16, 11:04 |
| backup_mydb_20161113110414.sql    | sql       | none        | 150,93 KB | 13/11/16, 11:04 |
+-----------------------------------+-----------+-------------+-----------+-----------------+

$ bin/cake database_backup.import backup_mydb_20161113165059.sql.gz
Backup `backups/backup_mydb_20161113165059.sql.gz` has been imported
```

***

## ~~`rotate`~~
_(deprecated since 2.13.5, removed in 2.14.0)_
~~Rotates backups.~~

**Usage:**
```
cake database_backup.rotate [-h] [-q] [-v] <keep>
```

**Arguments:**
```
keep  Number of backups you want to keep. So, it will delete all backups
    that are older
```

**Example:**
```
$ bin/cake database_backup.index

Backup files found: 3
+-----------------------------------+-----------+-------------+-----------+-----------------+
| Filename                          | Extension | Compression | Size      | Datetime        |
+-----------------------------------+-----------+-------------+-----------+-----------------+
| backup_mydb_20161113165059.sql.gz | sql.gz    | gzip        | 51,05 KB  | 13/11/16, 16:50 |
| backup_mydb_20161113110419.sql.gz | sql.gz    | gzip        | 51,05 KB  | 13/11/16, 11:04 |
| backup_mydb_20161113110414.sql    | sql       | none        | 150,93 KB | 13/11/16, 11:04 |
+-----------------------------------+-----------+-------------+-----------+-----------------+

$ bin/cake database_backup.rotate 1
Deleted backup files: 2
```

***

## ~~`send`~~
_(deprecated since 2.13.4, removed in 2.14.0)_
~~Sends a backup file via email.~~

**Usage:**
```
cake database_backup.send [-h] [-q] [-v] <filename> <recipient>
```

**Arguments:**
```
filename  Filename. It can be an absolute path
recipient  Recipient's email address
```

**Example:**
```
$ bin/cake database_backup.index

Backup files found: 3
+-----------------------------------+-----------+-------------+-----------+-----------------+
| Filename                          | Extension | Compression | Size      | Datetime        |
+-----------------------------------+-----------+-------------+-----------+-----------------+
| backup_mydb_20161113165059.sql.gz | sql.gz    | gzip        | 51,05 KB  | 13/11/16, 16:50 |
| backup_mydb_20161113110419.sql.gz | sql.gz    | gzip        | 51,05 KB  | 13/11/16, 11:04 |
| backup_mydb_20161113110414.sql    | sql       | none        | 150,93 KB | 13/11/16, 11:04 |
+-----------------------------------+-----------+-------------+-----------+-----------------+

$ bin/cake database_backup.send backup_mydb_20161113110414.sql mymail@example.com
Backup `backup_mydb_20161113110414.sql` was sent via mail
```