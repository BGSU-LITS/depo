<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class AddLevelFieldToTrayTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function up(): void
    {
        $this->table('tray')
            ->addColumn('level', 'enum', [
                'null' => true,
                'values' => ['tray', 'item'],
                'after' => 'per_shelf',
            ])
            ->save();
    }

    #[\Override]
    protected function down(): void
    {
        $this->table('tray')
            ->dropColumn('level')
            ->save();
    }
}
