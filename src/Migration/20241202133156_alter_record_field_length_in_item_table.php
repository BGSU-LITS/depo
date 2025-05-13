<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;

final class AlterRecordFieldLengthInItemTable extends AbstractMigration
{
    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function up(): void
    {
        $this->table('item')
            ->changeColumn('record', 'record', 'string', ['length' => 10])
            ->save();
    }

    /** @throws InvalidArgumentValueException */
    #[\Override]
    protected function down(): void
    {
        $this->table('item')
            ->changeColumn('record', 'record', 'string', ['length' => 9])
            ->save();
    }
}
