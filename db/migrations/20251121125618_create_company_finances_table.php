<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCompanyFinancesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        if ($this->hasTable('company_finances')) {
            $this->table('company_finances')->drop()->save();
        }

        $table = $this->table('company_finances');
        $table
            ->addColumn('order_id', 'integer', ['null' => true])
            ->addColumn('transaction_id', 'integer', ['null' => true])
            ->addColumn('card_id', 'integer', ['null' => true])
            ->addColumn('courier_id', 'integer', ['null' => true])
            ->addColumn('client_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('supplier_id', 'integer', ['null' => true])
            ->addColumn('manager_id', 'integer', ['null' => true])
            ->addColumn('category', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('comments', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('issue_date', 'date', ['null' => true])

            ->addColumn('type', 'enum', [
                'values' => [
                    'stock_balances','courier_balances','expense','expense_stock_balances',
                    'courier_expense','return_debit_courier','courier_income_other',
                    'shipping_manager','shipping_return','debt_repayment_client_supplier',
                    'debt_repayment_сompanies_supplier','expense_stock_balances_supplier','moved_cash'
                ],
                'null' => false
            ])
            ->addColumn('return_type', 'enum', [
                'values' => ['cash','wheel','return_wheel'],
                'null' => true
            ])
            ->addColumn('status', 'enum', [
                'values' => ['processed','pending','confirm_courier','confirm_admin'],
                'default' => 'pending',
                'null' => false
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('company_finances')->drop()->save();
    }
}
