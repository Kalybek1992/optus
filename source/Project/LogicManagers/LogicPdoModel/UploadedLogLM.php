<?php

namespace Source\Project\LogicManagers\LogicPdoModel;


use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\UploadedLog;
use DateTime;

/**
 *
 */
class UploadedLogLM
{
    public static function getUploadedLog()
    {

        $builder = UploadedLog::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getEntitiesOurAccountDate($date): array
    {
        if (!empty($date)) {
            $dt = DateTime::createFromFormat('d.m.Y', $date);
            $check_date = $dt ? $dt->format('Y-m-d') : date('Y-m-d');
        } else {
            $check_date = date('Y-m-d');
        }

        $builder = LegalEntities::newQueryBuilder()
            ->select([
                'le.*',
                'ul.transactions_count',
                'ul.bank_order_count',
                'ul.new_accounts_count',
                'ul.client_returns_count',
                'ul.supplier_returns_count',
                'ul.client_services_returns_count',
                'ul.goods_supplier',
                'ul.goods_client',
                'ul.goods_client_service',
                'ul.expenses',
                'ul.income',
                'ul.file_name',
                'ul.date as uploaded_date',
            ])
            ->from('uploaded_log as ul')
            ->leftJoin('legal_entities le')
            ->on([
                'le.id = ul.legal_id',
                "DATE(ul.date) = '{$check_date}'",
            ])
            ->where([
                'le.our_account = 1',
            ])
            ->groupBy('ul.id');

        $rows = PdoConnector::execute($builder);
        if (!$rows) {
            return [];
        }

        Logger::log(print_r($rows, true), 'legal_entities');
        $result = [];

        foreach ($rows as $row) {

            if (!empty($account->date_created)) {
                $dt = DateTime::createFromFormat('Y-m-d', $account->date_created);
                $date_created = $dt ? $dt->format('d.m.Y') : date('d.m.Y');
            } else {
                $date_created = date('d.m.Y');
            }


            $is_expired = true;
            if ($row->file_name) {
                $is_expired = false;
            }

            $result[] = [
                'id' => $row->id,
                'company_name' => $row->company_name,
                'bank_name' => $row->bank_name,
                'inn' => $row->inn,
                'total_received' => $row->total_received,
                'total_written_off' => $row->total_written_off,
                'final_remainder' => $row->final_remainder,
                'transactions_count' => $row->transactions_count ?? 0,
                'bank_order_count' => $row->bank_order_count ?? 0,
                'new_accounts_count' => $row->new_accounts_count ?? 0,
                'client_returns_count' => $row->client_returns_count ?? 0,
                'supplier_returns_count' => $row->supplier_returns_count ?? 0,
                'client_services_returns_count' => $row->client_services_returns_count ?? 0,
                'goods_supplier' => $row->goods_supplier ?? 0,
                'goods_client' => $row->goods_client ?? 0,
                'goods_client_service' => $row->goods_client_service ?? 0,
                'expenses' => $row->expenses ?? 0,
                'income' => $row->income ?? 0,
                'file_name' => $row->file_name ?? null,
                'date_created' => $date_created,
                'is_expired' => $is_expired,
            ];
        }

        usort($result, function ($a, $b) {
            $a_no_date = empty($a['date_created']);
            $b_no_date = empty($b['date_created']);
            $a_zero = empty($a['final_remainder']);
            $b_zero = empty($b['final_remainder']);

            if ($a_no_date !== $b_no_date) {
                return $a_no_date <=> $b_no_date;
            }

            return $a_zero <=> $b_zero;
        });

        return $result;
    }

    public static function deleteUploadedLog(int $id)
    {

        $builder = UploadedLog::newQueryBuilder()
            ->delete()
            ->where([
                "id =" . $id,
            ]);


        return PdoConnector::execute($builder);
    }

    public static function insertUploadedLog(array $dataset)
    {
        $builder = UploadedLog::newQueryBuilder()
            ->insert($dataset);

        return PdoConnector::execute($builder);
    }


    public static function getAccountsUploadedMaxTime(array $selects_inn)
    {
        if (!$selects_inn) {
            return [];
        }

        $selects_inn = implode(',', array_map('intval', $selects_inn));

        $builder = UploadedLog::newQueryBuilder()
            ->select([
                'MAX(date) as max_date',
            ])
            ->where([
                "inn IN($selects_inn)"
            ]);


        return PdoConnector::execute($builder);
    }

    public static function getAccountsUploadedMaxid(): int
    {
        $builder = UploadedLog::newQueryBuilder()
            ->select([
                'MAX(id) as max_id',
            ])
            ->limit(1);


        return PdoConnector::execute($builder)[0]->max_id ?? 1;
    }

}