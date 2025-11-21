<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateClientServicesTable extends AbstractMigration
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
        if ($this->hasTable('client_services')) {
            $this->table('client_services')->drop()->save();
        }

        $table = $this->table('client_services');
        $table->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('supplier_id', 'integer', ['null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('client_services')->drop()->save();
    }
}
