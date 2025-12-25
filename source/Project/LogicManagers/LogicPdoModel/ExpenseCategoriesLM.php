<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\ExpenseCategories;


/**
 *
 */
class ExpenseCategoriesLM
{

    public static function insertNewCategories(array $data)
    {
        $builder = ExpenseCategories::newQueryBuilder()
            ->insert($data);

        return PdoConnector::execute($builder);
    }

    public static function getExpenseCategories($supplier_id = null, $project = 0): array
    {
        $categories_db = [];

        $builder = ExpenseCategories::newQueryBuilder()
            ->select([
                'p.id AS parent_id',
                'p.name AS parent_name',
                'c.id AS child_id',
                'c.name AS child_name',
            ])
            ->from('category_relations r')
            ->leftJoin('expense_categories p')
            ->on([
                'r.parent_id = p.id',
            ])
            ->leftJoin('expense_categories c')
            ->on([
                'r.child_id = c.id',
            ]);

        $builder
            ->where([
                'p.project =' . $project,
            ]);

        if ($supplier_id) {
            $builder
                ->where([
                    'p.supplier_id =' . $supplier_id,
                ])
                ->orderBy('p.id, c.id', '');
        } else {
            $builder
                ->where([
                    'p.supplier_id IS NULL',
                ])
                ->orderBy('p.id, c.id', '');
        }


        $categories = PdoConnector::execute($builder);


        $builder = ExpenseCategories::newQueryBuilder()
            ->select([
                'ec.id AS parent_id',
                'ec.name AS parent_name',
            ])
            ->from('expense_categories ec')
            ->leftJoin('category_relations cr')
            ->on([
                'ec.id = cr.parent_id',
            ]);

        if ($supplier_id) {
            $builder
                ->where([
                    'ec.is_parsed = 1',
                    'cr.parent_id IS NULL',
                    'ec.supplier_id =' . $supplier_id,
                ]);
        } else {
            $builder
                ->where([
                    'ec.is_parsed = 1',
                    'cr.parent_id IS NULL',
                    'ec.supplier_id IS NULL',
                ]);
        }

        $builder
            ->where([
                'ec.project =' . $project,
            ]);

        $categories_no_heir = PdoConnector::execute($builder);

        if (!$categories && !$categories_no_heir) {
            return [];
        }


        foreach ($categories as $category) {
            $categories_db[] = [
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent_name,
                'child_id' => $category->child_id,
                'child_name' => $category->child_name,
            ];
        }

        $categories_array = self::buildCategoryTree($categories_db);

        foreach ($categories_no_heir as $category) {
            $categories_array[] = $category->parent_name;
        }


        //Logger::log(print_r($categories_array, true), '$categories');
        //Logger::log(print_r($categories_no_heir, true), '$categories_tet');
        //Logger::log(print_r($categories, true), 'categories');


        return $categories_array;
    }

    public static function getExpenseCategoriesAllChild($name, $supplier_id = null): array
    {

        $builder = ExpenseCategories::newQueryBuilder()
            ->select([
                'p.id AS parent_id',
                'p.name AS parent_name',
                'c.id AS child_id',
                'c.name AS child_name',
            ])
            ->from('category_relations r')
            ->leftJoin('expense_categories p')
            ->on([
                'r.parent_id = p.id',
            ]);

        if ($supplier_id) {
            $builder
                ->leftJoin('expense_categories c')
                ->on([
                    'r.child_id = c.id',
                    'p.supplier_id =' . $supplier_id,
                ]);
        } else {
            $builder
                ->leftJoin('expense_categories c')
                ->on([
                    'r.child_id = c.id',
                    'p.supplier_id IS NULL',
                ]);
        }


        $builder
            ->orderBy('p.id, c.id', '');

        $categories = PdoConnector::execute($builder);

        $builder = ExpenseCategories::newQueryBuilder()
            ->select([
                'ec.id AS parent_id',
                'ec.name AS parent_name',
            ])
            ->from('expense_categories ec')
            ->leftJoin('category_relations cr')
            ->on([
                'ec.id = cr.parent_id',
            ]);

        if ($supplier_id) {
            $builder
                ->where([
                    'ec.is_parsed = 1',
                    'cr.parent_id IS NULL',
                    'ec.supplier_id =' . $supplier_id,
                ]);
        } else {
            $builder
                ->where([
                    'ec.is_parsed = 1',
                    'cr.parent_id IS NULL',
                    'ec.supplier_id IS NULL',
                ]);
        }


        $categories_no_heir = PdoConnector::execute($builder);
        $categories_db = [];

        foreach ($categories as $category) {
            $categories_db[] = [
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent_name,
                'child_id' => $category->child_id,
                'child_name' => $category->child_name,
            ];
        }

        $result = self::returnAllChildrenOfCategory($categories_db, $name);

        foreach ($categories_no_heir as $category) {
            if ($category->parent_name == $name) {
                $result[] = [
                    'id' => $category->parent_id,
                    'name' => $category->parent_name,
                ];
            }
        }


        //Logger::log(print_r($result, true), '$result');


        return $result;
    }

    public static function getExpenseCategoriesNameParent($name)
    {
        $builder = ExpenseCategories::newQueryBuilder()
            ->select([
                'ec_parent.id AS parent_id',
                'ec_parent.name AS parent_name',
                'ec_child.id AS child_id',
                'ec_child.name AS child_name',
            ])
            ->from('expense_categories ec_parent')
            ->leftJoin('category_relations cr')
            ->on([
                'ec_parent.id = cr.parent_id',
            ])
            ->leftJoin('expense_categories ec_child')
            ->on([
                'cr.child_id = ec_child.id',
            ])
            ->where([
                'ec_parent.name ="' . $name . '"'
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getExpenseCategoriesName($name)
    {


        $builder = ExpenseCategories::newQueryBuilder()
            ->select(['*'])
            ->where([
                'name = "' . $name . '"'
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function getExpenseCategoriesIsParsed()
    {

        $builder = ExpenseCategories::newQueryBuilder()
            ->select()
            ->where([
                "is_parsed = '" . 1 . "'",
            ]);


        return PdoConnector::execute($builder) ?? [];
    }

    public static function delCategories(array $categories)
    {

        $where = '';

        foreach ($categories as $key => $category) {

            if ($categories[$key + 1] ?? false) {
                $where .= "{$category['id']}, ";
            } else {
                $where .= "{$category['id']}";
            }
        }


        $builder = ExpenseCategories::newQueryBuilder()
            ->delete()
            ->where([
                "id IN($where)"
            ]);


        return PdoConnector::execute($builder);
    }

    public static function buildCategoryTree(array $categories_db): array
    {
        // Построение карты: parent_id => массив детей (каждый с id и именем)
        $map = [];

        foreach ($categories_db as $cat) {
            $map[$cat['parent_id']][] = [
                'id' => $cat['child_id'],
                'name' => $cat['child_name'],
            ];
        }

        // Все уникальные parent_id и child_id
        $all_parents = array_unique(array_column($categories_db, 'parent_id'));
        $all_children = array_unique(array_column($categories_db, 'child_id'));

        // Верхний уровень — parent_id, которые не встречаются как child_id
        $top_level_ids = array_diff($all_parents, $all_children);

        // Рекурсивная функция для построения дерева
        $build = function ($parent_id) use (&$build, &$map, &$used): array {
            if (in_array($parent_id, $used, true)) {
                return []; // избегаем циклов
            }
            $used[] = $parent_id;

            $result = [];
            if (isset($map[$parent_id])) {
                foreach ($map[$parent_id] as $child) {
                    $child_id = $child['id'];
                    $child_name = $child['name'];
                    $children_tree = $build($child_id);

                    if (!empty($children_tree)) {
                        $result[$child_name] = $children_tree;
                    } else {
                        $result[] = $child_name;
                    }
                }
            }
            return $result;
        };

        $result = [];
        foreach ($top_level_ids as $top_id) {
            $used = [];  // очищаем список посещённых перед каждым новым корнем

            // Получаем имя корня
            $top_name = null;
            foreach ($categories_db as $cat) {
                if ($cat['parent_id'] === $top_id) {
                    $top_name = $cat['parent_name'];
                    break;
                }
            }
            if ($top_name === null) {
                $top_name = (string)$top_id;
            }

            $result[$top_name] = $build($top_id);
        }

        return $result;
    }

    public static function returnAllChildrenOfCategory(array $data, string $target_name): array
    {

        // Шаг 1: создаем карту name → id по parent_name и child_name
        $category_map = [];
        foreach ($data as $item) {
            $category_map[$item['parent_name']] = $item['parent_id'];
            $category_map[$item['child_name']] = $item['child_id'];
        }

        // Шаг 2: получаем id по имени
        if (!isset($category_map[$target_name])) {
            return [];
        }
        $start_id = $category_map[$target_name];

        // Шаг 3: создаем карту parent_id -> список детей
        $children_map = [];
        foreach ($data as $item) {
            $children_map[$item['parent_id']][] = [
                'id' => $item['child_id'],
                'name' => $item['child_name'],
            ];
        }

        // Шаг 4: начинаем обход в глубину
        $result = [];
        $visited = [];
        $stack = [['id' => $start_id, 'name' => $target_name]]; // начинаем с исходной категории

        while (!empty($stack)) {
            $current = array_pop($stack);
            $id = $current['id'];

            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;

            $result[] = $current;

            if (!empty($children_map[$id])) {
                foreach ($children_map[$id] as $child) {
                    $stack[] = $child;
                }
            }
        }

        return $result;
    }

}