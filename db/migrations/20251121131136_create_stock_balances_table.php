<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateStockBalancesTable extends AbstractMigration
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
        if ($this->hasTable('stock_balances')) {
            $this->table('stock_balances')->drop()->save();
        }

        $table = $this->table('stock_balances', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('balance', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false, 'default' => 0.00])
            ->addColumn('updated_date', 'date', ['null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('stock_balances')->drop()->save();
    }
}
