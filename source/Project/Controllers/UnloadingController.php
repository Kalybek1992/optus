<?php

namespace Source\Project\Controllers;

use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\LogicPdoModel\ClientServicesLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\CouriersLM;
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
        exit;
    }

    public function downloadExtract()
    {
        $file_name = (string) (InformationDC::get('file') ?? '');
        if ($file_name === '') {
            return ApiViewer::getErrorBody(['message' => 'File name is empty']);
        }

        // защита от ../
        $safeName = basename($file_name);
        $file_path = Path::RESOURCES_DIR . 'uploads/' . $safeName;

        if (!is_file($file_path)) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        // очищаем буферы
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($file_path);

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

    public function getTransactionDate(): array
    {
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
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

    public function archiveOfExtracts(): array
    {
        $date = InformationDC::get('date');
        $our_accounts = LegalEntitiesLM::getEntitiesOurAccountDate($date);

        if (!$our_accounts) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        $file_path = XlsxLM::archiveOfExtracts($our_accounts);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function supplierClientReceiptsDate(): array
    {
        $client_id = InformationDC::get('client_id') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $suplier = InformationDC::get('suplier');
        $supplier_id = $suplier['supplier_id'] ?? 0;
        $client = ClientsLM::getClientSupplierId($client_id, $supplier_id);
        $limit = 120;
        $offset = 0;

        if (!$client) {
            return ApiViewer::getErrorBody(['message' => 'supplier_id']);
        }


        $transactions_count = LegalEntitiesLM::getEntitiesClientTransactionsCount(
            null,
            $date_from,
            $date_to,
            $client_id,
        );

        $all_transactions = [];

        $limit_count = 2500;
        if ($transactions_count > $limit_count) {
            return ApiViewer::getErrorBody(['message' => 'limit_reached']);
        }

        while ($offset < $transactions_count) {
            $chunk = LegalEntitiesLM::getEntitiesClientTransactions(
                null,
                $offset,
                $limit,
                $date_from,
                $date_to,
                $client_id
            );

            foreach ($chunk as &$item) {
                $percent = $client['percentage'] ?? 0;
                $item['interest_income'] = round($item['total_amount'] * ($percent / 100), 2);
            }

            if (!empty($chunk)) {
                $all_transactions = array_merge($all_transactions, $chunk);
            }

            $offset += $limit;
            usleep(500);
        }

        if (!$all_transactions) {
            return ApiViewer::getErrorBody(['message' => 'File not found']);
        }

        $transactions_sum = LegalEntitiesLM::getEntitiesClientTransactionsSum(
            null,
            $date_from,
            $date_to,
            $client_id,
        );

        $file_path = XlsxLM::supplierClientReceiptsDate($all_transactions, $transactions_sum);

        return ApiViewer::getOkBody(['file' => $file_path]);
    }

    public function courierFinances(): array
    {
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $category = InformationDC::get('category');
        $user = InformationDC::get('user');
        $courier = CouriersLM::getCourierByUserId($user['id']);
        $courier_id = $courier['id'] ?? 0;
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
}
