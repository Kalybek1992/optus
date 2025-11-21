<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMutualSettlementTable extends AbstractMigration
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
        if ($this->hasTable('mutual_settlement')) {
            $this->table('mutual_settlement')->drop()->save();
        }

        $table = $this->table('mutual_settlement', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('debt_id', 'integer', ['null' => false])
            ->addColumn('id_mutual_settlement', 'integer', ['null' => true])
            ->addColumn('repaid', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0.00])
            ->addColumn('remainder', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0.00, 'null' => true])
            ->addColumn('date', 'date', ['default' => 'CURRENT_DATE', 'null' => false])
            ->addColumn('repayment_type', 'enum', ['values' => ['supplier_goods','client_services'], 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['pending','processed'], 'default' => 'pending'])
            ->create();
    }

    public function down(): void
    {
        $this->table('mutual_settlement')->drop()->save();
    }
}
