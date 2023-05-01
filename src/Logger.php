<?php

namespace Wujidadi\LogFacade;

use Countable;
use Illuminate\Support\Facades\Log;

/**
 * Logs by channels (called by LogFacade) and levels.
 *
 * @method emergency($message, ...$context)
 * @method alert($message, ...$context)
 * @method critical($message, ...$context)
 * @method error($message, ...$context)
 * @method warning($message, ...$context)
 * @method notice($message, ...$context)
 * @method info($message, ...$context)
 * @method debug($message, ...$context)
 */
class Logger
{
    private string $channel;

    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }

    public function __call(string $name, array|Countable $arguments)
    {
        $count = count($arguments);
        if ($count > 1) {
            for ($i = 1; $i < $count; $i++) {
                if (is_object($arguments[$i]) || is_array($arguments[$i])) {
                    $arguments[$i] = json_encode($arguments[$i], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }
        }
        $message = call_user_func_array('sprintf', $arguments);
        if ($this->channel == 'laravel') {
            Log::$name($message);
        } else {
            Log::channel($this->channel)->$name($message);
        }
    }
}
