<?php

use Illuminate\Database\Eloquent\Relations\Relation;

if (!function_exists('getMorphedModelName')) {
    /**
     * Get the morphed model name for a given class.
     *
     * @param string $class
     * @return string
     */
    function getMorphedModelName(string $class): string
    {
        return array_search($class, Relation::morphMap()) ?: class_basename($class);
    }
}

if (!function_exists('getAppVersion')) {
    /**
     * Get the current application version.
     *
     * @return string
     */
    function getAppVersion()
    {
        return env('APP_VERSION', '1.0.0'); // Default version if not set in .env
    }
}