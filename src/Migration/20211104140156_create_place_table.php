<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class CreatePlaceTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('place', 'item_id')
            ->addColumn('item_id', 'integer')
            ->addColumn('tray_id', 'string', ['length' => 1, 'null' => true])
            ->addColumn('module', 'string', ['length' => 1])
            ->addColumn('side', 'string', ['length' => 2])
            ->addColumn('section', 'string', ['length' => 2])
            ->addColumn('shelf', 'string', ['length' => 2])
            ->addColumn('tray', 'string', ['length' => 2])
            ->addColumn('item', 'string', ['length' => 2, 'null' => true])
            ->addForeignKey('item_id', 'item', 'id', ForeignKey::CASCADE)
            ->addForeignKey('tray_id', 'tray', 'id', ForeignKey::RESTRICT)
            ->create();
    }

    protected function down(): void
    {
        $this->table('place')->drop();
    }
}
