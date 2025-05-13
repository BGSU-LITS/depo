<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class AddUpdatedIndexToItemTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function up(): void
    {
        $this->table('item')->addIndex('updated')->save();
    }

    #[\Override]
    protected function down(): void
    {
        $this->table('item')->dropIndex('updated')->save();
    }
}
