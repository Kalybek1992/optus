<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSuppliersTable extends AbstractMigration
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
        if ($this->hasTable('suppliers')) {
            $this->table('suppliers')->drop()->save();
        }

        $table = $this->table('suppliers', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('percentage', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true, 'default' => 0.00])
            ->addColumn('balance', 'float', ['null' => false, 'default' => 0])
            ->addColumn('stock_balance', 'float', ['null' => false, 'default' => 0])
            ->create();
    }

    public function down(): void
    {
        $this->table('suppliers')->drop()->save();
    }
}
