<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCategoryRelationsTable extends AbstractMigration
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
        if ($this->hasTable('category_relations')) {
            $this->table('category_relations')->drop()->save();
        }

        $table = $this->table('category_relations');
        $table->addColumn('parent_id', 'integer', ['null' => false])
            ->addColumn('child_id', 'integer', ['null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('category_relations')->drop()->save();
    }
}
