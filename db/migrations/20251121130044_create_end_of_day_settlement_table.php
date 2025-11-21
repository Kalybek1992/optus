<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEndOfDaySettlementTable extends AbstractMigration
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
        if ($this->hasTable('end_of_day_settlement')) {
            $this->table('end_of_day_settlement')->drop()->save();
        }

        $table = $this->table('end_of_day_settlement', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('manager_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('amount', 'float', ['default' => 0, 'null' => false])
            ->addColumn('scenario', 'integer', ['limit' => 3, 'signed' => false, 'null' => false])
            ->addColumn('date', 'date', ['null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('end_of_day_settlement')->drop()->save();
    }
}
