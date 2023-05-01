<?php

namespace Wujidadi\LogFacade;

/**
 * Log facade class, should be extended while being used in practice for different channels.
 */
class LogFacade
{
    /**
     * Writes a log to Laravel main channel.
     *
     * @return Logger
     */
    public static function laravel(): Logger
    {
        return new Logger('laravel');
    }
}
