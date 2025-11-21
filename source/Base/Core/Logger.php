<?php

namespace Source\Base\Core;

use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Interfaces\LoggerInterface;

/**
 * Class LogLogicManager
 * @package Source\LogicManagers
 */
class Logger implements LoggerInterface
{
    /**
     * @var string|null
     */
    public static ?string $log_dir = null;

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function emergency(string $message, array $context = null): void
    {
        self::log( 'EMERGENCY | ' . $message,'emergency',  $context);
    }

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function alert(string $message, array $context = null): void
    {
        self::log( 'ALERT | ' . $message,'alert',  $context);
    }

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function critical(string $message, array $context = null): void
    {
        self::log( 'CRITICAL | ' . $message, 'critical', $context);
    }

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function error(string $message, array $context = null): void
    {
        self::log( 'ERROR | ' . $message, 'error', $context);
    }

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function warning(string $message, array $context = null): void
    {
        self::log( 'WARNING | ' . $message,'warning', $context);
    }

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function notice(string $message, array $context = null): void
    {
        self::log( 'ERROR | ' . $message,'notice', $context);
    }

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function info(string $message, array $context = null): void
    {
        self::log('INFO | ' . $message, 'info',  $context);
    }

    /**
     * @param string $message
     * @param string $error_filename
     * @param array|null $context
     * @return void
     */
    public static function log(string $message, string $error_filename = 'error', array $context = null): void
    {
        $debug = debug_backtrace();
        $filename = self::getFilenameFromDebug($debug);

        $path = (static::$log_dir ?: Path::RESOURCES_LOGS_DIR);
        self::createLogDirectories($path, $filename);

        $file = $filename . '/log/' . $error_filename . '.log';
        self::archiveOldLogs($path, $file, $filename, $error_filename);

        self::writeToLogFile($path, $file, $debug, $message);
    }

    /**
     * @param $debug
     * @return string
     */
    private static function getFilenameFromDebug($debug): string
    {
        $pop = array_pop($debug);
        preg_match('#(?<=/)[^/]*?(?=.php)#', $pop['file'], $filename);

        return $filename[0];
    }

    /**
     * @param string $path
     * @param string $filename
     * @return void
     */
    private static function createLogDirectories(string $path, string $filename): void
    {
        if (!file_exists($path)) {
            mkdir($path);
        }

        if (!file_exists($path  . '/' .  $filename)) {
            mkdir($path  . '/' .  $filename);
        }

        if (!file_exists($path . '/' . $filename . '/log/')) {
            mkdir($path  . '/' .  $filename . '/log/');
        }
    }

    /**
     * @param $path
     * @param $file
     * @param $filename
     * @param $error_filename
     * @return void
     */
    private static function archiveOldLogs($path, $file, $filename, $error_filename): void
    {
        clearstatcache(true, $path . $file);

        if (file_exists($path . $file) && (filesize($path . $file) > 104857600)) {
            file_put_contents($path . $file, '');
//            $new_file = $filename . '_' . time() . '.log';
//            $error_folder = 'zip/';
//
//            if (!file_exists($path . '/' . $filename . '/' . $error_folder)) {
//                mkdir($path . '/' . $filename . '/' . $error_folder);
//            }
//
//            rename($path . $file, $path . '/' . $filename . '/' . $error_folder . $new_file);
//            exec("zip -r -Z bzip2 $path/$filename/$error_folder$error_filename" . "_" .
//                time() . ".zip $path/$filename/$error_folder$new_file && rm $path/$filename/$error_folder$new_file");
        }
    }

    /**
     * @param $path
     * @param $file
     * @param $debug
     * @param $message
     * @return void
     */
    private static function writeToLogFile($path, $file, $debug, $message): void
    {
        file_put_contents(
            $path . $file,
            gmdate('[d.m.y H:i:s', time() + 2*3600) .
            '] LINE - ' . ($debug[0]['line'] ?? 'fail') . ' | ' . $message . "\n",
            FILE_APPEND
        );
    }

    /**
     * @param string $message
     * @param array|null $context
     * @return void
     */
    public static function debug(string $message, array $context = null): void
    {
        self::log('DEBUG | ' . $message, 'debug',  $context);
    }
}