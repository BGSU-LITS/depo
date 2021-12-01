<?php

declare(strict_types=1);

namespace Lits\Command;

use Lits\Command;
use Lits\Data\ItemData;
use Lits\Exception\FailedCommandException;
use Lits\Exception\InvalidConfigException;

final class ItemNewestCommand extends DatabaseCommand
{
    /**
     * @throws FailedCommandException
     * @throws InvalidConfigException
     */
    public function command(): void
    {
        $rows = ItemData::setNewest($this->database);

        Command::output(
            'Updated ' . \number_format($rows) .
            ' total rows' . \PHP_EOL
        );
    }
}
