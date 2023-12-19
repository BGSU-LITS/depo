<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class DropTotalFieldFromTrayTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('tray')
            ->dropColumn('total')
            ->save();
    }

    /** @throws InvalidArgumentValueException */
    protected function down(): void
    {
        $this->table('tray')
            ->addColumn('total', 'integer', [
                'null' => true,
                'after' => 'per_shelf',
            ])
            ->save();
    }
}
