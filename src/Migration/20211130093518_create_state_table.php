<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

class CreateStateTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('state', ['catalog_id', 'state'])
            ->addColumn('catalog_id', 'string', ['length' => 8])
            ->addColumn('state', 'enum', ['values' => [
                'accession',
                'deaccession',
                'update',
            ]])
            ->addColumn('field', 'enum', ['values' => [
                'message',
                'status',
            ]])
            ->addColumn('value', 'string', ['length' => 1])
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
        $this->table('state')->drop();
    }
}
