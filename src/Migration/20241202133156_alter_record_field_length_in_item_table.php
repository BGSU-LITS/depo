<?php

declare(strict_types=1);

namespace Migration;

use Phoenix\Migration\AbstractMigration;

final class AlterRecordFieldLengthInItemTable extends AbstractMigration
{
    protected function up(): void
    {
        $this->table('item')
            ->changeColumn('record', 'record', 'string', ['length' => 10])
            ->save();
    }

    protected function down(): void
    {
        $this->table('item')
            ->changeColumn('record', 'record', 'string', ['length' => 9])
            ->save();
    }
}
