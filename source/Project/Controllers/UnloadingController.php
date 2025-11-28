<?php

namespace Source\Project\Controllers;

use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\LogicPdoModel\ClientServicesLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\Xlsx\XlsxLM;
use Source\Project\Viewer\ApiViewer;
use Source\Base\Constants\Settings\Path;

class UnloadingController extends BaseController
{

    public function downloadFile()
    {
        $file_name = InformationDC::get('file') ?? '';
        $file_path = Path::RESOURCES_DIR . 'unloading/' . basename($file_name);

        if (!file_exists($file_path)) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        $original_name = basename($file_name);

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $original_name . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        readfile($file_path);
        unlink($file_path);
        exit;
    }

    public function shopReceiptsDate(): array
    {
        $shop_id = InformationDC::get('shop_id');
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 120;

        $transactions_count = TransactionsLM::getEntitiesShopTransactionsCount($shop_id, $date_from, $date_to);
        $all_transactions = [];
        $offset = 0;

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = TransactionsLM::getEntitiesShopTransactions(
                $shop_id,
                $offset,
                $limit,
                $date_from,
                $date_to
            );

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        $file_path = XlsxLM::transactionMagazine($all_transactions);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function clientReceiptsDate(): array
    {
        $client_id = InformationDC::get('client_id') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 120;

        $transactions_count = LegalEntitiesLM::getEntitiesClientTransactionsCount($client_id, $date_from, $date_to);
        $all_transactions = [];
        $offset = 0;

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = LegalEntitiesLM::getEntitiesClientTransactions($client_id, $offset, $limit, $date_from, $date_to);

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        $transactions_sum = LegalEntitiesLM::getEntitiesClientTransactionsSum($client_id, $date_from, $date_to);
        $file_path = XlsxLM::transactionclientReceipts($all_transactions, $transactions_sum);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function clientServicesReceiptsDate(): array
    {
        $client_id = InformationDC::get('client_id') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 120;

        $client = ClientServicesLM::clientServicesId($client_id);
        $supplier_id = $client['supplier_id'] ?? 0;


        $transactions_count = LegalEntitiesLM::getEntitiesClientServicesTransactionsCount(
            $supplier_id,
            $date_from,
            $date_to,
            null,
            $client_id,
        );
        $all_transactions = [];
        $offset = 0;

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = LegalEntitiesLM::getEntitiesClientServicesTransactions(
                $supplier_id,
                $offset,
                $limit,
                $date_from,
                $date_to,
                null,
                $client_id,
            );

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        $transactions_sum =  LegalEntitiesLM::getEntitiesClientServicesTransactionsSum(
            $supplier_id,
            $date_from,
            $date_to,
            null,
            $client_id,
        );

        $file_path = XlsxLM::clientServicesReceiptsDate($all_transactions, $transactions_sum);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function suppliersSendingsDate(): array
    {
        $supplier_id = InformationDC::get('supplier_id');
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 120;

        $transactions_count = LegalEntitiesLM::getEntitiesSuppliersTransactionsCount(
            $supplier_id,
            $date_from,
            $date_to
        );
        $all_transactions = [];
        $offset = 0;

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = LegalEntitiesLM::getEntitiesSuppliersTransactions(
                $supplier_id,
                $offset,
                $limit,
                $date_from,
                $date_to
            );

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        $transactions_sum = LegalEntitiesLM::getEntitiesSuppliersTransactionsSum(
            $supplier_id,
            $date_from,
            $date_to
        );

        $file_path = XlsxLM::suppliersSendingsDate($all_transactions, $transactions_sum);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function getCourierFinances(): array
    {
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $courier_id = InformationDC::get('courier_id');
        $category = InformationDC::get('category');
        $limit = 120;

        $transactions_count = CompanyFinancesLM::getCourierFinancesCount($courier_id, $category, $date_from, $date_to);
        $all_transactions = [];
        $offset = 0;

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = CompanyFinancesLM::getCourierFinances(
                $courier_id,
                $offset,
                $limit,
                $category,
                $date_from,
                $date_to
            );

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }


        $file_path = XlsxLM::getCourierFinances($all_transactions);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function getExpenses(): array
    {
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $category = InformationDC::get('category');
        $limit = 120;

        $transactions_count = CompanyFinancesLM::getTranslationExpensesCount(
            $category,
            $date_from,
            $date_to,
            'company'
        );

        $all_transactions = [];
        $offset = 0;

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = CompanyFinancesLM::getExpenses(
                $offset,
                $limit,
                $category,
                $date_from,
                $date_to,
                'company'
            );

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }


        $file_path = XlsxLM::getExpenses($all_transactions);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function getTransferYourself(): array
    {
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 120;

        $transactions_count = TransactionsLM::getEntitiesOurTransactionsCount(
            $date_from,
            $date_to
        );

        $all_transactions = [];
        $offset = 0;

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = TransactionsLM::getEntitiesOurTransactions(
                $offset,
                $limit,
                $date_from,
                $date_to
            );

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        $transactions_sum = TransactionsLM::getEntitiesOurTransactionsSum(
            $date_from,
            $date_to
        );

        $file_path = XlsxLM::getTransferYourself($all_transactions, $transactions_sum);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function getExpensesStockBalances(): array
    {
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $category = InformationDC::get('category');
        $limit = 120;

        $transactions_count = CompanyFinancesLM::getTranslationExpensesCount(
            $category,
            $date_from,
            $date_to,
            'stock_balances'
        );

        $all_transactions = [];
        $offset = 0;

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = CompanyFinancesLM::getExpenses(
                $offset,
                $limit,
                $category,
                $date_from,
                $date_to,
                'stock_balances'
            );

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }


        $file_path = XlsxLM::getExpensesStockBalances($all_transactions);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

}
