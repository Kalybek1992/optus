<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateShopTable extends AbstractMigration
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
        if ($this->hasTable('shop')) {
            $this->table('shop')->drop()->save();
        }

        $table = $this->table('shop', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('balance', 'float', ['null' => false, 'default' => 0])
            ->addColumn('stock_balance', 'float', ['null' => false, 'default' => 0])
            ->create();
    }

    public function down(): void
    {
        $this->table('shop')->drop()->save();
    }
}
