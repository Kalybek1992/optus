<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUploadedDocumentsTable extends AbstractMigration
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
        if ($this->hasTable('uploaded_documents')) {
            $this->table('uploaded_documents')->drop()->save();
        }

        $table = $this->table('uploaded_documents', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('inn', 'string', ['limit' => 12, 'null' => false])
            ->addColumn('document_number', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('date', 'integer', ['null' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('uploaded_documents')->drop()->save();
    }
}
