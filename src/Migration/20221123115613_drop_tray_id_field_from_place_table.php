<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

class DropTrayIdFieldFromPlaceTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('place')
            ->dropForeignKey('tray_id')
            ->dropColumn('tray_id')
            ->save();
    }

    /** @throws InvalidArgumentValueException */
    protected function down(): void
    {
        $this->table('place')
            ->addColumn('tray_id', 'string', [
                'null' => true,
                'after' => 'item_id',
            ])
            ->addForeignKey('tray_id', 'tray', 'id', ForeignKey::RESTRICT)
            ->save();
    }
}
