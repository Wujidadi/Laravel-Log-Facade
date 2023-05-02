# Laravel Log Facade

A simple log facade tool based on Laravel.

## Installation

```bash
composer require "wujidadi/laravel-log-facade"
```

After installing, you should create your own `LogFacade` class extended `Wujidadi\LogFacade\LogFacade` for your own different log channels.

Let's say you hava a class `App\Utilities\LogFacade` which extends `Wujidadi\LogFacade\LogFacade`:
```php
<?php

namespace App\Utilities;

use Wujidadi\LogFacade\LogFacade as Facade;
use Wujidadi\LogFacade\Logger;

class LogFacade extends Facade
{
    public static function mychannel(): Logger
    {
        return new Logger('mychanel');
    }
}
```

And register the channel in your `config/logging.php`:
```php
return [

    ...

    'channels' => [

        'mychannel' => [
            'driver' => 'daily',
            'tap' => [\Wujidadi\LogFacade\MicrosecondFormatter::class],
            'path' => storage_path('logs/mychannel/mymessage.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'permission' => 0664,
            'replace_placeholders' => true,
        ],

        ...

    ],

];
```

Now you can use `App\Utilities\LogFacade::mychannel()` to log your messages.


## Microsecond-level Timestamps

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