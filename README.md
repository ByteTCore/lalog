# Lalog - Laravel SQL Query Logger

[![Latest Version](https://img.shields.io/packagist/v/ByteTCore/lalog)](https://packagist.org/packages/ByteTCore/lalog)
[![License](https://img.shields.io/packagist/l/ByteTCore/lalog)](https://packagist.org/packages/ByteTCore/lalog)
[![Latest Stable Version](http://poser.pugx.org/ByteTCore/lalog/v)](https://packagist.org/packages/ByteTCore/lalog)
[![Total Downloads](http://poser.pugx.org/ByteTCore/lalog/downloads)](https://packagist.org/packages/ByteTCore/lalog)
[![Latest Unstable Version](http://poser.pugx.org/ByteTCore/lalog/v/unstable)](https://packagist.org/packages/ByteTCore/lalog)

Automatically log all SQL queries to files with **binding interpolation**, **time tracking**, and **file rotation** support. Perfect for debugging and performance analysis during development.

## Features

- 🔍 **Full SQL Logging** — Captures every query with bindings interpolated
- ⏱️ **Query Time** — Records execution time for each query
- 📁 **File Rotation** — Automatically creates new files when size limit is reached
- 🗑️ **Auto Clear** — Optionally clears previous logs each request cycle
- ⚙️ **Configurable** — Disk, directory, format, max size, extension — all customizable
- 🚀 **Zero Config** — Works out of the box with sensible defaults

## Requirements

- PHP 8.0+
- Laravel 9.x+

## Installation

```bash
composer require bytetcore/lalog --dev
```

The package auto-discovers its ServiceProvider. No manual registration needed.

### Publish Config (Optional)

```bash
php artisan vendor:publish --tag=lalog-config
```

## Configuration

Add to your `.env`:

```env
APP_LOG_QUERY=true
```

### Available Options

| Option | Env Variable | Default | Description |
|---|---|---|---|
| `enabled` | `APP_LOG_QUERY` | `false` | Enable/disable query logging |
| `disk` | `LALOG_DISK` | `local` | Storage disk (any disk from `filesystems.php`) |
| `directory` | `LALOG_DIRECTORY` | `query` | Directory within the disk |
| `max_size` | `LALOG_MAX_SIZE` | `2000000` | Max file size in bytes (~2MB) |
| `format` | `LALOG_FORMAT` | `sql-{date}` | File name format (`{date}` placeholder) |
| `date_format` | `LALOG_DATE_FORMAT` | `Y-m-d` | PHP date format for `{date}` |
| `extension` | `LALOG_EXTENSION` | `sql` | File extension |
| `clear_on_start` | `LALOG_CLEAR_ON_START` | `true` | Delete current day's log on start |

### Example Config

```php
// config/lalog.php
return [
    'enabled'        => env('APP_LOG_QUERY', false),
    'disk'           => env('LALOG_DISK', 'local'),
    'directory'      => env('LALOG_DIRECTORY', 'query'),
    'max_size'       => env('LALOG_MAX_SIZE', 2000000),
    'format'         => env('LALOG_FORMAT', 'sql-{date}'),
    'date_format'    => env('LALOG_DATE_FORMAT', 'Y-m-d'),
    'extension'      => env('LALOG_EXTENSION', 'sql'),
    'clear_on_start' => env('LALOG_CLEAR_ON_START', true),
];
```

## Output Example

File: `storage/app/query/sql-2026-04-03.sql`

```sql
----------START---------
Date: 2026-04-03 13:45:12
Time query: 2.34(ms)
select * from `users` where `email` = 'john@example.com' limit 1;
----------END----------

Date: 2026-04-03 13:45:12
Time query: 0.89(ms)
select * from `posts` where `user_id` = '1' order by `created_at` desc;
----------END----------
```

## File Rotation

When a log file exceeds `max_size`, new files are created with an incremented index:

```
query/sql-2026-04-03.sql      (2MB reached)
query/sql-2026-04-03-1.sql    (next file)
query/sql-2026-04-03-2.sql    (and so on)
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

See [AUTHORS](AUTHORS.md) for the list of contributors.

## License

Licensed under the Apache License 2.0. Please see [License File](LICENSE) for more information.
