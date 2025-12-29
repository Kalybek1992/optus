<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLegalEntitiesTable extends AbstractMigration
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
        if ($this->hasTable('legal_entities')) {
            $this->table('legal_entities')->drop()->save();
        }

        $table = $this->table('legal_entities', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table
            ->addColumn('id', 'integer', [
                'identity' => true,
                'signed' => true,
                'null' => false,
            ])
            ->addColumn('our_account', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->addColumn('supplier_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('client_services', 'boolean', [
                'default' => false,
                'null' => true,
            ])
            ->addColumn('client_service_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('manager_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('supplier_client_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('client_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('shop_id', 'integer', [
                'null' => true,
            ])
            ->addColumn('percent', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'default' => 0.00,
                'null' => true,
            ])
            ->addColumn('account', 'string', [
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('inn', 'string', [
                'limit' => 12,
                'null' => true,
            ])
            ->addColumn('bank_name', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('company_name', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('total_received', 'float', [
                'null' => true,
            ])
            ->addColumn('total_written_off', 'float', [
                'null' => true,
            ])
            ->addColumn('final_remainder', 'float', [
                'null' => true,
            ])
            ->addColumn('date_created', 'date', [
                'null' => true,
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('legal_entities')) {
            $this->table('legal_entities')->drop()->save();
        }
    }

}
