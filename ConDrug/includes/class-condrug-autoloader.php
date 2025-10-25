<?php

namespace ConDrug;

class Autoloader
{
    protected static array $prefixes = [
        'ConDrug\\' => CONDRUG_PLUGIN_DIR . 'includes/',
    ];

    public static function register(): void
    {
        spl_autoload_register([static::class, 'autoload']);
    }

    protected static function autoload(string $class): void
    {
        foreach (static::$prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            $relativeClass = substr($class, $len);
            $relativeClass = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);
            $file = $baseDir . static::buildFilename($relativeClass);

            if (is_readable($file)) {
                require_once $file;
            }
        }
    }

    protected static function buildFilename(string $relativeClass): string
    {
        $parts = explode(DIRECTORY_SEPARATOR, $relativeClass);
        $fileParts = [];

        foreach ($parts as $part) {
            $part = preg_replace('/(?<!^)[A-Z]/', '-$0', $part);
            $part = strtolower(str_replace('_', '-', $part));
            $fileParts[] = $part;
        }

        $filename = array_pop($fileParts);
        $path = $fileParts ? implode(DIRECTORY_SEPARATOR, $fileParts) . DIRECTORY_SEPARATOR : '';

        return $path . 'class-condrug-' . $filename . '.php';
    }
}
