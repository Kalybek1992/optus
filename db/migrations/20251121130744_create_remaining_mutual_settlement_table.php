<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRemainingMutualSettlementTable extends AbstractMigration
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
        if ($this->hasTable('remaining_mutual_settlement')) {
            $this->table('remaining_mutual_settlement')->drop()->save();
        }

        $table = $this->table('remaining_mutual_settlement', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('supplier_id', 'integer', ['null' => false])
            ->addColumn('supplier_goods', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false])
            ->addColumn('client_services', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false])
            ->addColumn('date', 'date', ['null' => false])
            ->addColumn('status', 'enum', ['values' => ['pending','processed'], 'default' => 'pending'])
            ->create();
    }

    public function down(): void
    {
        $this->table('remaining_mutual_settlement')->drop()->save();
    }
}
