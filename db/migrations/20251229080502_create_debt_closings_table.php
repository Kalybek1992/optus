<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDebtClosingsTable extends AbstractMigration
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
        if ($this->hasTable('debt_closings')) {
            $this->table('debt_closings')->drop()->save();
        }

        $table = $this->table('debt_closings', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table
            ->addColumn('id', 'integer', [
                'identity' => true,
                'signed' => true,
                'null' => false,
            ])
            ->addColumn('debt_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('transaction_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('amount', 'decimal', [
                'precision' => 15,
                'scale' => 2,
                'null' => false,
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('debt_closings')) {
            $this->table('debt_closings')->drop()->save();
        }
    }

}
