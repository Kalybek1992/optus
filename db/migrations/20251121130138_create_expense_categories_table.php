<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateExpenseCategoriesTable extends AbstractMigration
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
        if ($this->hasTable('expense_categories')) {
            $this->table('expense_categories')->drop()->save();
        }

        $table = $this->table('expense_categories', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('is_parsed', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('supplier_id', 'integer', ['null' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('expense_categories')->drop()->save();
    }
}
