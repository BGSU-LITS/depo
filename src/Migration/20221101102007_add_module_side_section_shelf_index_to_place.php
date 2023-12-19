<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class AddModuleSideSectionShelfIndexToPlace extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('place')
            ->addIndex(['module', 'side', 'section', 'shelf'])
            ->save();
    }

    protected function down(): void
    {
        $this->table('place')
            ->dropIndex(['module', 'side', 'section', 'shelf'])
            ->save();
    }
}
