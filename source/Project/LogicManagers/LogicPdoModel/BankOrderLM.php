<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\LogicManagers\DocumentLM\DocumentLM;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\BankOrder;
use Source\Project\Models\Clients;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\Suppliers;
use Source\Project\Models\Transactions;
use Source\Project\Models\Users;


/**
 *
 */
class BankOrderLM
{

    public static function updateBankOrder(array $data, $id)
    {
        $builder = BankOrder::newQueryBuilder()
            ->update($data)
            ->where([
                'id =' . $id
            ]);

        //return $builder->build();


        return PdoConnector::execute($builder);
    }

    public static function getBankOrderMaxId()
    {
        $builder = BankOrder::newQueryBuilder()
            ->select([
                'MAX(id) as max_id',
            ])
            ->limit(1);



        return PdoConnector::execute($builder)[0]->max_id ?? 0;
    }

    public static function insertNewBankOrder(array $dataset)
    {
        $builder = BankOrder::newQueryBuilder()
            ->insert($dataset);


        return PdoConnector::execute($builder);
    }

    public static function getBankOrderCountPending()
    {

        $builder = BankOrder::newQueryBuilder()
            ->select([
                'COUNT(id)',
            ])
            ->where([
                "status = 'pending'",
            ]);

        $pending = PdoConnector::execute($builder)[0] ?? false;

        if ($pending) {
            $pending = $pending->variables['COUNT(id)'];
        } else {
            $pending = 0;
        }


        return $pending;
    }

    public static function getBankOrders($offset, $limit)
    {

        $builder = BankOrder::newQueryBuilder()
            ->select([
                '*',
                'le.company_name as sender_company_name',
                'le.bank_name as sender_bank_name'
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id',
            ])
            ->where([
                "status = 'pending'",
            ])
            ->orderBy('date', 'DESC')
            ->limit($limit)
            ->offset($offset);

        $pending = PdoConnector::execute($builder);
        $bank_order_arr = [];

        foreach ($pending as $bank_order) {
            $bank_order_arr[] = [
                'id' => $bank_order->id,
                'type' => $bank_order->type,
                'amount' => $bank_order->amount,
                'date' => date('d.m.Y', strtotime($bank_order->date)),
                'description' => $bank_order->description,
                'document_number' => $bank_order->document_number,
                'recipient_company_name' => $bank_order->recipient_company_name,
                'status' => $bank_order->status,
                'sender_company_name' => $bank_order->sender_company_name,
                'sender_bank_name' => $bank_order->sender_bank_name,
            ];
        }


        return $bank_order_arr;
    }

    public static function getBankOrderId(int $bank_order_id): array
    {

        $builder = BankOrder::newQueryBuilder()
            ->select([
                '*',
                'le.id as legal_id',
                'le.company_name as sender_company_name',
                'le.bank_name as sender_bank_name'
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id',
            ])
            ->where([
                "status = 'pending'",
                "id = '" . $bank_order_id . "'",
            ]);

        $pending = PdoConnector::execute($builder);


        $bank_order_arr = [];

        foreach ($pending as $bank_order) {
            $bank_order_arr[] = [
                'id' => $bank_order->id,
                'type' => $bank_order->type,
                'amount' => $bank_order->amount,
                'date' => date('d.m.Y', strtotime($bank_order->date)),
                'description' => $bank_order->description,
                'document_number' => $bank_order->document_number,
                'recipient_company_name' => $bank_order->recipient_company_name,
                'status' => $bank_order->status,
                'sender_company_name' => $bank_order->sender_company_name,
                'sender_bank_name' => $bank_order->sender_bank_name,
                'legal_id' => $bank_order->legal_id,
            ];
        }

        //Logger::log(print_r($bank_order_arr, true), 'pending');
        return $bank_order_arr;
    }

    public static function getBankOrder(int $bank_order_id)
    {

        $builder = BankOrder::newQueryBuilder()
            ->select([
                '*',
                'le.company_name as sender_company_name',
                'le.bank_name as sender_bank_name',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id',
            ])
            ->where([
                "status = 'pending'",
                "id = '" . $bank_order_id . "'",
            ])
            ->limit(1);



        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getBankOrderReturn(int $bank_order_id)
    {

        $builder = BankOrder::newQueryBuilder()
            ->select([
                '*',
                'le.company_name as sender_company_name',
                'le.bank_name as sender_bank_name',
                'le.id as sender_legal_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id',
            ])
            ->where([
                "return_account = 1",
                "id = '" . $bank_order_id . "'",
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0] ?? [];
    }


    public static function getBankOrderRecipientCompanyName(string $recipient_company_name)
    {

        $builder = BankOrder::newQueryBuilder()
            ->select([
                '*',
                'le.company_name as sender_company_name',
                'le.bank_name as sender_bank_name',
                'le.id as sender_legal_id',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id',
            ])
            ->where([
                "return_account = 1",
                "recipient_company_name = '" . $recipient_company_name . "'",
            ]);


        return PdoConnector::execute($builder);
    }

    public static function deleteBankOrderRecipientCompanyName(string $recipient_company_name)
    {

        $builder = BankOrder::newQueryBuilder()
            ->delete()
            ->where([
                "return_account = 1",
                "recipient_company_name = '" . $recipient_company_name . "'",
            ]);


        return PdoConnector::execute($builder);
    }


    public static function getBankOrderAllDescription(string $description)
    {

        $builder = BankOrder::newQueryBuilder()
            ->select([
                '*',
                'le.company_name as sender_company_name',
                'le.bank_name as sender_bank_name',
            ])
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = from_account_id',
            ])
            ->where([
                "status = 'pending'",
                "description = '" . $description . "'",
            ]);



        return PdoConnector::execute($builder) ?? [];
    }


}