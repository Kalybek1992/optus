<?php

namespace Source\Project\Controllers;


use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\LogicManagers\LogicPdoModel\CreditCardsLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\DataContainers\InformationDC;
use Source\Project\Viewer\ApiViewer;


class CardsController extends BaseController
{

    /**
     * @return array
     * @throws \Exception
     */

    public function addCard(): array
    {
        $card_number = InformationDC::get('card_number');
        $legal_id = InformationDC::get('legal_id');
        $get_card = CreditCardsLM::getCardNumber($card_number);
        $entities = LegalEntitiesLM::getEntitiesId($legal_id);

        if ($get_card) {
            return ApiViewer::getErrorBody(['value' => 'duplicate_card']);
        }

        if (!$entities) {
            return ApiViewer::getErrorBody(['value' => 'not_legal']);
        }

        if (!$entities->our_account) {
            return ApiViewer::getErrorBody(['value' => 'not_our_account']);
        }

        CreditCardsLM::setNewCreditCards([
            'card_number' => $card_number,
            'legal_id' => $entities->id,
            'balance' => 0,
            'date' => date('Y-m-d')
        ]);


        Logger::log(print_r($entities, true), 'addCard');


        return ApiViewer::getOkBody([
            'success' => 'ok',
        ]);
    }

    public function getOurCompany(): array
    {

        $companies = LegalEntitiesLM::getEntitiesOurAccount();

        //Logger::log(print_r($companies, true), '$companies');


        return ApiViewer::getOkBody([
            'success' => 'ok',
            'companies' => $companies
        ]);
    }

    public function getOurCart(): array
    {

        $companies = CreditCardsLM::getAllCardNumber();

        //Logger::log(print_r($companies, true), '$companies');


        return ApiViewer::getOkBody([
            'success' => 'ok',
            'companies' => $companies
        ]);
    }

}
