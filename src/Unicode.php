<?php

namespace Wujidadi\LogFacade;

/**
 * Handles Unicode strings in the log.
 */
class Unicode
{
    /**
     * Makes a JSON string unescaped.
     *
     * @param string $string
     * @return false|string
     */
    public static function unescape(string $string): false|string
    {
        if ($jsonObj = json_decode($string)) {
            return json_encode($jsonObj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $jsonObj = json_decode('["' . $string . '"]');
        return trim(json_encode($jsonObj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), '[]"');
    }
}