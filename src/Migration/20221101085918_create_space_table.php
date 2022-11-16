<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

class CreateSpaceTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    protected function up(): void
    {
        $primary = ['module', 'side', 'section', 'shelf', 'updated'];

        $this->table('space', $primary)
            ->addColumn('module', 'string', ['length' => 1])
            ->addColumn('side', 'string', ['length' => 2])
            ->addColumn('section', 'string', ['length' => 2])
            ->addColumn('shelf', 'string', ['length' => 2])
            ->addColumn('updated', 'date')
            ->addColumn('tray_id', 'string', ['length' => 1])
            ->addColumn('used', 'integer')
            ->addForeignKey('tray_id', 'tray', 'id', ForeignKey::RESTRICT)
            ->create();
    }

    protected function down(): void
    {
        $this->table('space')->drop();
    }
}
