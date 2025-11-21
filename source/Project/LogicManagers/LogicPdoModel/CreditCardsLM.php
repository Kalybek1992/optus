<?php

namespace Source\Project\LogicManagers\LogicPdoModel;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Controllers\CardsController;
use Source\Project\Models\BankAccounts;
use Source\Project\Models\Clients;
use Source\Project\Models\Couriers;
use Source\Project\Models\CreditCards;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\MutualSettlement;
use Source\Project\Models\Users;


/**
 *
 */
class CreditCardsLM
{

    public static function getMutualSettlementCount($date_from, $date_to)
    {

        $builder = CreditCards::newQueryBuilder()
            ->select([
                'COUNT(id) as mutual_settlement_count',
            ]);



        return PdoConnector::execute($builder)[0]->mutual_settlement_count ?? 0;
    }


    public static function getCardNumber($card_number)
    {
        $builder = CreditCards::newQueryBuilder()
            ->select([
                '*',
            ])
            ->where([
                'card_number =' . $card_number,
            ])
            ->limit(1);

        //Logger::log(print_r($mutual_settlements_arr, true), 'editDebtSupplier');

        return PdoConnector::execute($builder)[0] ?? [];
    }

    public static function getAllLegalCardNumber($legal_id): array
    {
        $builder = CreditCards::newQueryBuilder()
            ->select(['*'])
            ->where([
                'legal_id =' . $legal_id,
            ]);

        $cards = PdoConnector::execute($builder) ?? [];
        $cards_arr = [];

        if (!$cards) {
            return [];
        }

        foreach ($cards as $card) {
            $cards_arr[] = [
                'id' => $card->id,
                'card_number' => $card->card_number,
                'balance' => $card->balance,
                'date' => $card->date,
            ];
        }

        return $cards_arr;
    }

    public static function getAllCardNumber(): array
    {
        $builder = CreditCards::newQueryBuilder()
            ->select(['*']);

        $cards = PdoConnector::execute($builder) ?? [];
        $cards_arr = [];

        if (!$cards) {
            return [];
        }

        foreach ($cards as $card) {
            $cards_arr[] = [
                'id' => $card->id,
                'card_number' => $card->card_number,
                'balance' => $card->balance,
                'date' => $card->date,
            ];
        }

        return $cards_arr;
    }

    public static function setNewCreditCards($date)
    {
        $builder = CreditCards::newQueryBuilder()
            ->insert($date);


        return PdoConnector::execute($builder);
    }

    public static function getCardId($id)
    {
        $builder = CreditCards::newQueryBuilder()
            ->select(['*'])
            ->where([
                'id =' . $id,
            ])
            ->limit(1);



        return PdoConnector::execute($builder)[0] ?? null;
    }

    public static function getAllLegalCardNumbersByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $ids_str = implode(',', array_map('intval', $ids));


        $builder = CreditCards::newQueryBuilder()
            ->select(['*'])
            ->where([
                "legal_id IN($ids_str)"
            ]);



      return PdoConnector::execute($builder) ?? [];
  }
}