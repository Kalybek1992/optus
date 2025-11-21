<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCreditCardsTable extends AbstractMigration
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
        if ($this->hasTable('credit_cards')) {
            $this->table('credit_cards')->drop()->save();
        }

        $table = $this->table('credit_cards');
        $table->addColumn('card_number', 'char', ['length' => 16, 'null' => false])
            ->addColumn('legal_id', 'integer', ['null' => true])
            ->addColumn('balance', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0.00])
            ->addColumn('date', 'date', ['default' => 'CURRENT_DATE', 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['active','inactive','blocked'], 'default' => 'active', 'null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('credit_cards')->drop()->save();
    }
}
