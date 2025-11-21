<?php /** @noinspection ALL */

namespace Source\Project\Controllers;


use DateTime;
use Source\Base\Constants\Settings\Path;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\Controllers\Base\BaseController;
use Source\Project\DataContainers\InformationDC;
use Source\Project\DataContainers\RequestDC;
use Source\Project\LogicManagers\DocumentLM\DocumentExtractLM;
use Source\Project\LogicManagers\DocumentLM\DocumentLM;
use Source\Project\LogicManagers\DocumentLM\TransactionsProcessLM;
use Source\Project\LogicManagers\LogicPdoModel\BankAccountsLM;
use Source\Project\LogicManagers\LogicPdoModel\BankOrderLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\LoadedTransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\MutualSettlementLM;
use Source\Project\LogicManagers\LogicPdoModel\RemainingMutualSettlementLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\Models\BankOrder;
use Source\Project\Models\Transactions;
use Source\Project\Viewer\ApiViewer;


class DebtsController extends BaseController
{

    /**
     * @return array
     * @throws \Exception
     */
    public function debtsClientServices(): string
    {

        $mutual_settlement = DebtsLM::getDebtsClientServicesGroup();

        //Logger::log(print_r($client_test, true), 'mutualSettlement');


        return $this->twig->render('Debts/DebtsClientServices.twig', [
            'mutual_settlement' => $mutual_settlement
        ]);
    }

    public function mutualSettlement(): array
    {
        $amount_repaid = InformationDC::get('amount_repaid');
        $supplier_id = InformationDC::get('supplier_id');

        $supplier_goods = LegalEntitiesLM::getDebtsSupplierGoods($supplier_id);
        $client_services = DebtsLM::getDebtsClientServicesFrom($supplier_id);

        $supplier_goods_sum = $supplier_goods[0]['supplier_sum_amount'] ?? 0;
        $client_services_sum = $client_services[0]['transaction_amount_sum'] ?? 0;


        if (!$supplier_goods_sum || !$client_services_sum) {
            return ApiViewer::getErrorBody(['value' => 'not_goods_or_client_service']);
        }


        if ($client_services_sum < $amount_repaid) {
            return ApiViewer::getErrorBody(['value' => 'amount_repaid']);
        }

        $mutual_settlement =
            DebtsLM::getDebtsMutualSettlement(
            $amount_repaid,
            $supplier_id,
            $client_services,
            $supplier_goods
        );

        $repayments = $mutual_settlement['repayments'];
        $insert_mutual_settlement = $mutual_settlement['mutual_settlement'];
        $remaining_mutual_settlement = $mutual_settlement['remaining_mutual_settlement'];

        foreach ($repayments as $repayment) {

            if ($repayment['new_debt_amount'] > 0) {

                $tet = DebtsLM::updateDebtsId([
                    'amount =' . $repayment['new_debt_amount'],
                ], $repayment['debt_id']);

            } else {
                $tet = DebtsLM::updateDebtsId([
                    'status = offs_confirmation',
                ], $repayment['debt_id']);
            }


            if ($repayment['new_transaction_amount'] > 0) {
                TransactionsLM::updateTransactionsId([
                    'amount =' . $repayment['new_transaction_amount'],
                    'interest_income =' . $repayment['new_interest_income'],
                    'status = pending',
                ], $repayment['transaction_id']);

            } else {
                TransactionsLM::updateTransactionsId([
                    'status = pending',
                ], $repayment['transaction_id']);
            }
        }

        //Logger::log(print_r($repayments, true), 'mutualSettlement');
        //Logger::log(print_r($insert_mutual_settlement, true), 'mutualSettlement');

        MutualSettlementLM::insertNewMutualSettlement($insert_mutual_settlement);
        RemainingMutualSettlementLM::setNewRemaining($remaining_mutual_settlement);


        return ApiViewer::getOkBody([
            'success' => 'ok',
        ]);
    }

    public function getMutualSettlements(): string
    {
        $page = InformationDC::get('page') ?? 0;
        $supplier_id = InformationDC::get('supplier_id') ?? null;

        $date_from = InformationDC::get('date_from');
        $date_to = InformationDC::get('date_to');
        $limit = 30;
        $offset = $page * $limit;


        $mutual_settlements = MutualSettlementLM::getMutualSettlement($date_from, $date_to, $offset, $limit, $supplier_id);
        $mutual_count = MutualSettlementLM::getMutualSettlementCount($date_from, $date_to, $supplier_id);
        $page_count = ceil($mutual_count / $limit);

        //Logger::log(print_r($mutual_count, true), 'getMutualSettlements');


        return $this->twig->render('Debts/GetMutualSettlements.twig', [
            'mutual_settlements' => $mutual_settlements,
            'page' => $page + 1,
            'page_count' => $page_count,
        ]);
    }

    public function getMutualsData(): string
    {
        $date = InformationDC::get('date');
        $supplier_id = InformationDC::get('supplier_id');

        $supplier_accounts = LegalEntitiesLM::getSupplierAccounts($supplier_id);

        if ($supplier_accounts) {
            $tet = MutualSettlementLM::getMutualDebtsSettlement($supplier_accounts->accounts_id, $date);
        }


        $supplier = SuppliersLM::getSuppliersId($supplier_id);
        $formatted_date = DateTime::createFromFormat('d.m.Y', $date)->format('Y-m-d');
        $remaining = RemainingMutualSettlementLM::getRemainingMutualSettlement($formatted_date, $supplier_id);

        //Logger::log(print_r($supplier_id, true), 'getMutualsData');
        Logger::log(print_r($remaining, true), 'getMutualsData');


        return $this->twig->render('Debts/GetMutualsData.twig', [
            'mutual_settlements' => $tet,
            'date' => $date,
            'username' => $supplier->username,
            'email' => $supplier->email,
            'remaining_goot' => $remaining->supplier_goods,
            'remaining_client' => $remaining->client_services,
            'client_services_name' => $supplier->client_services_name,
        ]);
    }


}
