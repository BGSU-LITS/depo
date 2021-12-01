<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

class AddStateFieldToItemTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('item')
            ->addColumn('state', 'enum', ['null' => true, 'values' => [
                'accession',
                'deaccession',
                'update',
            ]])
            ->addForeignKey(
                ['catalog_id', 'state'],
                'state',
                ['catalog_id', 'state'],
                ForeignKey::RESTRICT
            )
            ->addIndex(['catalog_id', 'location', 'newest', 'state'])
            ->save();
    }

    protected function down(): void
    {
        $this->table('item')
            ->dropIndex(['catalog_id', 'location', 'newest', 'state'])
            ->dropForeignKey(['catalog_id', 'state'])
            ->dropColumn('state')
            ->save();
    }
}
