<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDebtsTable extends AbstractMigration
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
        if ($this->hasTable('debts')) {
            $this->table('debts')->drop()->save();
        }

        $table = $this->table('debts', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table
            ->addColumn('id', 'integer', [
                'identity' => true,
                'signed' => true,
                'null' => false,
            ])
            ->addColumn('from_account_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('to_account_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('transaction_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('writing_transaction_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('type_of_debt', 'enum', [
                'values' => [
                    'supplier_goods',
                    'client_goods',
                    'client_services',
                    'сlient_debt',
                    'сlient_debt_supplier',
                    'supplier_debt_сlient',
                    'client_services_debt',
                    'supplier_debt',
                ],
                'null' => false,
            ])
            ->addColumn('amount', 'decimal', [
                'precision' => 15,
                'scale' => 2,
                'null' => false,
            ])
            ->addColumn('date', 'date', [
                'null' => true,
            ])
            ->addColumn('status', 'enum', [
                'values' => ['active', 'paid', 'offs_confirmation'],
                'default' => 'active',
                'null' => true,
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('debts')) {
            $this->table('debts')->drop()->save();
        }
    }

}
