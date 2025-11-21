<?php

namespace Source\Project\LogicManagers\DocumentLM;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\LogicManagers\LogicPdoModel\BankAccountsLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\LoadedTransactionsLM;


class DocumentLM
{
    public static array $field_map = [
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


    public static array $bank_exchange = [
        'sender' => 'Отправитель',
        'date_created' => 'ДатаСоздания',
        'start_date' => 'ДатаНачала',
        'initial_balance' => 'НачальныйОстаток',
        'total_received' => 'ВсегоПоступило',
        'total_written_off' => 'ВсегоСписано',
        'final_remainder' => 'КонечныйОстаток',
        'bank_account' => 'РасчСчет',
    ];


    public static function parsProcess(array $lines)
    {

        $blocks = self::documentSection($lines);
        $sort_section = self::sortSection($blocks);
        $map_section = self::mapSectionOrder($sort_section['payment_order']);

        $get_bank_accounts = LegalEntitiesLM::getBankAccounts($map_section);

        foreach ($get_bank_accounts as $bank_account) {
            foreach ($map_section as $key => $entry) {
                if ($entry['bank_account'] == $bank_account->bank_account) {
                    $balance = $bank_account->balance + $entry['balance'];

                    BankAccountsLM::getBankAccounts(
                        [
                            'balance =' . $balance
                        ],
                        $bank_account->id
                    );

                    unset($map_section[$key]);
                }
            }
        }


        return $map_section;
    }

    public static function documentSection(array $lines): array
    {

        $blocks = [];
        $current_block = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'СекцияДокумент')) {
                // Если текущий блок не пустой — сохраняем его
                if (!empty($current_block)) {
                    $blocks[] = $current_block;
                    $current_block = [];
                }
            }
            $current_block[] = $line;
        }

        // Добавляем последний блок
        if (!empty($current_block)) {
            $blocks[] = $current_block;
        }


        return self::sortSection($blocks);
    }


    public static function sortSection(array $blocks): array
    {

        $payment_order = [];
        $bank_order = [];
        $bank_exchange = [];

        $section_document = [
            'payment_order' => 'СекцияДокумент=Платежное поручение',
            'bank_order' => 'СекцияДокумент=Банковский ордер',
            'bank_exchange' => '1CClientBankExchange'
        ];

        foreach ($blocks as $block) {
            if (str_starts_with($block[0], $section_document['payment_order'])) {
                $payment_order[] = $block;
            }

            if (str_starts_with($block[0], $section_document['bank_order'])) {
                $bank_order[] = $block;
            }

            if (str_starts_with($block[0], $section_document['bank_exchange'])) {
                $bank_exchange[] = $block;
            }
        }


        $result = [];
        $map_payment_order = [];
        $map_bank_order = [];
        $inn = '';
        $document_number = '';

        if ($payment_order) {
            $map_payment_order = self::mapSectionOrder($payment_order);
            $account_numbers = self::getSelectInOrder($map_payment_order);
            $inn .= $account_numbers['inn'];
            $document_number .= $account_numbers['document_number'];

        }

        if ($bank_order) {
            $map_bank_order = self::mapSectionOrder($bank_order);
            $account_numbers = self::getSelectInOrder($map_bank_order);
            $inn .= ', ' . $account_numbers['inn'];
            $document_number .= ', ' . $account_numbers['document_number'];

        }




        if (($bank_exchange) && ($payment_order || $bank_order)) {

            $loaded_transactions = LoadedTransactionsLM::getBankAccounts($inn, $document_number);

            $map_bank_order_remove = self::removeDuplicates($loaded_transactions, $map_bank_order);
            $map_payment_order_remove = self::removeDuplicates($loaded_transactions, $map_payment_order);

            $result['bank_order'] = $map_bank_order_remove;
            $result['payment_order'] = $map_payment_order_remove;

            //Logger::log(print_r($map_bank_order_remove, true), 'map_bank_order');
            //Logger::log(print_r($result, true), 'map_payment_order');

            $result['bank_exchange'] = self::mapBankExchange($bank_exchange);
        }


        //Logger::log(print_r($result, true), 'map_payment_order');

        return $result;
    }


    public static function mapSectionOrder(array $data_order): array
    {
        $legal_entities_insert = [];

        foreach ($data_order as $block) {
            $data = [];
            foreach ($block as $bl) {
                $key_meaning = explode('=', $bl);
                foreach (self::$field_map as $key => $map) {
                    if ($map == $key_meaning[0]) {
                        $data[$key] = $key_meaning[1] ?? null;
                    }
                }

            }

            if ($data) {
                $legal_entities_insert[] = $data;
            }
        }


        return $legal_entities_insert;
    }

    public static function removeDuplicates(array $loaded_transactions, $transactions): array|string
    {
        foreach ($loaded_transactions as $transaction) {

            $exclude_inn = $transaction->inn;
            $exclude_doc = $transaction->document_number;

            $transactions = array_filter($transactions, function ($item) use ($exclude_inn, $exclude_doc) {
                return !($item['inn'] === $exclude_inn && $item['document_number'] === $exclude_doc);
            });
        }

        if (!$transactions){
            return 'processed_invoices';
        }

        return $transactions;
    }

    public static function getSelectInOrder(array $order): array
    {
        $inns = [];
        $document_numbers = [];

        foreach ($order as $transaction) {
            $inns[] = "{$transaction['inn']}";
            $document_numbers[] = "{$transaction['document_number']}";
        }


        return [
            'inn' => implode(', ', $inns),
            'document_number' => implode(', ', $document_numbers),
        ];
    }


    public static function mapBankExchange(array $data_order): array
    {
        $legal_entities_insert = [];

        foreach ($data_order as $block) {
            $data = [];
            foreach ($block as $bl) {
                $key_meaning = explode('=', $bl);
                foreach (self::$bank_exchange as $key => $map) {
                    if ($map == $key_meaning[0]) {
                        $data[$key] = $key_meaning[1] ?? null;
                    }
                }

            }

            if ($data) {
                $legal_entities_insert[] = $data;
            }
        }


        return $legal_entities_insert[0] ?? [];
    }

    public static function getBankAccountArr(array $account_arr): array
    {
        $select_bank_account = [];
        foreach ($account_arr as $entry) {
            $select_bank_account[] = $entry['bank_account'];
            $select_bank_account[] = $entry['bank_account_recipient'];
        }

        $remove_duplicates = array_unique($select_bank_account);


        return array_values($remove_duplicates);
    }


    public function parseDateToMysqlFormat(string $raw_date): ?string
    {
        $formats = [
            'd.m.Y',
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
        ];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $raw_date);
            if ($date && $date->format($format) === $raw_date) {
                return $date->format('Y-m-d'); // формат для MySQL
            }
        }

        return null; // если ни один формат не подошёл
    }


    public static function detectTransactionType(string $description): ?string
    {
        $commissionKeywords = [
            'комиссия', 'ком.за', 'плата за', 'плата', 'fee'
        ];

        $withdrawalKeywords = [
            'снятие', 'выдача', 'atm', 'устройство самообслуживания'
        ];

        $descriptionLower = mb_strtolower($description, 'UTF-8');

        // Сначала ищем комиссию
        foreach ($commissionKeywords as $word) {
            if (mb_strpos($descriptionLower, $word) !== false) {
                return 'commission';
            }
        }

        // Затем ищем выдачу
        foreach ($withdrawalKeywords as $word) {
            if (mb_strpos($descriptionLower, $word) !== false) {
                return 'withdrawal';
            }
        }

        return 'unknown';
    }


}