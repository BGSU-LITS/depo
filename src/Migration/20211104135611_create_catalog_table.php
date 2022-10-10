<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class CreateCatalogTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('catalog', 'id')
            ->addColumn('id', 'string', ['length' => 8])
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('url', 'string', ['length' => 255])
            ->create();
    }

    protected function down(): void
    {
        $this->table('catalog')->drop();
    }
}
