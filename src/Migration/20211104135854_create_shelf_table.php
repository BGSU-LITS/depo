<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class CreateShelfTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function up(): void
    {
        $this->table('shelf', ['module', 'side', 'section', 'shelf'])
            ->addColumn('module', 'string', ['length' => 1])
            ->addColumn('side', 'string', ['length' => 2])
            ->addColumn('section', 'string', ['length' => 2])
            ->addColumn('shelf', 'string', ['length' => 2])
            ->addColumn('tray_id', 'string', ['length' => 1])
            ->addForeignKey('tray_id', 'tray', 'id', ForeignKey::RESTRICT)
            ->create();
    }

    #[\Override]
    protected function down(): void
    {
        $this->table('shelf')->drop();
    }
}
