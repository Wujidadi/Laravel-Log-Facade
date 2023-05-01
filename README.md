# Laravel Log Facade

A simple log facade tool based on Laravel.

## Installation

```bash
composer require "wujidadi/laravel-log-facade"
```

After installing, you should create your own `LogFacade` class extended `Wujidadi\LogFacade\LogFacade` for your own different log channels.

`Wujidadi\LogFacade\MicrosecondFormatter`, which makes log timestamps microsecond-level, can be used in the "tap" option by each channel in `config/logging.php`, just like:
```php
return [

    ...

    'channels' => [

        ...

        'daily' => [
            'driver' => 'daily',
            'tap' => [\Wujidadi\LogFacade\MicrosecondFormatter::class],
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        ...

    ],

];
```