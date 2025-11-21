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
        $destination = $upload_dir . bin2hex(random_bytes(16)) . '.txt';
        $get_bank_accounts = [];
        $transactions_account = [];

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



        if ($document->result_document_processing === 'no_extracts_files') {
            unlink($destination);
            return ApiViewer::getErrorBody(['value' => 'no_extracts_files']);
        }

        if ($document->result_document_processing == 'error_no_title') {
            return ApiViewer::getErrorBody(['value' => 'error_no_title']);
        }

        if ($document->result_document_processing == 'processed_invoices') {
            return ApiViewer::getErrorBody(['value' => 'processed_payments']);
        }
        
        $document
            ->determineExpensesIncome()
            ->getFamousCompanies()
            ->updateBalanceExisting()
            ->closeClientsDebt()
            ->closeClientServicesDebt()
            ->processingNewAccounts()
            ->setNewTransactions()
            ->makePurchasesServices()
            ->setNewTransactionsBankOrder()
            ->setLoadedTransactions()
            ->updateKnownLegalEntitiesTotals();


        //Logger::log(print_r($inset_loaded_transactions, true), 'inset_loaded_transactions');
        //Logger::log(print_r($map_section, true), 'map_section');
        //Logger::log(print_r($transaction_insert, true), 'transaction_insert');

        $transactions_count = $document->transactions_count;
        $new_bank_accounts_count = $document->new_bank_accounts_count;
        $bank_accounts_updated = $document->bank_accounts_updated_count;

        return ApiViewer::getOkBody([
            'success' => 'ok',
            'transactions_count' => $transactions_count,
            'new_bank_accounts_count' => $new_bank_accounts_count,
            'bank_accounts_updated' => $bank_accounts_updated,
        ]);
    }


}
