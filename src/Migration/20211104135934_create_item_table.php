<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class CreateItemTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('item')
            ->addColumn('catalog_id', 'string', ['length' => 8])
            ->addColumn('record', 'string', ['length' => 9])
            ->addColumn('created', 'date')
            ->addColumn('updated', 'date')
            ->addColumn('revision', 'integer')
            ->addColumn('location', 'string', ['length' => 5])
            ->addColumn('status', 'string', ['length' => 3, 'null' => true])
            ->addColumn('message', 'string', ['length' => 1, 'null' => true])
            ->addIndex(
                ['catalog_id', 'record', 'created', 'updated'],
                Index::TYPE_UNIQUE
            )
            ->addForeignKey(
                'catalog_id',
                'catalog',
                'id',
                ForeignKey::RESTRICT
            )
            ->create();
    }

    protected function down(): void
    {
        $this->table('item')->drop();
    }
}
