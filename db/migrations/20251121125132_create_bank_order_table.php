<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBankOrderTable extends AbstractMigration
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
        if ($this->hasTable('bank_order')) {
            $this->table('bank_order')->drop()->save();
        }

        $table = $this->table('bank_order');
        $table->addColumn('type', 'enum', ['values' => ['commission','withdrawal','unknown','expense','sending_by_courier'], 'null' => false])
            ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => false])
            ->addColumn('date', 'datetime', ['null' => false])
            ->addColumn('from_account_id', 'integer', ['null' => true])
            ->addColumn('transaction_id', 'integer', ['null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('document_number', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('recipient_company_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('recipient_bank_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('recipient_bank_account', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('recipient_inn', 'string', ['limit' => 12, 'null' => true])
            ->addColumn('recipient_kpp', 'string', ['limit' => 9, 'null' => true])
            ->addColumn('recipient_bic', 'string', ['limit' => 9, 'null' => true])
            ->addColumn('recipient_correspondent_account', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['processed','pending'], 'default' => 'pending'])
            ->addColumn('auto_detection', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('return_account', 'boolean', ['default' => false, 'null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('bank_order')->drop()->save();
    }
}
