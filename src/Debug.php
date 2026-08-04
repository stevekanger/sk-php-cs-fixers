<?php

declare(strict_types=1);

namespace SkPhpCsFixers;

final class Debug {
    private static function getRootDir(string $path = '') {
        return dirname(__DIR__) . $path;
    }

    /**
     * Converts all whitespace to readable text for logging.
     *
     * @param string $str The string to convert
     */
    public static function convertWhitespace(string $str): string {
        return strtr($str, [
            ' ' => '_',
            "\t" => '\\t',
            "\n" => '\\n',
            "\r" => '\\r',
            "\0" => '\\0',
            "\v" => '\\v',
            "\f" => '\\f',
        ]);
    }

    /**
     * Logs to the debug.log file in the root directory.
     *
     * @param mixed ...$items The items to log
     */
    public static function log(mixed ...$items): void {
        $log_file = self::getRootDir('/debug.log');
        $log = '';

        foreach ($items as $item) {
            if (is_array($item) || is_object($item)) {
                $log .= print_r($item, true);
            } elseif (false === $item) {
                $log .= 'false';
            } elseif (null === $item) {
                $log .= 'null';
            } else {
                $log .= ($item ?? '');
            }
        }

        $log .= "\n";

        file_put_contents($log_file, $log, \FILE_APPEND | \LOCK_EX);
    }

    /**
     * Logs to the debug.log file in the root directory with the file and line info.
     *
     * @param mixed ...$items The items to log
     */
    public static function logInfo(mixed ...$items): void {
        $log_file = self::getRootDir('/debug.log');

        $backtrace = debug_backtrace();
        $caller = array_shift($backtrace);
        $line = (string) $caller['line'];
        $full_file = explode('/', $caller['file']);
        $filename = end($full_file);

        $log = '';
        $log .= "================================================================================\n";
        $log .= "line: {$line}\n";
        $log .= "file: {$filename}\n";
        $log .= "--------------------------------------------------------------------------------\n";

        foreach ($items as $item) {
            if (is_array($item) || is_object($item)) {
                $log .= print_r($item, true);
            } elseif (false === $item) {
                $log .= 'false';
            } elseif (null === $item) {
                $log .= 'null';
            } else {
                $log .= ($item ?? '');
            }
        }

        $log .= "\n";

        file_put_contents($log_file, $log, \FILE_APPEND | \LOCK_EX);
    }
}
