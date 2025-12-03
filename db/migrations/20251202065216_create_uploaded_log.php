<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUploadedLog extends AbstractMigration
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
        if ($this->hasTable('uploaded_log')) {
            $this->table('uploaded_log')->drop()->save();
        }

        $table = $this->table('uploaded_log', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb3_general_ci',
        ]);

        $table
            ->addColumn('id', 'integer', [
                'identity' => true,
            ])
            ->addColumn('legal_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('date', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addColumn('transactions_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('new_accounts_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('bank_accounts_updated', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('file_name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('uploaded_log')) {
            $this->table('uploaded_log')->drop()->save();
        }
    }

}
