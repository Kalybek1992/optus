<?php

namespace Source\Project\LogicManagers\DocumentLM;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\Connectors\PdoConnector;
use Source\Project\LogicManagers\LogicPdoModel\BankAccountsLM;
use Source\Project\LogicManagers\LogicPdoModel\BankOrderLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\EndOfDaySettlementLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\UploadedDocumentsLM;
use Source\Project\Models\LegalEntities;


class TransactionsProcessLM extends DocumentExtractLM
{
    public array $expenses = [];

    public array $income = [];

    public array $update_balance = [];

    public array $expenditure_on_goods = [];


    public array $transactions = [];

    public array $new_bank_accounts = [];

    public array $db_bank_accounts = [];

    public array $new_bank_order = [];

    public int|bool $our_account_id = false;

    public int $new_bank_accounts_count = 0;

    public int $transactions_count = 0;

    public int $bank_accounts_updated_count = 0;

    public array $transaction_pending = [];


    public array $bank_accounts = [];

    public function __construct(array $lines, $section_document = 'СекцияДокумент')
    {
        parent::__construct($lines, $section_document);
        $this->transactions_count = count($this->bank_order) + count($this->payment_order);
    }

    /**
     * Разделить на доходы и расходы
     */
    public function determineExpensesIncome(): static
    {
        Logger::log(print_r('determineExpensesIncome', true), 'tet');

        foreach ($this->payment_order as $order) {
            if ($order['bank_account'] == $this->bank_exchange['bank_account']) {
                $this->expenses[] = $order;
            } else {
                $this->income[] = $order;
            }
        }


        return $this;
    }

    /**
     * Получить известных счетов
     */
    public function getFamousCompanies(): static
    {
        Logger::log(print_r('getFamousCompanies', true), 'tet');


        $this->bank_accounts = $this->getBankAccountArr();
        if ($this->bank_accounts) {
            $this->db_bank_accounts = LegalEntitiesLM::getBankAccounts($this->bank_accounts);
        }

        return $this;
    }

    /**
     * Обновить баланс существующих
     */
    public function updateBalanceExisting(): static
    {
        $this->getOurBalance();
        $this->getNewBalanceExpenses();
        $this->getNewBalanceIncome();
        Logger::log(print_r('updateBalanceExisting', true), 'tet');


        if ($this->update_balance) {
            foreach ($this->update_balance as $id => $value) {
                BankAccountsLM::updateBankAccounts([
                    'balance =' . $value['balance'],
                ], $id);
            }

            $this->bank_accounts_updated_count = count($this->update_balance);
        }

        return $this;
    }

    /**
     * Возврат денег клиенту
     */

    public function closeClientsDebt(): static
    {
        Logger::log(print_r('closeClientsDebt', true), 'tet');
        foreach ($this->db_bank_accounts as $bank_account) {

            if ($this->update_balance[$bank_account->id] ?? null && $this->update_balance[$bank_account->id]['type_of_translation'] == 'expenses') {

                if ($bank_account->debt ?? false) {
                    $amount = $this->update_balance[$bank_account->id]['amount'] ?? 0;
                    $role = $this->update_balance[$bank_account->id]['role'] ?? 'not_role';


                    if ($role == 'client') {
                        $debt = (int)$bank_account->debt;

                        DebtsLM::updateDebts([
                            'status = paid',
                        ], $bank_account->id);

                        if ($debt > $amount) {
                            $this->expenditure_on_goods[] = [
                                'from_account_id' => $bank_account->id,
                                'to_account_id' => $bank_account->id,
                                'type_of_debt' => 'client_goods',
                                'amount' => $bank_account->debt - $amount,
                                'status' => 'active',
                            ];
                        }
                    }
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
        Logger::log(print_r('closeClientServicesDebt', true), 'tet');
        foreach ($this->db_bank_accounts as $bank_account) {

            if ($this->update_balance[$bank_account->id] ?? null && $this->update_balance[$bank_account->id]['type_of_translation'] == 'expenses') {

                if ($bank_account->debt ?? false) {
                    $amount = $this->update_balance[$bank_account->id]['amount'] ?? 0;
                    $role = $this->update_balance[$bank_account->id]['role'] ?? 'not_role';

                    if ($role == 'client_services') {
                        $debt = (int)$bank_account->debt;

                        DebtsLM::updateDebts([
                            'status = paid',
                        ], $bank_account->id);

                        if ($debt > $amount) {
                            $this->expenditure_on_goods[] = [
                                'from_account_id' => $bank_account->id,
                                'to_account_id' => $bank_account->id,
                                'type_of_debt' => 'client_services',
                                'amount' => $bank_account->debt - $amount,
                                'status' => 'active',
                            ];
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Обработка новых аккаунтов
     */

    public function processingNewAccounts(): static
    {
        Logger::log(print_r('processingNewAccounts', true), 'tet');

        $this->setNewBankAccounts();

        return $this;
    }

    /**
     * Оформить покупки услуг
     */

    public function makePurchasesServices(): static
    {

        $this->determinePurchaseService();

        if ($this->expenditure_on_goods) {
            DebtsLM::setNewDebts($this->expenditure_on_goods);
        }


        Logger::log(print_r('makePurchasesServices', true), 'tet');

        return $this;
    }


    /**
     * Обработка ухода в денег
     */

    private function getNewBalanceExpenses(): void
    {
        foreach ($this->expenses as $key => $expense) {
            foreach ($this->db_bank_accounts as $bank_account) {
                if ($bank_account->bank_account == $expense['bank_account_recipient']) {

                    $role = 'not_role';
                    $amount = $expense['balance'];
                    $balance = 0;

                    if ($bank_account->client_id ?? false) {
                        $role = 'client';
                        $balance = $bank_account->balance - $expense['balance'];
                    }

                    if ($bank_account->supplier_id ?? false) {
                        $role = 'supplier';
                        //$amount = $expense['balance'] - ($expense['balance'] * $bank_account->percent / 100);
                        //$balance = $bank_account->balance - $amount;
                        $balance = $bank_account->balance + $expense['balance'];
                    }

                    if (isset($this->update_balance[$bank_account->id])) {
                        $this->update_balance[$bank_account->id]['balance'] -= $amount;
                        $this->update_balance[$bank_account->id]['amount'] += $amount;
                    } else {
                        $this->update_balance[$bank_account->id] = [
                            'balance' => $balance,
                            'amount' => $amount,
                            'role' => $role,
                            'type_of_translation' => 'expenses'
                        ];
                    }

                    if ($this->our_account_id) {
                        $this->update_balance[$this->our_account_id]['balance'] += $amount;
                    }

                    unset($this->expenses[$key]);
                }
            }
        }
    }

    /**
     * Обработка приходов денег
     */

    private function getNewBalanceIncome(): void
    {
        foreach ($this->income as $key => $income) {
            foreach ($this->db_bank_accounts as $bank_account) {
                if ($bank_account->bank_account == $income['bank_account']) {

                    $role = 'not_role';
                    $amount = $income['balance']; // по умолчанию весь доход
                    $balance = $bank_account->balance + $amount;

                    if ($bank_account->client_id ?? false) {
                        $role = 'client';
                        $amount = $income['balance'] - ($income['balance'] * $bank_account->percent / 100);
                    }

                    if ($bank_account->supplier_id ?? false) {
                        $role = 'supplier';
                        //$amount = $income['balance'] - ($income['balance'] * $bank_account->percent / 100);
                    }

                    if (isset($this->update_balance[$bank_account->id])) {
                        $this->update_balance[$bank_account->id]['balance'] += $amount;
                        $this->update_balance[$bank_account->id]['amount'] += $amount;
                    } else {
                        $this->update_balance[$bank_account->id] = [
                            'balance' => $balance,
                            'amount' => $amount,
                            'role' => $role,
                            'type_of_translation' => 'income'
                        ];
                    }

                    if ($this->our_account_id) {
                        $this->update_balance[$this->our_account_id]['balance'] -= $amount;
                    }

                    unset($this->income[$key]);
                }
            }
        }
    }

    /**
     * Корректировка нашего баланса
     */
    private function getOurBalance(): void
    {
        foreach ($this->db_bank_accounts as $key => $bank_account) {

            if ($bank_account->bank_account == $this->bank_exchange['bank_account']) {
                $balance = $bank_account->balance;
                $this->our_account_id = $bank_account->id;

                $this->update_balance[$this->our_account_id] = [
                    'balance' => $balance,
                    'role' => 'our_account',
                ];
                break;
            }
        }

    }

    /**
     * Определить покупку услуги
     */
    private function determinePurchaseService(): void
    {
        $this->transaction_pending = TransactionsLM::getTransactionsStatusPending();
        TransactionsLM::updateTransactionsStatusPending();

        foreach ($this->payment_order as $transaction) {
            $from_account_id = '';
            $to_account_id = '';
            $sender_role = '';
            $recipient_role = '';
            $percent = 0;
            $amount = $transaction['balance'];
            $transaction_id = 0;
            $supplier_client_id = 0;

            foreach ($this->db_bank_accounts as $account) {
                if ($transaction['bank_account'] == $account->bank_account) {
                    $from_account_id = $account->id;

                    if ($account->supplier_id ?? false) {
                        $sender_role = 'supplier';
                    }

                    if ($account->client_id ?? false) {
                        $sender_role = 'client';
                    }

                    if ($account->our_account == 1) {
                        $sender_role = 'our_account';
                    }
                }

                if ($transaction['bank_account_recipient'] == $account->bank_account && !$account->client_services) {
                    $to_account_id = $account->id;

                    if ($account->supplier_id ?? false) {
                        $recipient_role = 'supplier';
                    }

                    if ($account->our_account == 1) {
                        $recipient_role = 'our_account';
                    }
                }

                if ($account->percent > 0) {
                    $percent = $account->percent;
                }

                $supplier_client_id = $account->supplier_client_id;
            }

            foreach ($this->transaction_pending as $pending) {
                if (
                    $pending->from_account_id == $from_account_id &&
                    $pending->to_account_id == $to_account_id &&
                    $pending->amount == $amount &&
                    $pending->description == $transaction['type'] &&
                    $pending->percent == $percent
                ) {
                    $transaction_id = $pending->id;
                }
            }

            if ($percent > 0) {
                $amount = $transaction['balance'] - ($transaction['balance'] * $percent / 100);
            }

            $type_of_debt = [
                'our_account->supplier' => 'supplier_goods',
                'supplier->our_account' => 'client_services',
                'client->our_account' => 'client_goods',
            ][$sender_role . '->' . $recipient_role] ?? '';

            //TODO Образование товарного долга перед поставщиком клиента

            if ($type_of_debt == 'client_services' && $supplier_client_id) {
                $client = ClientsLM::getClientId($supplier_client_id);
                $percent = $client['percentage'];
                $amount = $transaction['balance'] - ($transaction['balance'] * $percent / 100);

                $this->expenditure_on_goods[] = [
                    'from_account_id' => $from_account_id,
                    'to_account_id' => $to_account_id,
                    'transaction_id' => $transaction_id,
                    'type_of_debt' => 'сlient_debt_supplier',
                    'amount' => $amount,
                    'date' => date('Y-m-d'),
                    'status' => 'active'
                ];
            }

            // TODO образование и сохранение других долгов
            if ($type_of_debt && $transaction_id) {
                $this->expenditure_on_goods[] = [
                    'from_account_id' => $from_account_id,
                    'to_account_id' => $to_account_id,
                    'transaction_id' => $transaction_id,
                    'type_of_debt' => $type_of_debt,
                    'amount' => $amount,
                    'date' => date('Y-m-d'),
                    'status' => 'active'
                ];
            }
        }
    }


    /**
     * Обработка новых аккаунтов
     */
    private function setNewBankAccounts(): void
    {
        $this->getNewExpensesBankAccounts();
        $this->getNewIncomeBankAccounts();
        $this->getNewOurAccountBankAccounts();

        foreach ($this->db_bank_accounts as $db_account) {
            if ($this->new_bank_accounts[$db_account->bank_account] ?? false) {
                unset($this->new_bank_accounts[$db_account->bank_account]);
            }
        }

        if ($this->new_bank_accounts) {
            $this->setBankAccounts();
        }

        $this->new_bank_accounts_count = count($this->new_bank_accounts);
    }


    /**
     * Обработка новых получателей
     */
    private function getNewExpensesBankAccounts(): void
    {
        $sections = $this->expenses;

        foreach ($sections as $section) {
            //Мы отправили деньги
            if (array_key_exists($section['bank_account'], $this->new_bank_accounts)) {
                $this->new_bank_accounts[$section['bank_account']]['balance'] += $section['balance'];
            } else {
                $this->new_bank_accounts[$section['bank_account']] = [
                    'our_account' => 1,
                    'inn' => $section['inn'],
                    'kpp' => $section['kpp'] ?? 0,
                    'bank_account' => $section['bank_account'],
                    'bank_name' => $section['bank_name'],
                    'bic' => $section['bic'] ?? 0,
                    'correspondent_account' => $section['correspondent_account'],
                    'company_name' => $section['company_name'],
                    'balance' => $section['balance'],
                ];
            }

            //Получатель должен нам
            if (array_key_exists($section['bank_account_recipient'], $this->new_bank_accounts)) {
                $this->new_bank_accounts[$section['bank_account_recipient']]['balance'] += $section['balance'];
            } else {
                $this->new_bank_accounts[$section['bank_account_recipient']] = [
                    'inn' => $section['inn_recipient'],
                    'kpp' => $section['kpp_recipient'] ?? 0,
                    'bank_account' => $section['bank_account_recipient'],
                    'bank_name' => $section['bank_name_recipient'],
                    'bic' => $section['bic_recipient'] ?? 0,
                    'correspondent_account' => $section['correspondent_account_recipient'],
                    'company_name' => $section['company_name_recipient'],
                    'balance' => $section['balance'], // минусуем
                ];
            }
        }
    }

    /**
     * Обработка новых отправителей
     */
    private function getNewIncomeBankAccounts(): void
    {
        $sections = $this->income;

        foreach ($sections as $section) {
            //Нам отправили деньги мы должны им вернуть

            if (array_key_exists($section['bank_account'], $this->new_bank_accounts)) {
                $this->new_bank_accounts[$section['bank_account']]['balance'] += $section['balance'];
            } else {
                $this->new_bank_accounts[$section['bank_account']] = [
                    'inn' => $section['inn'],
                    'kpp' => $section['kpp'] ?? 0,
                    'bank_account' => $section['bank_account'],
                    'bank_name' => $section['bank_name'],
                    'bic' => $section['bic'] ?? 0,
                    'correspondent_account' => $section['correspondent_account'],
                    'company_name' => $section['company_name'],
                    'balance' => $section['balance'], // плюсуем
                ];
            }

            //Получатель мы
            if (array_key_exists($section['bank_account_recipient'], $this->new_bank_accounts)) {
                $this->new_bank_accounts[$section['bank_account_recipient']]['balance'] -= $section['balance'];
            } else {
                $this->new_bank_accounts[$section['bank_account_recipient']] = [
                    'our_account' => 1,
                    'inn' => $section['inn_recipient'],
                    'kpp' => $section['kpp_recipient'] ?? 0,
                    'bank_account' => $section['bank_account_recipient'],
                    'bank_name' => $section['bank_name_recipient'],
                    'bic' => $section['bic_recipient'] ?? 0,
                    'correspondent_account' => $section['correspondent_account_recipient'],
                    'company_name' => $section['company_name_recipient'],
                    'balance' => -1 * $section['balance'],
                ];
            }
        }
    }

    /**
     * Если только наш новый аккаунт
     */
    private function getNewOurAccountBankAccounts(): void
    {
        $our_account = in_array('our_account', array_column($this->update_balance, 'role'), true);
        $our_account_new = in_array(1, array_column($this->new_bank_accounts, 'our_account'), true);


        if (!$our_account && !$our_account_new) {

            foreach ($this->payment_order as $order) {
                //Нам отправили деньги мы должны им вернуть

                if ($order['bank_account'] == $this->bank_exchange['bank_account']) {

                    if (array_key_exists($order['bank_account'], $this->new_bank_accounts)) {
                        $this->new_bank_accounts[$order['bank_account']]['balance'] += $order['balance'];
                    } else {
                        $this->new_bank_accounts[$order['bank_account']] = [
                            'our_account' => 1,
                            'inn' => $order['inn'],
                            'kpp' => $order['kpp'] ?? 0,
                            'bank_account' => $order['bank_account'],
                            'bank_name' => $order['bank_name'],
                            'bic' => $order['bic'] ?? 0,
                            'correspondent_account' => $order['correspondent_account'],
                            'company_name' => $order['company_name'],
                            'balance' => $order['balance'], // плюсуем
                        ];
                    }

                } else {

                    if (array_key_exists($order['bank_account_recipient'], $this->new_bank_accounts)) {
                        $this->new_bank_accounts[$order['bank_account_recipient']]['balance'] -= $order['balance'];
                    } else {
                        $this->new_bank_accounts[$order['bank_account_recipient']] = [
                            'our_account' => 1,
                            'inn' => $order['inn_recipient'],
                            'kpp' => $order['kpp_recipient'] ?? 0,
                            'bank_account' => $order['bank_account_recipient'],
                            'bank_name' => $order['bank_name_recipient'],
                            'bic' => $order['bic_recipient'] ?? 0,
                            'correspondent_account' => $order['correspondent_account_recipient'],
                            'company_name' => $order['company_name_recipient'],
                            'balance' => -1 * $order['balance'],
                        ];
                    }
                }
            }
        }
    }

    /**
     * Установка новых аккаунтов
     */
    private function setBankAccounts(): void
    {
        $legal_entities_insert = [];
        $insert_balance_data = [];
        $legal_entities_max = LegalEntitiesLM::getLegalEntitiesMaxId();


        foreach ($this->new_bank_accounts as $key => $value) {
            $legal_entities_max += 1;

            $legal_entities_insert[] = [
                'id' => $legal_entities_max,
                'inn' => $value['inn'],
                'kpp' => $value['kpp'],
                'bank_account' => $value['bank_account'],
                'bank_name' => $value['bank_name'],
                'bic' => $value['bic'],
                'company_name' => $value['company_name'],
                'correspondent_account' => $value['correspondent_account'],
                'our_account' => $value['our_account'] ?? 0,
            ];

            $insert_balance_data[] = [
                'legal_entity_id' => $legal_entities_max,
                'balance' => $value['balance'],
            ];
        }


        if ($legal_entities_insert) {

            $builder = LegalEntities::newQueryBuilder()
                ->insert($legal_entities_insert);

            PdoConnector::execute($builder);

            BankAccountsLM::setNewBankAccounts($insert_balance_data);
        }
    }

    /**
     * Обработка новых транзакций
     */
    public function setNewTransactions(): static
    {
        $date_update_report_supplier = [];
        $unique_manager_dates = [];

        if ($this->bank_accounts) {
            $this->db_bank_accounts = LegalEntitiesLM::getBankAccounts($this->bank_accounts);
        }

        foreach ($this->payment_order as $transaction) {
            $from_account_id = '';
            $to_account_id = '';
            $percent = 0;
            $percent_income = 0;
            $type = $this->bank_exchange['bank_account'] == $transaction['bank_account'] ? 'expense' : 'income';
            $date = $this->parseDateToMysqlFormat($transaction['date']);

            foreach ($this->db_bank_accounts as $account) {
                if ($transaction['bank_account'] == $account->bank_account) {
                    $from_account_id = $account->id;
                }

                if ($transaction['bank_account_recipient'] == $account->bank_account) {
                    $to_account_id = $account->id;
                }

                if ($account->percent > 0) {
                    $percent = $account->percent;
                }

                if ($transaction['bank_account_recipient'] == $account->bank_account && $account->client_id ?? false) {
                    $type = 'return';
                    $percent = 0;
                }

                if ($transaction['bank_account_recipient'] == $account->bank_account && $account->client_services ?? false) {
                    $type = 'return_client_services';
                    $percent = 0;
                }

                if ($account->manager_id && $account->supplier_id) {
                    $key = $account->manager_id . '|' . $date;

                    if (!isset($unique_manager_dates[$key])) {
                        $unique_manager_dates[$key] = true;
                        $date_update_report_supplier[] = [
                            'manager_id' => $account->manager_id,
                            'supplier_id' => $account->supplier_id,
                            'date' => $date,
                        ];
                    }
                }
            }

            if ($percent > 0) {
                $percent_income = abs($transaction['balance']) * ($percent / 100);
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

        if ($this->transactions) {
            TransactionsLM::insertNewTransactions($this->transactions);
        }

        if ($date_update_report_supplier) {
            EndOfDaySettlementLM::updateEndOfDayTransactions($date_update_report_supplier);
        }

        return $this;
    }

    /**
     * Обработка банковских ордеров
     */
    public function setNewTransactionsBankOrder(): static
    {
        $exchange = LegalEntitiesLM::getBankAccount($this->bank_exchange['bank_account']);

        if ($exchange && $this->bank_order) {

            foreach ($this->bank_order as $key => $bank_order) {
                $transaction_type = $this->detectTransactionType($bank_order['type']);
                $date = $this->parseDateToMysqlFormat($bank_order['date']);

                if ($bank_order['balance']) {
                    $this->new_bank_order[] = [
                        'type' => $transaction_type,
                        'amount' => $bank_order['balance'],
                        'date' => $date ?? 0,
                        'description' => $bank_order['type'] ?? 0,
                        'from_account_id' => $exchange->id,
                        'document_number' => $bank_order['document_number'] ?? 0,
                        'recipient_company_name' => $bank_order['company_name_recipient'] ?? 0,
                        'recipient_bank_name' => $bank_order['bank_name_recipient'] ?? 0,
                        'recipient_bank_account' => $bank_order['bank_account_recipient'] ?? 0,
                        'recipient_inn' => $bank_order['inn_recipient'] ?? 0,
                        'recipient_kpp' => $bank_order['kpp_recipient'] ?? 0,
                        'recipient_bic' => $bank_order['bic_recipient'] ?? 0,
                        'recipient_correspondent_account' => $bank_order['correspondent_account_recipient'] ?? 0,
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
        Logger::log(print_r('setLoadedTransactions', true), 'tet');
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


    private function getBankAccountArr(): array
    {

        $select_bank_account = [];
        foreach ($this->payment_order as $entry) {
            $select_bank_account[] = $entry['bank_account'];
            $select_bank_account[] = $entry['bank_account_recipient'];
        }


        $remove_duplicates = array_unique($select_bank_account);


        return array_values($remove_duplicates);
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

    public function updateKnownLegalEntitiesTotals(): void
    {
        // bank_exchange всегда массив с ключами total_received, total_written_off, final_remainder, bank_account

        if (!empty($this->bank_exchange['bank_account'])) {
            foreach ($this->db_bank_accounts as $entity) {
                if ($entity->bank_account == $this->bank_exchange['bank_account']) {
                    $date = null;

                    if ($this->bank_exchange['end_date'] ?? false) {
                        $date = DateTime::createFromFormat('d.m.Y', $this->bank_exchange['end_date']);
                    }

                    $total_received =
                        ($entity->total_received ?? 0)
                        + floatval($this->bank_exchange['total_received'] ?? 0);

                    if ($date) {
                        LegalEntitiesLM::updateLegalEntities([
                            'total_received =' . $total_received,
                            'total_written_off =' . floatval($this->bank_exchange['total_written_off'] ?? 0),
                            'final_remainder =' . floatval($this->bank_exchange['final_remainder'] ?? 0),
                            'date_created =' . $date->format('Y-m-d'),
                        ], $entity->id);
                    } else {
                        LegalEntitiesLM::updateLegalEntities([
                            'total_received =' . $total_received,
                            'total_written_off =' . floatval($this->bank_exchange['total_written_off'] ?? 0),
                            'final_remainder =' . floatval($this->bank_exchange['final_remainder'] ?? 0)
                        ], $entity->id);
                    }
                }
            }
        }
    }
}