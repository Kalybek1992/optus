<?php

namespace Source\Project\LogicManagers\HtmlLM;


use Source\Base\Core\Logger;

class HtmlLM
{

    public static function renderCategoryLevels(array $categories, int $level = 0, array $path = []): string
    {
        $html = "<template x-if=\"level === {$level} && JSON.stringify(path.slice(0, {$level})) === '".htmlspecialchars(json_encode($path, JSON_UNESCAPED_UNICODE))."'\">
             <ul class=\"space-y-2\">\n";

        foreach ($categories as $key => $value) {
            if (is_array($value)) {
                if (is_string($key)) {
                    // Подкатегория
                    $item_name = htmlspecialchars($key);
                    $full_path = array_merge($path, [$key]);
                    $json_path = htmlspecialchars(json_encode($full_path, JSON_UNESCAPED_UNICODE));
                    $next_level = $level + 1;

                    $html .= <<<HTML
                    <li class="flex justify-between items-center bg-gray-50 hover:bg-gray-100 rounded-lg px-4 py-2">
                        <span @click="open = false; path = {$json_path}" class="cursor-pointer">{$item_name}</span>
                        <button class="ml-2 w-20 h-8 flex items-center justify-center border border-gray-300 rounded-md text-xl text-gray-600 hover:bg-gray-200 transition"
                                @click="level = {$next_level}; path = {$json_path}">→</button>
                    </li>
                    HTML;
                } else {
                    foreach ($value as $leaf) {
                        $item_name = htmlspecialchars($leaf);
                        $full_path = array_merge($path, [$leaf]);
                        $json_path = htmlspecialchars(json_encode($full_path, JSON_UNESCAPED_UNICODE));
                        $html .= <<<HTML
                    <li @click="open = false; path = {$json_path}" class="cursor-pointer px-4 py-2 bg-green-50 hover:bg-green-100 rounded-lg text-green-800 font-medium">{$item_name}</li>
                    HTML;
                    }
                }
            } else {
                $item_name = htmlspecialchars($value);
                $full_path = array_merge($path, [$value]);
                $json_path = htmlspecialchars(json_encode($full_path, JSON_UNESCAPED_UNICODE));
                $html .= <<<HTML
                    <li @click="open = false; path = {$json_path}" class="cursor-pointer px-4 py-2 bg-green-50 hover:bg-green-100 rounded-lg text-green-800 font-medium">{$item_name}</li>
                    HTML;
            }
        }

        $html .= "</ul>\n</template>\n";

        foreach ($categories as $key => $value) {
            if (is_array($value) && is_string($key)) {
                $full_path = array_merge($path, [$key]);
                $html .= self::renderCategoryLevels($value, $level + 1, $full_path);
            }
        }


        return $html;
    }


    public static function renderCategoryLevelsAdd(array $categories, int $level = 0, array $path = []): string
    {
        $html = "<template x-if=\"level === {$level} && JSON.stringify(path.slice(0, {$level})) === '" . htmlspecialchars(json_encode($path, JSON_UNESCAPED_UNICODE)) . "'\">
            <ul class=\"space-y-2\">\n";

        foreach ($categories as $key => $value) {
            if (is_array($value)) {
                if (is_string($key)) {
                    $item_name = htmlspecialchars($key);
                    $full_path = array_merge($path, [$key]);
                    $json_path = htmlspecialchars(json_encode($full_path, JSON_UNESCAPED_UNICODE));
                    $next_level = $level + 1;

                    $html .= <<<HTML
            <li class="category-name flex justify-between items-center bg-gray-50 hover:bg-gray-100 rounded-lg px-4 py-2">
                <span @click="open = false; path = {$json_path}" class="cursor-pointer">{$item_name}</span>
                <div class="flex items-center gap-2">
                    <button
                        class="addCategories ml-2 w-20 h-8 flex items-center justify-center border border-green-400 rounded-md text-xl text-green-700 hover:bg-green-200 transition"
                        onclick="delCategories('$item_name', '$next_level')">
                           <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                     </button>
                    <button
                        class="ml-2 w-20 h-8 flex items-center justify-center border border-gray-300 rounded-md text-xl text-gray-600 hover:bg-gray-200 transition"
                        @click="level = {$next_level}; path = {$json_path}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </button>
                </div>
            </li>
            HTML;
                } else {
                    foreach ($value as $leaf) {
                        $item_name = htmlspecialchars($leaf);
                        $full_path = array_merge($path, [$leaf]);
                        $json_path = htmlspecialchars(json_encode($full_path, JSON_UNESCAPED_UNICODE));
                        $html .= <<<HTML
            <li class="category-name flex justify-between items-center bg-green-50 hover:bg-green-100 rounded-lg px-4 py-2">
                <span @click="open = false; path = {$json_path}" class="cursor-pointer text-green-800 font-medium">{$item_name}</span>
                <button
                    class="addCategories ml-2 w-20 h-8 flex items-center justify-center border border-green-400 rounded-md text-xl text-green-700 hover:bg-green-200 transition"
                    data-name="{$item_name}" 
                    onclick="delCategories('$item_name', null)">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                       </svg>
                </button>
            </li>
            HTML;
                    }
                }
            } else {
                $item_name = htmlspecialchars($value);
                $full_path = array_merge($path, [$value]);
                $json_path = htmlspecialchars(json_encode($full_path, JSON_UNESCAPED_UNICODE));
                $html .= <<<HTML
            <li class="category-name flex justify-between items-center bg-green-50 hover:bg-green-100 rounded-lg px-4 py-2">
                <span @click="open = false; path = {$json_path}" class="cursor-pointer text-green-800 font-medium">{$item_name}</span>
                <div class="flex items-center gap-2">
                <button
                    class="addCategories ml-2 w-20 h-8 flex items-center justify-center border border-green-400 rounded-md text-xl text-green-700 hover:bg-green-200 transition"
                    data-name="{$item_name}" 
                    onclick="delCategories('$item_name', null)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                    <button
                    class="addCategories ml-2 w-20 h-8 flex items-center justify-center border border-green-400 rounded-md text-xl text-green-700 hover:bg-green-200 transition"
                    data-name="{$item_name}" 
                    onclick="addCategoriesAtLevel('$item_name')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </button>
                </div>
            </li>
            HTML;
            }
        }

        // Добавляем кнопку "Добавить категорию" внизу каждого списка
        $current_path_json = htmlspecialchars(json_encode($path, JSON_UNESCAPED_UNICODE));
        $html .= <<<HTML
        <li class="flex justify-center mt-2">
            <button
                class="w-full max-w-xs py-2 border border-blue-500 rounded-md text-blue-600 hover:bg-blue-100 transition"
                onclick="addCategoriesAtLevel('$current_path_json')">
                Добавить категорию
            </button>
        </li>
        HTML;

        $html .= "</ul>\n</template>\n";

        foreach ($categories as $key => $value) {
            if (is_array($value) && is_string($key)) {
                $full_path = array_merge($path, [$key]);
                $html .= self::renderCategoryLevelsAdd($value, $level + 1, $full_path);
            }
        }

        return $html;
    }




    public static function renderCategoryNot(): string
    {
        return '<div>Нет категории!!!</div>';
    }

}