<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

class CreateTrayTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $this->table('tray', 'id')
            ->addColumn('id', 'string', ['length' => 1])
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('length', 'decimal', ['decimals' => 3, 'null' => true])
            ->addColumn('width', 'decimal', ['decimals' => 3, 'null' => true])
            ->addColumn('height', 'decimal', ['decimals' => 3, 'null' => true])
            ->addColumn('per_shelf', 'integer', ['null' => true])
            ->addColumn('total', 'integer', ['null' => true])
            ->addColumn('color', 'string', ['length' => 255, 'null' => true])
            ->create();
    }

    protected function down(): void
    {
        $this->table('tray')->drop();
    }
}
