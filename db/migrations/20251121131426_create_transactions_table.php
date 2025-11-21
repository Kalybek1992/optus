<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTransactionsTable extends AbstractMigration
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
        if ($this->hasTable('transactions')) {
            $this->table('transactions')->drop()->save();
        }

        $table = $this->table('transactions', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('type', 'enum', ['values' => ['income','expense','return','bank_order','internal_transfer','courier_expense','courier_income','return_client_services'], 'null' => false])
            ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false])
            ->addColumn('percent', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 0.00])
            ->addColumn('interest_income', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0.00])
            ->addColumn('date', 'datetime', ['null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('from_account_id', 'integer', ['null' => true])
            ->addColumn('to_account_id', 'integer', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['processed','pending'], 'default' => 'pending'])
            ->addColumn('date_received', 'date', ['null' => true])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('transactions')->drop()->save();
    }
}
