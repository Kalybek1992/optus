<?php

namespace Source\Project\LogicManagers\DocumentLM;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\LogicManagers\LogicPdoModel\BankAccountsLM;
use Source\Project\LogicManagers\LogicPdoModel\BankOrderLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientServicesLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\EndOfDaySettlementLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\SupplierBalanceLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\UploadedDocumentsLM;
use Source\Project\LogicManagers\LogicPdoModel\UploadedLogLM;
use Source\Project\Models\LegalEntities;
use Source\Project\Models\SupplierBalance;


class TransactionsProcessLM extends DocumentExtractLM
{
    public array $expenses = [];

    public array $income = [];

    public array $expenditure_on_goods = [];

    public array $transactions = [];

    public array $new_bank_accounts = [];

    public array $db_bank_accounts = [];

    public array $new_bank_order = [];

    public int $new_bank_accounts_count = 0;

    public int $transactions_count = 0;

    public $our_account_number = null;

    public array $transaction_pending = [];

    public array $date_update_report_supplier = [];

    public array $our_account = [];

    public int $bank_order_count = 0;

    public int $customer_client_returns_count = 0;

    public int $customer_supplier_returns_count = 0;

    public int $customer_client_services_returns_count = 0;

    public int $goods_client = 0;

    public int $goods_supplier = 0;

    public int $goods_client_service = 0;

    public int $income_sum = 0;

    public int $expense_sum = 0;

    public array $debts_repaid_company = [];

    public function __construct(array $lines, $section_document = 'СекцияДокумент')
    {
        parent::__construct($lines, $section_document);
        $this->transactions_count = count($this->payment_order);
        $this->bank_order_count = count($this->bank_order);
    }

    /**
     * Разделить на доходы и расходы
     */
    public function determineExpensesIncome(): static
    {

        foreach ($this->payment_order as $order) {
            if ($order['bank_account'] == $this->bank_exchange['bank_account']) {
                $this->expenses[] = $order;
                $this->expense_sum += $order['balance'];
            } else {
                $this->income[] = $order;
                $this->income_sum += $order['balance'];
            }
        }

        if ($this->expenses) {
            $this->our_account = [
                'company_name' => $this->expenses[0]['company_name'],
                'inn' => $this->expenses[0]['inn'],
                'bank_name' => $this->expenses[0]['bank_name'],
                'account' => $this->expenses[0]['bank_account'],
                'our_account' => 1,
            ];

            $this->our_account_number = $this->expenses[0]['bank_account'];
        } elseif ($this->income) {
            $this->our_account = [
                'company_name' => $this->income[0]['company_name_recipient'],
                'inn' => $this->income[0]['inn_recipient'],
                'bank_name' => $this->income[0]['bank_name_recipient'],
                'account' => $this->income[0]['bank_account_recipient'],
                'our_account' => 1,
            ];

            $this->our_account_number = $this->income[0]['bank_account'];
        }


//        Logger::log(
//            'determineExpensesIncome = this->our_inn ' . print_r($this->our_account, true),
//            'TransactionsProcess'
//        );
//
//        Logger::log(
//            'determineExpensesIncome = this->expenses ' . print_r($this->expenses, true),
//            'TransactionsProcess'
//        );
//
//        Logger::log(
//            'determineExpensesIncome = this->income ' . print_r($this->income, true),
//            'TransactionsProcess'
//        );

        return $this;
    }

    /**
     * Записать наш аккаунт если его нету и получить ид
     */
    public function getOurAccounts(): static
    {
        $our_account = LegalEntitiesLM::getBankAccounts($this->our_account_number);

        if (!$our_account) {
            $this->our_account['id'] = LegalEntitiesLM::getLegalEntitiesMaxId() + 1;
            LegalEntitiesLM::setNewLegalEntitie($this->our_account);
            $our_account = LegalEntitiesLM::getBankAccounts($this->our_account_number);
        }

        foreach ($our_account as $account) {
            $this->our_account = [
                'id' => $account->id,
                'company_name' => $account->company_name,
                'inn' => $account->inn,
                'bank_name' => $account->bank_name,
                'total_received' => $account->total_received,
                'total_written_off' => $account->total_written_off,
                'final_remainder' => $account->final_remainder,
                'date_created' => $account->date_created,
            ];
        }

//        Logger::log(
//            'getOurAccounts = this->our_account ' . print_r($this->our_account, true),
//            'TransactionsProcess'
//        );

        return $this;
    }


    /**
     * Получить известных счетов через ИНН
     */
    public function getFamousCompanies(): static
    {

        $account_implode = $this->getBankAccountImplode();

        if ($account_implode != '') {
            $this->db_bank_accounts = LegalEntitiesLM::getBankAccounts($account_implode);
        }

        return $this;
    }

    /**
     * Обработка новых аккаунтов
     */

    public function processingNewAccounts(): static
    {
        $this->getNewExpensesBankAccounts();
        $this->getNewIncomeBankAccounts();

        foreach ($this->db_bank_accounts as $db_account) {
            if ($this->new_bank_accounts[$db_account->account] ?? false) {
                unset($this->new_bank_accounts[$db_account->account]);
            }
        }

        if ($this->new_bank_accounts) {
            $this->setIdBankAccounts();
        }

        $account_implode = $this->getBankAccountImplode();
        if ($account_implode != '') {
            $this->db_bank_accounts = LegalEntitiesLM::getBankAccounts($account_implode . ', ' . $this->our_account_number);
        }


//        Logger::log(
//            'processingNewAccounts = this->new_bank_accounts ' . print_r($this->new_bank_accounts, true),
//            'TransactionsProcess'
//        );
//
//        Logger::log(
//            'processingNewAccounts = this->db_bank_accounts ' . print_r($this->db_bank_accounts, true),
//            'TransactionsProcess'
//        );


        $this->new_bank_accounts_count = count($this->new_bank_accounts);

        return $this;
    }

    /**
     * Обработка новых транзакций
     */
    public function setNewTransactions(): static
    {
        $unique_manager_dates = [];

        foreach ($this->payment_order as $transaction) {
            $from_account_id = '';
            $to_account_id = '';
            $percent = 0;
            $type = $this->our_account_number == $transaction['bank_account'] ? 'expense' : 'income';
            $date = $this->parseDateToMysqlFormat($transaction['date']);

            foreach ($this->db_bank_accounts as $account) {
                if ($transaction['bank_account'] == $account->account) {
                    $from_account_id = $account->id;
                }

                if ($transaction['bank_account_recipient'] == $account->account) {
                    $to_account_id = $account->id;
                }

                if ($account->percent > 0) {
                    $percent = $account->percent;
                }

                if (($transaction['bank_account'] == $account->account) && ($account->supplier_id ?? false) && (!$account->client_service_id)) {
                    $type = 'return_supplier';
                    $percent = 0;
                }

                if ($transaction['bank_account_recipient'] == $account->account && ($account->client_id ?? false)) {
                    $type = 'return';
                    $percent = 0;
                }

                if ($transaction['bank_account_recipient'] == $account->account && ($account->client_service_id ?? false)) {
                    $type = 'return_client_services';
                    $percent = 0;
                }

                if ($account->manager_id && $account->supplier_id) {
                    $key = $account->manager_id . '|' . $date;

                    if (!isset($unique_manager_dates[$key])) {
                        $unique_manager_dates[$key] = true;
                        $this->date_update_report_supplier[] = [
                            'manager_id' => $account->manager_id,
                            'supplier_id' => $account->supplier_id,
                            'date' => $date,
                        ];
                    }
                }
            }

            if ($type != 'return' && $type != 'return_supplier' && $type != 'return_client_services') {
                $percent_income = abs($transaction['balance']) * ($percent / 100);
            } else {
                $percent_income = 0;
                $percent = 0;
            }

            $this->transactions[] = [
                'type' => $type,
                'amount' => $transaction['balance'],
                'percent' => $percent,
                'interest_income' => $percent_income,
                'date' => $date,
                'description' => $transaction['type'],
                'from_account_id' => $from_account_id,
                'to_account_id' => $to_account_id,
                'status' => 'pending'
            ];
        }

//        Logger::log(
//            'setNewTransactions = this->transactions ' . print_r($this->transactions, true),
//            'TransactionsProcess'
//        );
//
//        Logger::log(
//            'setNewTransactions = this->date_update_report_supplier ' . print_r($this->date_update_report_supplier, true),
//            'TransactionsProcess'
//        );

        if ($this->transactions) {
            TransactionsLM::insertNewTransactions($this->transactions);
            $this->transaction_pending = TransactionsLM::getTransactionsStatusPending();
        }

        Logger::log(
            'setNewTransactions = this->transaction_pending ' . print_r($this->transaction_pending, true),
            'TransactionsProcess'
        );

        return $this;
    }

    /**
     * Возврат денег клиенту если есть долги
     */


    public function closeClientsDebt(): static
    {
        foreach ($this->transaction_pending as $key => $transaction) {

            if ($transaction->type == 'return') {

                $client_id = $transaction->recipient_client_id;
                $client = ClientsLM::getClientId($client_id);

                if ($client && $client['legal_id']) {
                    CompanyFinancesLM::insertTransactionsExpenses([
                        'transaction_id' => $transaction->id,
                        'client_id' => $client_id,
                        'comments' => 'Вернули долг через выписки клиент',
                        'type' => 'debt_repayment_transaction',
                        'status' => 'confirm_admin',
                        'issue_date' => $transaction->date,
                    ]);

                    $amount = $transaction->amount;
                    $percent = $transaction->recipient_percent;
                    $result = $amount - ($amount * $percent / 100);

                    DebtsLM::payOffClientsDebt(
                        $client['legal_id'],
                        $result,
                        $transaction->id
                    );

                    $this->customer_client_returns_count++;
                    Logger::log(
                        'closeClientsDebt = amount ' . print_r($transaction->amount, true),
                        'TransactionsProcess'
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Возврат денег клиент услуга
     */

    public function closeClientServicesDebt(): static
    {
        foreach ($this->transaction_pending as $key => $transaction) {

            if ($transaction->type == 'return_client_services') {

                $client_services_id = $transaction->recipient_client_service_id;
                $client_services = ClientServicesLM::clientServicesId($client_services_id);


                if ($client_services && $client_services['legal_id']) {
                    CompanyFinancesLM::insertTransactionsExpenses([
                        'transaction_id' => $transaction->id,
                        'client_services_id' => $client_services_id,
                        'comments' => 'Вернули долг через выписки клиент услуги',
                        'type' => 'debt_repayment_transaction',
                        'status' => 'confirm_admin',
                        'issue_date' => $transaction->date,
                    ]);

                    $amount = $transaction->amount;
                    $percent = $transaction->recipient_percent;
                    $result = $amount - ($amount * $percent / 100);

                    DebtsLM::payOffClientServicesDebt(
                        $client_services['legal_id'],
                        $result,
                        $transaction->id,
                    );

                    $this->customer_client_services_returns_count++;

                    Logger::log(
                        'closeClientServicesDebt = amount ' . print_r($transaction->amount, true),
                        'TransactionsProcess'
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Возврат денег поставшик
     */

    public function closeSupplierDebt(): static
    {
        foreach ($this->transaction_pending as $key => $transaction) {

            if ($transaction->type == 'return_supplier') {

                $supplier_id = $transaction->sender_supplier_id;
                $supplier = SuppliersLM::getSupplierIdLegal($supplier_id);

                if ($supplier && $supplier['legal_id']) {
                    CompanyFinancesLM::insertTransactionsExpenses([
                        'transaction_id' => $transaction->id,
                        'supplier_id' => $supplier_id,
                        'comments' => 'Поставщик вернул долг через выписки',
                        'type' => 'debt_repayment_transaction',
                        'status' => 'confirm_admin',
                        'issue_date' => $transaction->date,
                    ]);

                    $amount = $transaction->amount;
                    $percent = $transaction->sender_percent;
                    $result = $amount - ($amount * $percent / 100);

                    DebtsLM::returnOffSuppliersDebt(
                        $supplier['legal_id'],
                        $result,
                        $transaction->id,
                    );

                    $this->customer_supplier_returns_count++;

                    Logger::log(
                        'closeSupplierDebt = amount ' . print_r($transaction->amount, true),
                        'TransactionsProcess'
                    );
                }
            }
        }
        return $this;
    }

    /**
     * Обновить баланс существующих поставшиков
     */

    //TODO Надо подумать как обновлять баланс при возврате
    public function updateBalanceSupplier(): static
    {
        $insert_new_balance = [];

        foreach ($this->transaction_pending as $transaction) {
            if ($transaction->recipient_supplier_id && $transaction->type == 'expense') {
                $supplier_balance = SupplierBalanceLM::getSupplierBalance(
                    $transaction->recipient_id,
                    $transaction->sender_id
                );

                if (!$supplier_balance) {

                    $key = $transaction->recipient_id . '_' . $transaction->sender_id;

                    if (!isset($insert_new_balance[$key])) {
                        $insert_new_balance[$key] = [
                            'legal_id' => $transaction->recipient_id,
                            'sender_legal_id' => $transaction->sender_id,
                            'amount' => $transaction->amount,
                        ];
                    } else {
                        $insert_new_balance[$key]['amount'] += $transaction->amount;
                    }

                }

                if ($supplier_balance) {
                    $new_balance = $supplier_balance->amount + $transaction->amount;
                    SupplierBalanceLM::updateSupplierBalance(
                        [
                            'amount = ' . $new_balance,
                        ],
                        $transaction->recipient_id,
                        $transaction->sender_id,
                    );
                }
            }
        }

        if ($insert_new_balance) {
            $insert_new_balance = array_values($insert_new_balance);
            SupplierBalanceLM::setNewSupplierBalance($insert_new_balance);
        }

        Logger::log(
            'updateBalanceSupplie = insert_new_balance ' . print_r($insert_new_balance, true),
            'TransactionsProcess'
        );

        return $this;
    }

    /**
     * Оформить покупки услуг
     */

    public function makePurchasesServices(): static
    {
        $this->supplierGoods();
        $this->clientServices();
        $this->clientGoods();

        if ($this->expenditure_on_goods) {
            DebtsLM::setNewDebts($this->expenditure_on_goods);
        }

        if ($this->date_update_report_supplier) {
            EndOfDaySettlementLM::updateEndOfDayTransactions($this->date_update_report_supplier);
        }

        TransactionsLM::updateTransactionsStatusPending();
        return $this;
    }

    /**
     * Определить покупку услуги у поставшика
     */
    private function supplierGoods(): void
    {
        foreach ($this->transaction_pending as $transaction) {

            if ($transaction->recipient_supplier_id && $transaction->type == 'expense') {
                $from_account_id = $transaction->from_account_id;
                $to_account_id = $transaction->to_account_id;

                $this->expenditure_on_goods[] = [
                    'from_account_id' => $from_account_id,
                    'to_account_id' => $to_account_id,
                    'transaction_id' => $transaction->id,
                    'type_of_debt' => 'supplier_goods',
                    'amount' => $transaction->amount - $transaction->interest_income,
                    'date' => date('Y-m-d'),
                    'status' => 'active'
                ];
            }
        }
    }


    /**
     * Определить покупку услуги у нас клиент сервис (и если поставщик назначил клиента)
     */
    private function clientServices(): void
    {
        foreach ($this->transaction_pending as $transaction) {
            if (
                $transaction->sender_supplier_id &&
                $transaction->sender_client_service_id &&
                $transaction->type == 'income'
            ) {
                $from_account_id = $transaction->from_account_id;
                $to_account_id = $transaction->to_account_id;

                $this->expenditure_on_goods[] = [
                    'from_account_id' => $from_account_id,
                    'to_account_id' => $to_account_id,
                    'transaction_id' => $transaction->id,
                    'type_of_debt' => 'client_services',
                    'amount' => $transaction->amount - $transaction->interest_income,
                    'date' => date('Y-m-d'),
                    'status' => 'active'
                ];

                if ($transaction->sender_supplier_client_id) {

                    $client = ClientsLM::getClientId($transaction->sender_supplier_client_id);
                    $percent = $client['percentage'];
                    $amount = $transaction->amount - ($transaction->amount * $percent / 100);

                    $this->expenditure_on_goods[] = [
                        'from_account_id' => $from_account_id,
                        'to_account_id' => $to_account_id,
                        'transaction_id' => $transaction->id,
                        'type_of_debt' => 'сlient_debt_supplier',
                        'amount' => $amount,
                        'date' => date('Y-m-d'),
                        'status' => 'active'
                    ];
                }

                $this->goods_client_service++;
            }
        }
    }

    /**
     * Определить покупку клиента
     */
    private function clientGoods(): void
    {
        foreach ($this->transaction_pending as $transaction) {
            if ($transaction->recipient_our_account &&
                $transaction->sender_client_id &&
                $transaction->type == 'income'
            ) {
                $from_account_id = $transaction->from_account_id;
                $to_account_id = $transaction->to_account_id;

                $this->expenditure_on_goods[] = [
                    'from_account_id' => $from_account_id,
                    'to_account_id' => $to_account_id,
                    'transaction_id' => $transaction->id,
                    'type_of_debt' => 'client_goods',
                    'amount' => $transaction->amount - $transaction->interest_income,
                    'date' => date('Y-m-d'),
                    'status' => 'active'
                ];

                $this->goods_client++;
            }
        }
    }


    /**
     * Определить покупку клиента
     */
    private function mutualSettlements($transaction_id, $user)
    {
        foreach ($this->transaction_pending as $transaction) {
            if ($transaction->id == $transaction_id) {
                if ($user['legal_id']) {

                    $amount = $transaction->amount;

                    $amount_new = DebtsLM::mutualSettlementsDebts(
                        $user['legal_id'],
                        $amount,
                        $transaction->id,
                    );

                    if ($amount_new != $transaction->amount) {
                        CompanyFinancesLM::insertTransactionsExpenses([
                            'transaction_id' => $transaction->id,
                            'supplier_id' => $user['id'],
                            'comments' => 'Взаиморасчеты с поставщиком',
                            'type' => 'debt_repayment_transaction',
                            'status' => 'confirm_admin',
                            'issue_date' => $transaction->date,
                        ]);

                        if ($amount_new > 0){
                            $new = $amount_new - ($amount_new * $transaction->percent / 100);


                        }

                    }

                }else{
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Обработка новых получателей
     */
    private function getNewExpensesBankAccounts(): void
    {
        $sections = $this->expenses;

        //$transaction['bank_account_recipient'],
        foreach ($sections as $section) {
            //Получатель должен нам
            $this->new_bank_accounts[$section['bank_account_recipient']] = [
                'inn' => $section['inn_recipient'],
                'bank_name' => $section['bank_name_recipient'],
                'company_name' => $section['company_name_recipient'],
                'account' => $section['bank_account_recipient'],
            ];
        }
    }

    /**
     * Обработка новых отправителей
     */
    private function getNewIncomeBankAccounts(): void
    {
        $sections = $this->income;

        //$transaction['bank_account']
        foreach ($sections as $section) {
            //Нам отправили деньги мы должны им вернуть
            $this->new_bank_accounts[$section['bank_account']] = [
                'inn' => $section['inn'],
                'bank_name' => $section['bank_name'],
                'company_name' => $section['company_name'],
                'account' => $section['bank_account'],
            ];
        }
    }


    /**
     * Установка новых аккаунтов
     */
    private function setIdBankAccounts(): void
    {
        $legal_entities_insert = [];
        $legal_entities_max = LegalEntitiesLM::getLegalEntitiesMaxId();

        foreach ($this->new_bank_accounts as $key => $value) {
            $legal_entities_max += 1;
            $legal_entities_insert[] = [
                'id' => $legal_entities_max,
                'inn' => $value['inn'],
                'bank_name' => $value['bank_name'],
                'company_name' => $value['company_name'],
                'account' => $value['account'],
            ];
        }

        $this->new_bank_accounts = $legal_entities_insert;

        LegalEntitiesLM::setNewLegalEntitie($this->new_bank_accounts);
    }


    /**
     * Обработка банковских ордеров
     */
    public function setNewTransactionsBankOrder(): static
    {
        $id = $this->our_account['id'];

        if ($id && $this->bank_order) {

            foreach ($this->bank_order as $key => $bank_order) {
                $transaction_type = $this->detectTransactionType($bank_order['type']);
                $date = $this->parseDateToMysqlFormat($bank_order['date']);

                if ($bank_order['balance']) {
                    $this->new_bank_order[] = [
                        'type' => $transaction_type,
                        'amount' => $bank_order['balance'],
                        'date' => $date ?? 0,
                        'description' => $bank_order['type'] ?? 0,
                        'from_account_id' => $id,
                        'document_number' => $bank_order['document_number'] ?? 0,
                        'recipient_company_name' => $bank_order['company_name_recipient'] ?? 0,
                        'recipient_bank_name' => $bank_order['bank_name_recipient'] ?? 0,
                    ];
                } else {
                    unset($this->bank_order[$key]);
                }
            }
        }

        if ($this->new_bank_order) {
            BankOrderLM::insertNewBankOrder($this->new_bank_order);
        }

        return $this;
    }

    /**
     * Сохранить загруженных транзакции
     */
    public function setLoadedTransactions(): static
    {
        $data = array_merge(
            $this->bank_order,
            $this->payment_order,
        );

        $loaded_transactions = [];


        foreach ($data as $bank_order) {
            $loaded_transactions[] = [
                'inn' => $bank_order['inn'],
                'document_number' => $bank_order['document_number'],
                'date' => time()
            ];
        }


        UploadedDocumentsLM::insertNewLoadedTransactions($loaded_transactions);

        return $this;
    }

    /**
     * Возвращаем все инн из выписки с помощью employed для запроса базу данных
     */

    private function getBankAccountImplode(): string
    {

        $select_bank_account = [];
        foreach ($this->payment_order as $entry) {

            if ($entry['bank_account'] != $this->our_account_number) {
                $select_bank_account[] = $entry['bank_account'];
            }

            if ($entry['bank_account_recipient'] != $this->our_account_number) {
                $select_bank_account[] = $entry['bank_account_recipient'];
            }
        }

        if (!$select_bank_account) {
            return '';
        }

        $remove_duplicates = array_unique($select_bank_account);
        $array_values = array_values($remove_duplicates);


        return implode(', ', $array_values);
    }


    private function parseDateToMysqlFormat(string $raw_date): ?string
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


    private function detectTransactionType(string $description): ?string
    {
        $commissionKeywords = [
            'комиссия', 'ком.за', 'плата за', 'плата', 'fee'
        ];

        $withdrawalKeywords = [
            'снятие', 'выдача', 'atm', 'устройство самообслуживания'
        ];

        $descriptionLower = mb_strtolower($description, 'UTF-8');


        foreach ($commissionKeywords as $word) {
            if (mb_strpos($descriptionLower, $word) !== false) {
                return 'commission';
            }
        }


        foreach ($withdrawalKeywords as $word) {
            if (mb_strpos($descriptionLower, $word) !== false) {
                return 'withdrawal';
            }
        }

        return 'unknown';
    }

    /**
     * Запись остатков для нашего компаний
     */

    public function updateKnownLegalEntitiesTotals(): static
    {
        $date = null;
        $our_account_id = $this->our_account['id'];
        $total_received_db = $this->our_account['total_received'] ?? 0;

        if ($this->bank_exchange['end_date'] ?? false) {
            $date = DateTime::createFromFormat('d.m.Y', $this->bank_exchange['end_date']);
        }

        $total_received = $total_received_db + floatval($this->bank_exchange['total_received'] ?? 0);

        if ($date) {
            LegalEntitiesLM::updateLegalEntities([
                'total_received =' . $total_received,
                'total_written_off =' . floatval($this->bank_exchange['total_written_off'] ?? 0),
                'final_remainder =' . floatval($this->bank_exchange['final_remainder'] ?? 0),
                'date_created =' . $date->format('Y-m-d'),
            ], $our_account_id);
        } else {
            LegalEntitiesLM::updateLegalEntities([
                'total_received =' . $total_received,
                'total_written_off =' . floatval($this->bank_exchange['total_written_off'] ?? 0),
                'final_remainder =' . floatval($this->bank_exchange['final_remainder'] ?? 0)
            ], $our_account_id);
        }

        return $this;
    }

    /**
     * Сохраняем последний загрузку выписки
     */

    public function lastStatementDownload($file_name): static
    {
        $our_account_id = $this->our_account['id'];
        $transactions_count = $this->transactions_count;
        $new_bank_accounts_count = $this->new_bank_accounts_count;
        $bank_order_count = $this->bank_order_count;
        $customer_client_returns_count = $this->customer_client_returns_count;
        $customer_supplier_returns_count = $this->customer_supplier_returns_count;
        $customer_client_services_returns_count = $this->customer_client_services_returns_count;
        $goods_supplier = $this->goods_supplier;
        $goods_client = $this->goods_client;
        $goods_client_service = $this->goods_client_service;


        $insert_uploaded_log = [
            'legal_id' => $our_account_id,
            'transactions_count' => $transactions_count,
            'new_accounts_count' => $new_bank_accounts_count,
            'bank_order_count' => $bank_order_count,
            'client_returns_count' => $customer_client_returns_count,
            'supplier_returns_count' => $customer_supplier_returns_count,
            'client_services_returns_count' => $customer_client_services_returns_count,
            'goods_supplier' => $goods_supplier,
            'goods_client' => $goods_client,
            'goods_client_service' => $goods_client_service,
            'expenses' => $this->expense_sum,
            'income' => $this->income_sum,
            'file_name' => $file_name,
            'date' => date('Y-m-d H:i:s'),
        ];

        UploadedLogLM::insertUploadedLog($insert_uploaded_log);

        return $this;
    }
}