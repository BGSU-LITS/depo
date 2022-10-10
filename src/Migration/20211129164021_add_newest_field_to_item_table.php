<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class AddNewestFieldToItemTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('item')->addColumn('newest', 'boolean')->save();
    }

    protected function down(): void
    {
        $this->table('item')->dropColumn('newest')->save();
    }
}
