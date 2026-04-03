<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Query Logging
    |--------------------------------------------------------------------------
    |
    | Determines whether SQL query logging is active. When disabled, no queries
    | will be logged regardless of other settings. Uses APP_LOG_QUERY env
    | variable by default.
    |
    */

    'enabled' => env('APP_LOG_QUERY', false),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk where query log files will be stored.
    | This should be a valid disk defined in your config/filesystems.php.
    |
    | Supported: Any disk from config('filesystems.disks')
    |
    */

    'disk' => env('LALOG_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Log Directory
    |--------------------------------------------------------------------------
    |
    | The directory within the configured disk where log files are stored.
    |
    */

    'directory' => env('LALOG_DIRECTORY', 'query'),

    /*
    |--------------------------------------------------------------------------
    | Max File Size (bytes)
    |--------------------------------------------------------------------------
    |
    | Maximum size of a single log file in bytes. When a file reaches this
    | limit, a new file with an incremented index will be created.
    |
    | Default: 2,000,000 bytes (~2MB)
    |
    */

    'max_size' => env('LALOG_MAX_SIZE', 2000000),

    /*
    |--------------------------------------------------------------------------
    | File Format
    |--------------------------------------------------------------------------
    |
    | The format pattern for log file names. Available placeholders:
    |
    |   {date}  - Current date formatted by 'date_format' config
    |   {index} - Auto-incremented index when file exceeds max_size
    |
    | Extension is automatically appended based on 'extension' config.
    |
    */

    'format' => env('LALOG_FORMAT', 'sql-{date}'),

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | PHP date format used for the {date} placeholder in file names.
    |
    | Examples: 'Y-m-d' => 2026-04-03, 'Y-m-d_H' => 2026-04-03_13
    |
    */

    'date_format' => env('LALOG_DATE_FORMAT', 'Y-m-d'),

    /*
    |--------------------------------------------------------------------------
    | File Extension
    |--------------------------------------------------------------------------
    |
    | The file extension for log files.
    |
    */

    'extension' => env('LALOG_EXTENSION', 'sql'),

    /*
    |--------------------------------------------------------------------------
    | Clear on Start
    |--------------------------------------------------------------------------
    |
    | If true, the current day's log file will be deleted at the start of
    | each request cycle before logging begins. Useful during development
    | to keep only the latest request's queries.
    |
    */

    'clear_on_start' => env('LALOG_CLEAR_ON_START', true),

    /*
    |--------------------------------------------------------------------------
    | Log Separator
    |--------------------------------------------------------------------------
    |
    | The separator pattern used between individual query entries.
    |
    */

    'separator_start' => '----------START---------',
    'separator_end' => '----------END----------',

];
