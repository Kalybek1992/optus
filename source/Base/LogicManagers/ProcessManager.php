<?php

namespace Source\Base\LogicManagers;

/**
 * Class ProcessManager
 *
 * The ProcessManager class provides methods for managing processes in PHP.
 */
class  ProcessManager
{
    /**
     * Runs the count process.
     *
     * @param string $name_process The name of the process to count.
     * @param mixed|null $args (Optional) Additional arguments to pass to the process.
     * @param int $count (Optional) Number of times to run the process.
     * @return void
     */
    public static function runCountProcess(string $name_process, mixed $args = null, int $count = 1): void
    {
        // Указываем путь к PHP
        $phpBin = '/www/server/php/83/bin/php';

        // Полный путь к файлу cron
        $fullPath = "/www/wwwroot/optus.su/app/resources/cron/" . $name_process;

        // Проверка — существует ли скрипт
        if (!file_exists($fullPath)) {
            echo "❌ Cron script not found: $fullPath\n";
            return;
        }

        usleep(100000);

        // Считаем текущие процессы
        $count_proc = static::countProcess("$name_process $args");

        echo "Сейчас процессов: $count_proc\n";
        echo "$phpBin $fullPath $args > /dev/null 2>/dev/null &\n";

        // Запускаем недостающие процессы
        for ($i = 0; $i < $count - $count_proc; $i++) {

            exec("$phpBin $fullPath $args > /dev/null 2>/dev/null &");

            usleep(200000);
        }
    }


    /**
     * Counts the number of processes running with the given name.
     *
     * @param string $name_process The name of the process to count.
     *
     * @return int The number of processes with the given name.
     */
    public static function countProcess(string $name_process): int
    {
        $name_process = trim($name_process);

        exec ( "ps ax| grep '$name_process'", $output);

        $count = 0;

        foreach ($output as $value) {
            usleep(100);
            $grep = !str_contains($value, ' grep ');

            if ($grep && str_contains($value, "php8.3 $name_process")) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Kills a process with the specified name.
     *
     * @param string $name_process The name of the process to kill.
     *
     * @return void
     */
    public static function killProcess(string $name_process): void
    {
        exec ( "ps ax| grep 'php8.3 $name_process'", $output);

        if ($output) {
            foreach ($output as $value) {
                usleep(100);
                $explode = explode(' ', trim($value));
                $grep = !str_contains($value, ' grep ');

                if ($grep && str_contains($value, "php8.3 $name_process")) {
                    exec("kill $explode[0]");
                    var_dump("kill $explode[0]");
                }
            }
        }
    }
}