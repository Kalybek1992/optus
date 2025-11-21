<?php

namespace Source\Base\LogicManagers;

class ConfigManager
{
    /**
     * @param string $main_dir
     * @return void
     */
    public static function loadConfigs(string $main_dir): void
    {
        $current_hash = self::generateFilesHash("$main_dir/configs/");
        $config_cache_dir = $main_dir . "/resources/config_cache/";

        if (!self::isCacheValid($main_dir, $current_hash)) {
            if (!file_exists($config_cache_dir)) {
                mkdir($config_cache_dir, 0777);
            }

            self::generateConfigClasses($main_dir);
            file_put_contents($config_cache_dir . $current_hash, '');
        }
    }

    /**
     * @param string $main_dir
     * @param string $current_hash
     * @return bool
     */
    private static function isCacheValid(string $main_dir, string $current_hash): bool
    {
        return file_exists("$main_dir/$current_hash");
    }

    /**
     * @param $config_dir
     * @return string
     */
    private static function generateFilesHash($config_dir): string
    {
        $files = glob($config_dir . '/*.conf');
        $timestamps = array_map('filemtime', $files);

        return md5(implode('', $timestamps));
    }

    /**
     * @param $main_dir
     * @return void
     */
    private static function generateConfigClasses($main_dir)
    {
        $config_files = glob($main_dir . '/config//*.conf');
        $main_dir .= '/';

        if (!file_exists($main_dir . "/source/Base/Constants/")) {
            mkdir($main_dir ."/source/Base/Constants", 0644);
            mkdir($main_dir . "/source/Base/Constants/Settings", 0644);
        } else {
            $files = scandir(
                $main_dir .
                "/source/Base/Constants/Settings/"
            );

            foreach ($files as $file) {
                if (!in_array($file, ['.','..','.gitignore'])) {
                    unlink(
                        $main_dir .
                        "/source/Base/Constants/Settings/" .
                        $file
                    );
                }
            }
        }

        foreach ($config_files as $file) {
            if (str_contains($file, '.default.')) {
                continue;
            }

            $class_name = basename($file, '.conf');
            $properties = [];

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if (str_contains($file, 'path')) {
                $properties['MAIN_DIR'] = $main_dir;
            }

            foreach ($lines as $line) {
                $line = str_replace("'", '', $line);

                if (str_contains($line, '=') && !str_starts_with($line, '#')) {
                    list($key, $value) = explode('=', $line, 2);

                    if (str_contains($file, 'path') && $key != 'MAIN_DIR') {
                        $value = "$main_dir" . trim($value);
                    }

                    $properties[$key] = $value;
                }
            }

            $class_code = "<?php\n\nnamespace Source\Base\Constants\Settings;\n\nclass " . trim(ucfirst($class_name)) . " \n{\n";

            foreach ($properties as $key => $value) {
                $class_code .= "    public const string $key = '$value';\n\n";
            }

            $class_code .= "}\n";

            file_put_contents(
                $main_dir .
                "source/Base/Constants/Settings/" .
                trim(ucfirst($class_name)) . ".php",
                $class_code
            );
        }
    }
}