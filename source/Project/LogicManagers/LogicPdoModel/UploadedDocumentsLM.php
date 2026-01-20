<?php

namespace Source\Project\LogicManagers\LogicPdoModel;


use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\UploadedDocuments;



/**
 *
 */
class UploadedDocumentsLM
{
    public static function getBankAccounts(
        string $inn,
        string $document_number,
        string $amount,
        string $recipient_inn,
        string $bank_account,
        string $recipient_bank_account,
        string $statement_date,
    )
    {

        $builder = UploadedDocuments::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                "inn IN($inn)",
                "document_number IN($document_number)",
                "amount IN($amount)",
                "recipient_inn IN($recipient_inn)",
                "bank_account IN($bank_account)",
                "recipient_bank_account IN($recipient_bank_account)",
                "statement_date IN($statement_date)",
            ]);


        return PdoConnector::execute($builder);
    }

    public static function deleteUploadedDocuments(string $inn, string $document_number, string $date)
    {

        $builder = UploadedDocuments::newQueryBuilder()
            ->delete()
            ->where([
                'inn IN(' . $inn . ')',
                'document_number IN(' . $document_number . ')',
                'date IN(' . $date . ')',
            ]);


        return PdoConnector::execute($builder);
    }

    public static function deleteUploadedDocumentsIds($ids)
    {

        $builder = UploadedDocuments::newQueryBuilder()
            ->delete()
            ->where([
                'id IN(' . $ids . ')',
            ]);


        return PdoConnector::execute($builder);
    }

    public static function insertNewLoadedTransactions(array $dataset)
    {
        $builder = UploadedDocuments::newQueryBuilder()
            ->insert($dataset);

        return PdoConnector::execute($builder);
    }


    public static function getAccountsUploadedMaxTime(array $selects_inn)
    {
        if (!$selects_inn) {
            return [];
        }

        $selects_inn = implode(',', array_map('intval', $selects_inn));

        $builder = UploadedDocuments::newQueryBuilder()
            ->select([
                'MAX(date) as max_date',
            ])
            ->where([
                "inn IN($selects_inn)"
            ]);


        return PdoConnector::execute($builder);
    }


    public static function getUploadedMaxId()
    {
        $builder = UploadedDocuments::newQueryBuilder()
            ->select([
                'MAX(id) as max_id',
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0]->max_id ?? 1;
    }

}