<?php

namespace Source\Project\Controllers;

use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\Xlsx\XlsxLM;
use Source\Project\Viewer\ApiViewer;
use Source\Base\Constants\Settings\Path;

class UnloadingController extends BaseController
{
    public function shopReceiptsDate(): array
    {
        $shop_id = InformationDC::get('shop_id');
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 120;

        $transactions_count = TransactionsLM::getEntitiesShopTransactionsCount($shop_id, $date_from, $date_to);
        $all_transactions = [];
        $offset = 0;

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

}
