<?php

namespace Source\Project\Controllers;


use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\LogicManagers\HtmlLM\HtmlLM;
use Source\Project\LogicManagers\LogicPdoModel\CategoryRelationsLM;
use Source\Project\LogicManagers\LogicPdoModel\ExpenseCategoriesLM;
use Source\Project\DataContainers\InformationDC;
use Source\Project\Viewer\ApiViewer;


class CategoriesController extends BaseController
{
    public function pageCategories(): string
    {
        $return_page = InformationDC::get('return_page') ?? null;
        $get_categories = ExpenseCategoriesLM::getExpenseCategories();

        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevelsAdd($get_categories);
        } else {
            $categories_html = [];
        }
        //echo 'Пик использования памяти: ' . memory_get_peak_usage() . ' байт';
        //$level = '{ open: true, level: 1, path: [' . "'Транспорт'"  . '] }';
        //Logger::log(print_r($categories_html, true), 'htmlCategories');


        return $this->twig->render('Categories/Categories.twig', [
            'categories_html' => $categories_html,
            'return_page' => $return_page,
        ]);
    }

    public function addCategory(): array
    {
        $parent_category = InformationDC::get('parent_category');
        $new_category = InformationDC::get('new_category');
        $parent_db = [];

        if ($parent_category != 'not_parent') {
            $parent_db = ExpenseCategoriesLM::getExpenseCategoriesNameParent($parent_category);

            if (!$parent_db){
                return ApiViewer::getErrorBody(['error' => 'bad_parent_category']);
            }

            foreach ($parent_db as $category) {
                if ($category->child_name == $new_category) {
                    return ApiViewer::getErrorBody(['error' => 'bad_child_category']);
                }

                if ($category->parent_name == $new_category) {
                    return ApiViewer::getErrorBody(['error' => 'failed_to_add']);
                }
            }


            $new_category_db = ExpenseCategoriesLM::getExpenseCategoriesName($new_category);

            if (!$new_category_db) {
                ExpenseCategoriesLM::insertNewCategories([
                    'name' => $new_category,
                ]);

                $new_category_db = ExpenseCategoriesLM::getExpenseCategoriesName($new_category);
            }


            CategoryRelationsLM::insertNewRelations([
                'parent_id' => $parent_db[0]->parent_id,
                'child_id' => $new_category_db->id,
            ]);

        }else{

            $is_parsed = ExpenseCategoriesLM::getExpenseCategoriesIsParsed();

            if ($is_parsed) {
                foreach ($is_parsed as $category) {
                    if ($category->name == $new_category) {
                        return ApiViewer::getErrorBody(['error' => 'parent_duplicate']);
                    }
                }
            }

            ExpenseCategoriesLM::insertNewCategories([
                'name' => $new_category,
                'is_parsed' => 1
            ]);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function delCategory(): array
    {
        $category = InformationDC::get('category');
        $expense_categories = ExpenseCategoriesLM::getExpenseCategoriesAllChild($category);

        //Logger::log(print_r($expense_categories, true), 'expense_category');


        if (!$expense_categories) {
            return ApiViewer::getErrorBody(['error' => 'not_found']);
        }


        ExpenseCategoriesLM::delCategories($expense_categories);

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function supplierCategories(): string
    {
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;

        $return_page = InformationDC::get('return_page') ?? null;
        $get_categories = ExpenseCategoriesLM::getExpenseCategories($supplier_id);


        if ($get_categories) {
            $categories_html = HtmlLM::renderCategoryLevelsAdd($get_categories);
        } else {
            $categories_html = [];
        }

        //echo 'Пик использования памяти: ' . memory_get_peak_usage() . ' байт';
        //$level = '{ open: true, level: 1, path: [' . "'Транспорт'"  . '] }';
        //Logger::log(print_r($categories_html, true), 'htmlCategories');


        return $this->twig->render('Categories/SupplierCategories.twig', [
            'categories_html' => $categories_html,
            'return_page' => $return_page,
        ]);
    }

    public function addSupplierCategory(): array
    {
        $parent_category = InformationDC::get('parent_category');
        $new_category = InformationDC::get('new_category');
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;

        if ($parent_category != 'not_parent') {
            $parent_db = ExpenseCategoriesLM::getExpenseCategoriesNameParent($parent_category);

            if (!$parent_db){
                return ApiViewer::getErrorBody(['error' => 'bad_parent_category']);
            }

            foreach ($parent_db as $category) {
                if ($category->child_name == $new_category) {
                    return ApiViewer::getErrorBody(['error' => 'bad_child_category']);
                }

                if ($category->parent_name == $new_category) {
                    return ApiViewer::getErrorBody(['error' => 'failed_to_add']);
                }
            }


            $new_category_db = ExpenseCategoriesLM::getExpenseCategoriesName($new_category);

            if (!$new_category_db) {
                ExpenseCategoriesLM::insertNewCategories([
                    'name' => $new_category,
                    'supplier_id' => $supplier_id,
                ]);

                $new_category_db = ExpenseCategoriesLM::getExpenseCategoriesName($new_category);
            }


            CategoryRelationsLM::insertNewRelations([
                'parent_id' => $parent_db[0]->parent_id,
                'child_id' => $new_category_db->id,
            ]);

        }else{

            $is_parsed = ExpenseCategoriesLM::getExpenseCategoriesIsParsed();

            if ($is_parsed) {
                foreach ($is_parsed as $category) {
                    if ($category->name == $new_category) {
                        return ApiViewer::getErrorBody(['error' => 'parent_duplicate']);
                    }
                }
            }

            ExpenseCategoriesLM::insertNewCategories([
                'name' => $new_category,
                'supplier_id' => $supplier_id,
                'is_parsed' => 1
            ]);
        }

        return ApiViewer::getOkBody(['success' => 'ok']);
    }

    public function delSupplierCategory(): array
    {
        $category = InformationDC::get('category');
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;

        $expense_categories = ExpenseCategoriesLM::getExpenseCategoriesAllChild($category, $supplier_id);

        //Logger::log(print_r($expense_categories, true), 'expense_category');


        if (!$expense_categories) {
            return ApiViewer::getErrorBody(['error' => 'not_found']);
        }


        ExpenseCategoriesLM::delCategories($expense_categories);


        return ApiViewer::getOkBody(['success' => 'ok']);
    }

}
