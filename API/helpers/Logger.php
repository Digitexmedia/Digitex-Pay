<?php

class Logger
{
    public static function log(string $file, string $data): void
    {
        $path = __DIR__ . "/../logs/" . $file;

        file_put_contents(
            $path,
            date("Y-m-d H:i:s ") . $data . PHP_EOL,
            FILE_APPEND
        );
    }
}
