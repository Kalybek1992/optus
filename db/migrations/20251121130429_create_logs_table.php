<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLogsTable extends AbstractMigration
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
        if ($this->hasTable('logs')) {
            $this->table('logs')->drop()->save();
        }

        $table = $this->table('logs', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('action', 'enum', ['values' => ['create','update'], 'null' => false])
            ->addColumn('entity_type', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('old_value', 'text', ['null' => true])
            ->addColumn('new_value', 'text', ['null' => true])
            ->addColumn('timestamp', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('logs')->drop()->save();
    }
}
