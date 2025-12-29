<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUploadedLogTable extends AbstractMigration
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
        if ($this->hasTable('uploaded_log')) {
            $this->table('uploaded_log')->drop()->save();
        }

        $table = $this->table('uploaded_log', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table
            ->addColumn('id', 'integer', [
                'identity' => true,
                'signed' => true,
                'null' => false,
            ])
            ->addColumn('legal_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('transactions_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('bank_order_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('new_accounts_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('client_returns_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('supplier_returns_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('client_services_returns_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('goods_supplier', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('goods_client', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('goods_client_service', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('expenses', 'float', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('income', 'float', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('date', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('uploaded_log')) {
            $this->table('uploaded_log')->drop()->save();
        }
    }

}
