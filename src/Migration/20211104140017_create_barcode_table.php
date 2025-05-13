<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class CreateBarcodeTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function up(): void
    {
        $this->table('barcode', ['item_id', 'barcode'])
            ->addColumn('item_id', 'integer')
            ->addColumn('barcode', 'string', ['length' => 255])
            ->addForeignKey('item_id', 'item', 'id', ForeignKey::CASCADE)
            ->create();
    }

    #[\Override]
    protected function down(): void
    {
        $this->table('barcode')->drop();
    }
}
