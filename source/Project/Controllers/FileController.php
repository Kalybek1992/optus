<?php /** @noinspection ALL */

namespace Source\Project\Controllers;


use DateTime;
use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\RequestDC;
use Source\Project\LogicManagers\DocumentLM\DocumentExtractLM;
use Source\Project\LogicManagers\DocumentLM\DocumentLM;
use Source\Project\LogicManagers\DocumentLM\TransactionsProcessLM;
use Source\Project\LogicManagers\LogicPdoModel\BankAccountsLM;
use Source\Project\LogicManagers\LogicPdoModel\BankOrderLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\LoadedTransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\Models\BankOrder;
use Source\Project\Models\Transactions;
use Source\Project\Viewer\ApiViewer;


class FileController extends BaseController
{
    /**
     * @return array
     * @throws \Exception
     */
    public function upload(): array
    {
        $file = RequestDC::get('file');
        $filename = basename($file['name']);
        $upload_dir = Path::RESOURCES_DIR . '/uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $new_file_name = time() . '.txt';
        $destination = $upload_dir . $new_file_name;
        $get_bank_accounts = [];
        $transactions_account = [];
        $unknown_accounts = LegalEntitiesLM::getEntitiesNulCount();

        if (!pathinfo($filename, PATHINFO_EXTENSION) == 'txt') {
            return ApiViewer::getErrorBody(['value' => 'error_file_format']);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ApiViewer::getErrorBody(['value' => 'failed_to_save_file']);
        }

        // Читаем содержимое файла
        $content = file_get_contents($destination);
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1251');

        $lines = explode("\n", $content);
        $lines = array_filter(array_map('trim', $lines));
        $document = new TransactionsProcessLM($lines);

        if ($unknown_accounts > 0) {
            unlink($destination);
            return ApiViewer::getErrorBody(['value' => 'unknown_accounts']);
        }

        if ($document->result_document_processing === 'no_extracts_files') {
            unlink($destination);
            return ApiViewer::getErrorBody(['value' => 'no_extracts_files']);
        }

        if ($document->result_document_processing == 'error_no_title') {
            unlink($destination);
            return ApiViewer::getErrorBody(['value' => 'error_no_title']);
        }

        if ($document->result_document_processing == 'processed_invoices') {
            unlink($destination);
            return ApiViewer::getErrorBody(['value' => 'processed_payments']);
        }

        $document
            ->determineExpensesIncome()
            ->getOurAccounts()
            ->getFamousCompanies()
            ->processingNewAccounts()
            ->setNewTransactions()
            ->closeClientsDebt()
            ->closeClientServicesDebt()
            ->closeSupplierDebt()
            ->updateBalanceSupplier()
            ->makePurchasesServices()
            ->setNewTransactionsBankOrder()
            ->setLoadedTransactions()
            ->updateKnownLegalEntitiesTotals()
            ->lastStatementDownload($new_file_name)
            ->stepUpdateStatus();


        return ApiViewer::getOkBody([
            'success' => 'ok',
            'transactions_count' => $document->transactions_count,
            'bank_order_count' => $document->bank_order_count,
            'new_bank_accounts_count' => $document->new_bank_accounts_count,
            'customer_client_returns_count' => $document->customer_client_returns_count,
            'customer_supplier_returns_count' => $document->customer_supplier_returns_count,
            'customer_client_services_returns_count' => $document->customer_client_services_returns_count,
            'goods_supplier' => $document->goods_supplier,
            'goods_client' => $document->goods_client,
            'goods_client_service' => $document->goods_client_service,
        ]);
    }
}
