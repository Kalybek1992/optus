<?php

namespace Source\Project\LogicManagers\DocumentLM;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\LogicManagers\LogicPdoModel\UploadedDocumentsLM;


class DocumentExtractLM
{
    public array $field_map = [
        'name' => 'Плательщик1',
        'inn' => 'ПлательщикИНН',
        'kpp' => 'ПлательщикКПП',
        'bank_account' => 'ПлательщикРасчСчет',
        'bank_name' => 'ПлательщикБанк1',
        'date' => 'Дата',
        'bic' => 'ПлательщикБИК',
        'document_number' => 'Номер',
        'correspondent_account' => 'ПлательщикКорсчет',
        'balance' => 'Сумма',
        'type' => 'НазначениеПлатежа',
        'company_name' => 'Плательщик',
        'name_recipient' => 'Получатель1',
        'inn_recipient' => 'ПолучательИНН',
        'kpp_recipient' => 'ПолучательКПП',
        'bank_account_recipient' => 'ПолучательРасчСчет',
        'bank_name_recipient' => 'ПолучательБанк1',
        'bic_recipient' => 'ПолучательБИК',
        'correspondent_account_recipient' => 'ПолучательКорсчет',
        'company_name_recipient' => 'Получатель',
    ];


    public array $map_bank_exchange = [
        'sender' => 'Отправитель',
        'date_created' => 'ДатаСоздания',
        'start_date' => 'ДатаНачала',
        'end_date' => 'ДатаКонца',
        'initial_balance' => 'НачальныйОстаток',
        'total_received' => 'ВсегоПоступило',
        'total_written_off' => 'ВсегоСписано',
        'final_remainder' => 'КонечныйОстаток',
        'bank_account' => 'РасчСчет',
    ];

    public array $section_document = [
        'payment_order' => 'СекцияДокумент=Платежное поручение',
        'bank_order' => 'СекцияДокумент=Банковский ордер',
        'bank_exchange' => '1CClientBankExchange'
    ];

    public string $document_all_inn = '';
    public string $document_all_number = '';
    public string $document_all_balance = '';
    public string $result_document_processing = '';
    private array $blocks = [];
    public array $payment_order = [];

    public array $bank_order = [];
    public array $bank_exchange = [];
    public array $expenses = [];
    public array $income = [];


    public function __construct(array $lines, $section_document = 'СекцияДокумент')
    {
        $blocks = [];
        $current_block = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, $section_document)) {

                if (!empty($current_block)) {
                    $blocks[] = $current_block;
                    $current_block = [];
                }
            }
            $current_block[] = $line;
        }


        if (!empty($current_block)) {
            $blocks[] = $current_block;
        }

        $this->blocks = $blocks;

        //Logger::log(print_r($blocks, true), 'blocks');

        $this->sortSection();
    }


    private function sortSection(): void
    {

        foreach ($this->blocks as $block) {
            if (str_starts_with($block[0], $this->section_document['payment_order'])) {
                $this->payment_order[] = $block;
            }

            if (str_starts_with($block[0], $this->section_document['bank_order'])) {
                $this->bank_order[] = $block;
            }

            if (str_starts_with($block[0], $this->section_document['bank_exchange'])) {
                $this->bank_exchange[] = $block;
            }
        }


        $this->mapBankExchange();
        $this->mapSectionOrder();

        if (!$this->document_all_inn && !$this->document_all_number){
            $this->result_document_processing = 'no_extracts_files';
        }

        if (!$this->bank_order && !$this->payment_order){
            $this->result_document_processing = 'no_extracts_files';
        }

        if (!$this->bank_exchange || $this->bank_exchange['bank_account'] == '') {
            $this->result_document_processing = 'error_no_title';
            return;
        }

        $this->checkingUploadedDocuments();
        //Logger::log(print_r($this->result_document_processing , true), 'map_payment_order');
    }

    private function checkingUploadedDocuments(): void
    {
        $this->getSelectInOrder();

        $loaded_transactions = UploadedDocumentsLM::getBankAccounts(
            $this->document_all_inn,
            $this->document_all_number
        );

        $this->bank_order = $this->removeDuplicates($loaded_transactions, $this->bank_order);
        $this->payment_order = $this->removeDuplicates($loaded_transactions, $this->payment_order);


        if (!$this->bank_order && !$this->payment_order) {
            $this->result_document_processing = 'processed_invoices';
            return;
        }

        $this->result_document_processing = 'there_are_documents_left_to_be_processed.';
    }


    private function mapSectionOrder(): void
    {
        $data_processed = [
            'payment_order' => $this->payment_order,
            'bank_order' => $this->bank_order,
        ];

        $this->payment_order = [];
        $this->bank_order = [];

        foreach ($data_processed as $processed_key => $processed) {
            if ($processed) {
                foreach ($processed as $block) {
                    $data = [];
                    foreach ($block as $bl) {
                        $key_meaning = explode('=', $bl);
                        foreach ($this->field_map as $key => $map) {
                            if ($map == $key_meaning[0]) {
                                $data[$key] = $key_meaning[1] ?? null;
                            }
                        }
                    }

                    if ($data && $processed_key == 'payment_order') {
                        $this->payment_order[] = $data;
                    }

                    if ($data && $processed_key == 'bank_order') {
                        $this->bank_order[] = $data;
                    }
                }
            }
        }
    }

    //TODO кое что убрал может надо вернуть $exclude_amount это баланс || сумма !!!!
    private function removeDuplicates(array $loaded_transactions, $transactions)
    {
        foreach ($loaded_transactions as $transaction) {

            $exclude_inn = $transaction->inn;
            $exclude_doc = $transaction->document_number;
            $exclude_amount = $transaction->amount;

            $transactions = array_filter($transactions, function ($item) use ($exclude_inn, $exclude_doc, $exclude_amount) {
                return !(
                    $item['inn'] == $exclude_inn &&
                    $item['document_number'] == $exclude_doc
                );
            });
        }

        return $transactions;
    }

    private function getSelectInOrder(): void
    {
        $inns = [];
        $document_numbers = [];
        $balance = [];

        $data_processed = [
            'payment_order' => $this->payment_order,
            'bank_order' => $this->bank_order,
        ];

        foreach ($data_processed as $key => $processed) {
            if ($processed) {
                foreach ($processed as $transaction) {
                    $wrap = fn($v) => is_numeric($v) ? $v : "'$v'";

                    $inns[] = $wrap($transaction['inn']);
                    $document_numbers[] = $wrap($transaction['document_number']);
                    $balance[] = $wrap($transaction['balance']);
                }
            }
        }


        $this->document_all_inn = implode(', ', $inns);
        $this->document_all_number = implode(', ', $document_numbers);
        $this->document_all_balance = implode(', ', $balance);
    }


    private function mapBankExchange(): void
    {
        $result = [];
        foreach ($this->bank_exchange as $block) {
            foreach ($block as $bl) {
                $parts = explode('=', $bl, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                [$sourceKey, $value] = $parts;

                foreach ($this->map_bank_exchange as $targetKey => $mappedKey) {
                    if ($mappedKey !== $sourceKey) {
                        continue;
                    }

                    if (!array_key_exists($targetKey, $result) || empty($result[$targetKey])) {
                        $result[$targetKey] = $value ?: null;
                    }
                }
            }
        }

        $this->bank_exchange = $result;
    }


}