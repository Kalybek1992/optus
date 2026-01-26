<?php

namespace Source\Project\LogicManagers\DocumentLM;

use DateTime;
use Source\Base\Core\Logger;
use Source\Project\LogicManagers\LogicPdoModel\BankOrderLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientServicesLM;
use Source\Project\LogicManagers\LogicPdoModel\ClientsLM;
use Source\Project\LogicManagers\LogicPdoModel\CompanyFinancesLM;
use Source\Project\LogicManagers\LogicPdoModel\DebtsLM;
use Source\Project\LogicManagers\LogicPdoModel\EndOfDaySettlementLM;
use Source\Project\LogicManagers\LogicPdoModel\LegalEntitiesLM;
use Source\Project\LogicManagers\LogicPdoModel\StatementLogLM;
use Source\Project\LogicManagers\LogicPdoModel\SupplierBalanceLM;
use Source\Project\LogicManagers\LogicPdoModel\SuppliersLM;
use Source\Project\LogicManagers\LogicPdoModel\TransactionsLM;
use Source\Project\LogicManagers\LogicPdoModel\UploadedDocumentsLM;
use Source\Project\LogicManagers\LogicPdoModel\UploadedLogLM;



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

    public int $statement_log_id = 0;

    public int $goods_client_service = 0;

    public float $income_sum = 0;

    public float $expense_sum = 0;

    private array $statement_log_step = [];

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

        if (isset($this->expenses[0])) {
            $row = $this->expenses[0];
            $this->our_account = [
                'company_name' => $row['company_name'],
                'inn' => $row['inn'],
                'bank_name' => $row['bank_name'],
                'account' => $row['bank_account'],
                'our_account' => 1,
            ];
            $this->our_account_number = $row['bank_account'];
        }

        if (isset($this->income[0]) && !$this->our_account) {
            $row = $this->income[0];
            $this->our_account = [
                'company_name' => $row['company_name_recipient'],
                'inn' => $row['inn_recipient'],
                'bank_name' => $row['bank_name_recipient'],
                'account' => $row['bank_account_recipient'],
                'our_account' => 1,
            ];
            $this->our_account_number = $row['bank_account_recipient'];
        }

        if (isset($this->bank_order[0]) && !$this->our_account) {
            $row = $this->bank_order[0];
            $this->our_account = [
                'company_name' => $row['company_name'],
                'inn' => $row['inn'],
                'bank_name' => $row['bank_name'],
                'account' => $row['bank_account'],
                'our_account' => 1,
            ];
            $this->our_account_number = $row['bank_account_recipient'];
        }

        if (!isset($this->our_account)) {
            $this->result_document_processing = 'our_bank_is_not_listed';
            throw new LogicException('OR-аккаунт не определён ни через expenses, ни через income');
        }

        $this->stepStart();

        return $this;
    }

    /**
     * Записать наш аккаунт если его нету и получить ид
     */
    public function getOurAccounts(): static
    {
        $our_account = LegalEntitiesLM::getBankAccount($this->our_account_number);

        if (!$our_account) {
            $this->our_account['id'] = LegalEntitiesLM::getLegalEntitiesMaxId() + 1;
            LegalEntitiesLM::setNewLegalEntitie($this->our_account);

            $this->statement_log_step['add_or_new_account'] = $this->our_account['id'];
            $this->stepUpdate();
            $our_account = LegalEntitiesLM::getBankAccount($this->our_account_number);
        }

        foreach ($our_account as $account) {
            $this->our_account = [
                'id' => $account->id,
                'company_name' => $account->company_name,
                'account' => $account->account,
                'inn' => $account->inn,
                'bank_name' => $account->bank_name,
                'total_received' => $account->total_received,
                'total_written_off' => $account->total_written_off,
                'final_remainder' => $account->final_remainder,
                'date_created' => $account->date_created,
            ];
        }

        $this->statement_log_step['or_account'] = $this->our_account['id'];
        $this->stepUpdate();

        return $this;
    }


    /**
     * Получить известных счетов через счета
     */
    public function getFamousCompanies(): static
    {

        $account_implode = $this->getBankAccountImplode();

        if ($account_implode != []) {
            $this->db_bank_accounts = LegalEntitiesLM::getBankAccountsAndInn(
                $account_implode['bank_accounts'],
                $account_implode['inns'],
            );
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
            $key = $db_account->account . '|' . $db_account->inn;

            if ($this->new_bank_accounts[$key] ?? false) {
                unset($this->new_bank_accounts[$key]);
            }
        }

        if ($this->new_bank_accounts) {
            $this->setIdBankAccounts();
        }



        $account_implode = $this->getBankAccountImplode(false);
        if ($account_implode != []) {
            $this->db_bank_accounts = LegalEntitiesLM::getBankAccountsAndInn(
                $account_implode['bank_accounts'],
                $account_implode['inns'],
            );
        }


        $this->new_bank_accounts_count = count($this->new_bank_accounts);
        return $this;
    }

    /**
     * Обработка новых транзакций
     */
    public function setNewTransactions(): static
    {
        $transaction_max_id = TransactionsLM::getTranslationMaxId();
        $statement_log = [];
        foreach ($this->payment_order as $transaction) {
            $type = $this->our_account['account'] == $transaction['bank_account'] && $this->our_account['inn'] == $transaction['inn'] ? 'expense' : 'income';
            $date = $this->parseDateToMysqlFormat($transaction['date']);


            $description = $this->handleTransactionDescription($transaction);
            $from_account_id = $description['from_account_id'];
            $to_account_id = $description['to_account_id'];
            $percent = $description['percent'];
            $percent_income = $description['percent_income'];

            $type = $description['type'] !== false ? $description['type'] : $type;

            if (!$from_account_id || !$to_account_id) {
                $this->result_document_processing = 'error_in_calculating_the_payment_purpose';
                throw new LogicException('Error occurred while calculating the payment purpose');
            }

            $this->transactions[] = [
                'id' => $transaction_max_id += 1,
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

            $statement_log[] = $transaction_max_id;
        }


        if ($this->transactions) {
            TransactionsLM::insertNewTransactions($this->transactions);
            $this->statement_log_step['add_new_transactions'] = $statement_log;
            $this->stepUpdate();
            $this->transaction_pending = TransactionsLM::getTransactionsStatusPending();
        }

        return $this;
    }

    /**
     * Обработать назначение транзакции.
     */

    public function handleTransactionDescription($transaction): array
    {
        $from_account_id = false;
        $to_account_id = false;
        $percent = 0;
        $type = false;
        $date = $this->parseDateToMysqlFormat($transaction['date']);
        $unique_manager_dates = [];

        foreach ($this->db_bank_accounts as $account) {
            $between = [
                true => $account->id,
            ];
            /**
             * $sender    — Отправитель
             * $recipient — Получатель
             */

            $sender = $transaction['bank_account'] == $account->account && $transaction['inn'] == $account->inn;
            $recipient = $transaction['bank_account_recipient'] == $account->account && $transaction['inn_recipient'] == $account->inn;
            $from_account_id = $between[$sender] ?? $from_account_id;
            $to_account_id = $between[$recipient] ?? $to_account_id;

            $supplier_id = $account->supplier_id ?? false;
            $client_service_id = $account->client_service_id ?? false;
            $client_id = $account->client_id ?? false;

            if (($sender || $recipient) && $percent > 0) {
                $percent = $account->percent;
            }

            if ($sender && $supplier_id && !$client_service_id) {
                $type = 'return_supplier';
            }

            if ($recipient && $client_id) {
                $type = 'return';
            }

            if ($recipient && $client_service_id) {
                $type = 'return_client_services';
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

        return [
            'from_account_id' => $from_account_id,
            'to_account_id' => $to_account_id,
            'type' => $type,
            'percent' => $percent,
            'percent_income' => $percent_income,
        ];
    }

    /**
     * Возврат денег клиенту если есть долги
     */

    public function closeClientsDebt(): static
    {
        $close_clients_debt = [];

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

                    $close_clients_debt[] = $transaction->id;

                    $this->customer_client_returns_count++;
//                    Logger::log(
//                        'closeClientsDebt = amount ' . print_r($transaction->amount, true),
//                        'TransactionsProcess'
//                    );
                }
            }
        }

        if ($close_clients_debt) {
            $this->statement_log_step['close_clients_debt'] = $close_clients_debt;
            $this->stepUpdate();
        }


        return $this;
    }

    /**
     * Возврат денег клиент услуга
     */

    public function closeClientServicesDebt(): static
    {
        $close_clients_debt = [];

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

                    $close_clients_debt[] = $transaction->id;
                }
            }
        }

        if ($close_clients_debt) {
            $this->statement_log_step['close_client_services_debt'] = $close_clients_debt;
            $this->stepUpdate();
        }

        return $this;
    }

    /**
     * Возврат денег поставшик
     */

    public function closeSupplierDebt(): static
    {
        $close_supplier_debt = [];

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

                    DebtsLM::payOffCompaniesDebt(
                        $supplier['legal_id'],
                        $result,
                        $transaction->id,
                    );

                    $this->customer_supplier_returns_count++;
                    $close_supplier_debt[] = $transaction->id;
                }
            }
        }

        if ($close_supplier_debt) {
            $this->statement_log_step['close_supplier_debt'] = $close_supplier_debt;
            $this->stepUpdate();
        }


        return $this;
    }

    /**
     * Обновить баланс существующих поставшиков
     */

    public function updateBalanceSupplier(): static
    {
        $insert_new_balance = [];
        $update_balance_supplier = [];

        foreach ($this->transaction_pending as $transaction) {
            if ($transaction->recipient_supplier_id && $transaction->type == 'expense') {
                $supplier_balance = SupplierBalanceLM::getSupplierBalance(
                    $transaction->recipient_inn,
                    $transaction->sender_inn
                );

                if (!$supplier_balance) {
                    $key = $transaction->recipient_inn . '_' . $transaction->sender_inn;

                    if (!isset($insert_new_balance[$key])) {
                        $insert_new_balance[$key] = [
                            'recipient_inn' => $transaction->recipient_inn,
                            'sender_inn' => $transaction->sender_inn,
                            'amount' => $transaction->amount,
                        ];

                    } else {
                        $insert_new_balance[$key]['amount'] += $transaction->amount;
                    }
                }

                if ($supplier_balance) {
                    $new_balance = $supplier_balance->amount + $transaction->amount;
                    try {
                        SupplierBalanceLM::updateSupplierBalance(
                            [
                                'amount = ' . $new_balance,
                            ],
                            $supplier_balance->id,
                        );

                        $update_balance_supplier[] = [
                            'id' => $supplier_balance->id,
                            'amount' => $new_balance,
                        ];
                    } catch (Throwable $e) {
                        if ($update_balance_supplier) {
                            $this->statement_log_step['update_balance_supplier'] = $update_balance_supplier;
                            $this->stepUpdate();
                        }
                        throw new RuntimeException('error update supplier balance');
                    }
                }
            }
        }

        if ($insert_new_balance) {
            $insert_new_balance = array_values($insert_new_balance);
            SupplierBalanceLM::setNewSupplierBalance($insert_new_balance);
            $this->statement_log_step['insert_new_balance'] = $insert_new_balance;
            $this->stepUpdate();
        }

        if ($update_balance_supplier) {
            $this->statement_log_step['update_balance_supplier'] = $update_balance_supplier;
            $this->stepUpdate();
        }

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
        $expenditure_on_goods = [];

        foreach ($this->expenditure_on_goods as $good) {
            $expenditure_on_goods[] = $good['transaction_id'];
        }

        if ($this->expenditure_on_goods) {
            DebtsLM::setNewDebts($this->expenditure_on_goods);
            $this->statement_log_step['expenditure_on_goods'] = $expenditure_on_goods;
            $this->stepUpdate();
        }

        if ($this->date_update_report_supplier) {
            EndOfDaySettlementLM::updateEndOfDayTransactions($this->date_update_report_supplier);
            $this->statement_log_step['end_of_day_settlement'] = $this->date_update_report_supplier;
            $this->stepUpdate();
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

                $this->goods_supplier++;
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
     * Обработка новых получателей
     */
    private function getNewExpensesBankAccounts(): void
    {
        $sections = $this->expenses;

        foreach ($sections as $section) {
            $key = $section['bank_account_recipient'] . '|' . $section['inn_recipient'];

            $this->new_bank_accounts[$key] = [
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

        foreach ($sections as $section) {
            $key = $section['bank_account'] . '|' . $section['inn'];

            $this->new_bank_accounts[$key] = [
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
        $statement = [];

        foreach ($this->new_bank_accounts as $key => $value) {
            $legal_entities_max += 1;

            $legal_entities_insert[] = [
                'id' => $legal_entities_max,
                'inn' => $value['inn'],
                'bank_name' => $value['bank_name'],
                'company_name' => $value['company_name'],
                'account' => $value['account'],
            ];

            $statement[] = $legal_entities_max;
        }

        $this->statement_log_step['add_new_accounts'] = $statement;
        $this->stepUpdate();
        $this->new_bank_accounts = $legal_entities_insert;
        LegalEntitiesLM::setNewLegalEntitie($this->new_bank_accounts);
    }


    /**
     * Обработка банковских ордеров
     */
    public function setNewTransactionsBankOrder(): static
    {
        $id = $this->our_account['id'];
        $statement = [];

        if ($id && $this->bank_order) {

            $order_max_id = BankOrderLM::getBankOrderMaxId();
            foreach ($this->bank_order as $key => $bank_order) {
                $transaction_type = $this->detectTransactionType($bank_order['type']);
                $date = $this->parseDateToMysqlFormat($bank_order['date']);

                if ($bank_order['balance']) {
                    $order_max_id += 1;
                    $this->new_bank_order[] = [
                        'id' => $order_max_id,
                        'type' => $transaction_type,
                        'amount' => $bank_order['balance'],
                        'date' => $date ?? 0,
                        'description' => $bank_order['type'] ?? 0,
                        'from_account_id' => $id,
                        'document_number' => $bank_order['document_number'] ?? 0,
                        'recipient_company_name' => $bank_order['company_name_recipient'] ?? 0,
                        'recipient_bank_name' => $bank_order['bank_name_recipient'] ?? 0,
                    ];
                    $statement[] = $order_max_id;
                } else {
                    unset($this->bank_order[$key]);
                }
            }
        }

        if ($this->new_bank_order) {
            BankOrderLM::insertNewBankOrder($this->new_bank_order);
            $this->statement_log_step['new_bank_orders'] = $statement;
            $this->stepUpdate();
        }

        return $this;
    }

    /**
     * Фиксирую загруженных транзакций чтобы не попало повторы
     */
    public function setLoadedTransactions(): static
    {
        $data = array_merge(
            $this->bank_order,
            $this->payment_order,
        );

        $loaded_transactions = [];
        $loaded_ids = [];
        $max_id = UploadedDocumentsLM::getUploadedMaxId();

        $time = time();
        foreach ($data as $bank_order) {
            $loaded_transactions[] = [
                'id' => $max_id += 1,
                'inn' => $bank_order['inn'],
                'document_number' => $bank_order['document_number'],
                'amount' => $bank_order['balance'],
                'recipient_inn' => $bank_order['inn_recipient'],
                'bank_account' => $bank_order['bank_account'],
                'recipient_bank_account' => $bank_order['bank_account_recipient'] ?? 0,
                'statement_date' => $bank_order['date'] ?? 0,
                'date' => $time
            ];

            $loaded_ids[] = $max_id;
        }

        UploadedDocumentsLM::insertNewLoadedTransactions($loaded_transactions);
        $this->statement_log_step['uploaded_documents'] = $loaded_ids;
        $this->stepUpdate();

        return $this;
    }

    /**
     * Возвращаем все инн из выписки с помощью employed для запроса базу данных
     */

    private function getBankAccountImplode(bool $our_account_no = true): array
    {
        $unique_pairs = [];

        foreach ($this->payment_order as $entry) {
            if ($our_account_no) {
                if ($entry['bank_account'] != $this->our_account_number) {
                    $key = $entry['bank_account'] . '|' . $entry['inn'];
                    $unique_pairs[$key] = [
                        'account' => $entry['bank_account'],
                        'inn' => $entry['inn'],
                    ];
                }
            } else {
                $key = $entry['bank_account'] . '|' . $entry['inn'];
                $unique_pairs[$key] = [
                    'account' => $entry['bank_account'],
                    'inn' => $entry['inn'],
                ];
            }

            if ($our_account_no) {
                if ($entry['bank_account_recipient'] != $this->our_account_number) {
                    $key = $entry['bank_account_recipient'] . '|' . $entry['inn_recipient'];
                    $unique_pairs[$key] = [
                        'account' => $entry['bank_account_recipient'],
                        'inn' => $entry['inn_recipient'],
                    ];
                }
            } else {
                $key = $entry['bank_account_recipient'] . '|' . $entry['inn_recipient'];
                $unique_pairs[$key] = [
                    'account' => $entry['bank_account_recipient'],
                    'inn' => $entry['inn_recipient'],
                ];
            }
        }

        if (!$unique_pairs) {
            return [];
        }


        $bank_accounts = array_map(fn($v) => $v['account'], $unique_pairs);
        $inns = array_map(fn($v) => $v['inn'], $unique_pairs);

        $bank_accounts_sql = implode(', ', array_map(fn($v) => "'$v'", $bank_accounts));
        $inns_sql = implode(', ', array_map(fn($v) => "'$v'", $inns));

        return [
            'bank_accounts' => $bank_accounts_sql,
            'inns' => $inns_sql,
        ];
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
     * Данные ощущения остатки и так далее
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

        $this->statement_log_step['update_known_legal_entities_totals'] = [
            'total_received' => $this->our_account['total_received'] ?? 0,
            'total_written_off' => $this->our_account['total_written_off'] ?? 0,
            'final_remainder' => $this->our_account['final_remainder'] ?? 0,
            'date_created' => $this->our_account['date_created'] ?? 0,
        ];
        $this->stepUpdate();
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
        $max_id = UploadedLogLM::getAccountsUploadedMaxid() + 1;


        $insert_uploaded_log = [
            'id' => $max_id,
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

        $this->statement_log_step['last_statement_download'] = $max_id;
        $this->stepUpdate();

        return $this;
    }

    /**
     * Начинаем отслеживать шаги для отката
     */
    public function stepStart(): void
    {
        $this->statement_log_id = StatementLogLM::getStatementLogMaxId();

        StatementLogLM::setNewStatementLog([
            'id' => $this->statement_log_id,
        ]);
    }

    /**
     * Каждый шаг запоминаем обновляя шаги
     */
    public function stepUpdate(): static
    {
        $steps_string = serialize($this->statement_log_step);

        StatementLogLM::updateStatementLog([
            'steps = "' . $steps_string . '"'
        ], $this->statement_log_id);

        return $this;
    }

    /**
     * Если всё прошло хорошо ставим статус загрузки хорошо
     * Если нужно откатить можно откатить с помощью контроллера RollbackController|rollbackErrorUpload
     */
    public function stepUpdateStatus(): static
    {
        StatementLogLM::updateStatementLog([
            'status = ' . 1
        ], $this->statement_log_id);

        return $this;
    }

}