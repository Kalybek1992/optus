<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTaskPlannerTable extends AbstractMigration
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
        if ($this->hasTable('task_planner')) {
            $this->table('task_planner')->drop()->save();
        }

        $table = $this->table('task_planner', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('legal_id', 'integer', ['null' => true])
            ->addColumn('plan_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('comment', 'text', ['null' => true])
            ->addColumn('amount', 'float', ['null' => true])
            ->addColumn('paid', 'float', ['null' => false, 'default' => 0])
            ->addColumn('repeat_type', 'enum', ['values' => ['none','weekly','monthly'], 'default' => 'none'])
            ->addColumn('day', 'integer', ['limit' => 4, 'null' => false])
            ->addColumn('month', 'integer', ['limit' => 4, 'null' => false])
            ->addColumn('weekday', 'integer', ['limit' => 1, 'null' => false, 'default' => 0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->create();
    }

    public function down(): void
    {
        $this->table('task_planner')->drop()->save();
    }
}
