<?php

namespace Source\Base\LogicManagers;

/**
 * Class FileManager
 *
 * The FileManager class provides methods to work with file paths and file data.
 */
class FileManager
{
    /**
     * Retrieves a random proxy from a given proxy file.
     *
     * @param string $proxy_file The path to the proxy file. Defaults to the constant PROXY_FILE.
     * @return array|null An array containing the random proxy, or null if the proxy file is empty or does not exist.
     */
    public static function fetchRandomProxy(string $proxy_file = 'proxy.txt'): ?array
    {
        $data = file($proxy_file);

        return explode('@', $data[rand(0, count($data))]) ?? null;
    }
    /**
     * Retrieves all files within a directory and its subdirectories.
     *
     * @param string $path The path to the directory.
     * @return array An array containing all file paths within the directory and its subdirectories.
     */
    public static function getAllDirFiles(string $path): array
    {
        $files = [];
        $path = preg_replace("#/\Z#", '', $path);
        $folders = [$path];


        do {
            $folder = array_shift($folders);
            $catalog = scandir($folder);

            $current_path = preg_replace('#(?<=/)[\w\d_]*\.[\w\d_]*?(?=\Z)#', '', $folder);

            foreach ($catalog as $file) {
                if (!in_array($file, ['.', '..'])) {
                    if (str_contains($file, '.')) {
                        $files[] = $current_path . '/' . $file;
                    } else {
                        $folders[] = $current_path . '/' . $file;
                    }
                }
            }
        } while($folders != []);



        return $files;
    }

    /**
     * Retrieves the file path and name for a specific file within an array of files.
     *
     * @param array $files An array containing file paths.
     * @param string $file_name The name of the file to search for.
     * @return array An associative array containing the file name and its path, or null values if the file is not found.
     */
    public static function getFileData(array $files, string $file_name): array
    {
        $exec_file_path = null;

        foreach ($files as $file) {
            $explode = explode('/', $file);

            $count = count($explode);
            $exec_file_path = str_replace($explode[$count-1], '', $file);
            $current_file_name = str_replace('.php', '', $explode[$count-1]);

            if ($current_file_name == $file_name) {
                break;
            } else {
                $exec_file_path = null;
            }
        }

        return [
            'file_name' => $file_name,
            'path' => $exec_file_path
        ];
    }
}