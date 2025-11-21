<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBankAccountsTable extends AbstractMigration
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
        if ($this->hasTable('bank_accounts')) {
            $this->table('bank_accounts')->drop()->save();
        }

        $table = $this->table('bank_accounts');
        $table->addColumn('legal_entity_id', 'integer', ['null' => false])
            ->addColumn('balance', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0.00])
            ->addColumn('stock_balance', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0.00])
            ->addIndex('legal_entity_id')
            ->create();
    }

    public function down(): void
    {
        $this->table('bank_accounts')->drop()->save();
    }
}
