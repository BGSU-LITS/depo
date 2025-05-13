<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class AddColorFieldsToCatalogTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function up(): void
    {
        $this->table('catalog')
            ->addColumn('color1', 'string', ['length' => 255, 'null' => true])
            ->addColumn('color2', 'string', ['length' => 255, 'null' => true])
            ->save();
    }

    #[\Override]
    protected function down(): void
    {
        $this->table('catalog')
            ->dropColumn('color1')
            ->dropColumn('color2')
            ->save();
    }
}
