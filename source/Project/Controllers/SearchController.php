<?php

namespace Source\Project\Controllers;


use Source\Base\Core\Logger;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\StatementLogLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\Viewer\ApiViewer;


class SearchController extends BaseController
{

    public function extractSearch(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $query = InformationDC::get('query');
        $type = InformationDC::get('type');
        $limit = 30;
        $offset = $page * $limit;
        $legal_id = false;

        if ($type == 'inn') {
            $entities = LegalEntitiesLM::getEntitiesInn($query);
            $legal_id = $entities->id ?? false;
        }

        if ($type == 'company') {
            $entities = LegalEntitiesLM::getEntitiesInnCompany($query);
            $legal_id = $entities->id ?? false;
        }

        if ($type == 'bank') {
            $entities = LegalEntitiesLM::getEntitiesBank($query);
            $legal_id = $entities->id ?? false;
        }

        if (!$legal_id) {
            return $this->twig->render('Entities/NoAccount.twig');
        } else {
            $entities = LegalEntitiesLM::getEntitiesId($legal_id);
            $entities = [
                'user_name' => $entities->user_name ?? '',
                'user_role' => $entities->user_role ?? '',
                'inn' => $entities->inn,
                'bank_account' => $entities->bank_account ?? '',
                'bank_name' => $entities->bank_name ?? '',
                'company_name' => $entities->company_name ?? '',
            ];
        }

        $transactions = TransactionsLM::getFromAccountId($legal_id, $offset, $limit, $date_from, $date_to);
        $transactions_count = TransactionsLM::getFromAccountIdCount($legal_id);
        $page_count = ceil($transactions_count / $limit);

        return $this->twig->render('Search/LegalEntities.twig', [
            'page' => $page + 1,
            'entities' => $entities,
            'transactions' => $transactions,
            'page_count' => $page_count,
        ]);
    }

}
