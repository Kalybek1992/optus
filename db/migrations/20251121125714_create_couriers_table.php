<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCouriersTable extends AbstractMigration
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
        if ($this->hasTable('couriers')) {
            $this->table('couriers')->drop()->save();
        }

        $table = $this->table('couriers');
        $table->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('current_balance', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0.00])
            ->addColumn('last_update', 'datetime', ['null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('couriers')->drop()->save();
    }
}
