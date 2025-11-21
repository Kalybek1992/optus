<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateClientsTable extends AbstractMigration
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
        if ($this->hasTable('clients')) {
            $this->table('clients')->drop()->save();
        }

        $table = $this->table('clients');
        $table->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('percentage', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 0.00])
            ->addColumn('supplier_id', 'integer', ['null' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('clients')->drop()->save();
    }
}
