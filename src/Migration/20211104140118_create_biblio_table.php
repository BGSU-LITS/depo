<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class CreateBiblioTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('biblio', ['item_id', 'biblio'])
            ->addColumn('item_id', 'integer')
            ->addColumn('biblio', 'string', ['length' => 9])
            ->addForeignKey('item_id', 'item', 'id', ForeignKey::CASCADE)
            ->create();
    }

    protected function down(): void
    {
        $this->table('biblio')->drop();
    }
}
