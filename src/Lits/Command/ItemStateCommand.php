<?php

declare(strict_types=1);

namespace Lits\Command;

use Lits\Command;
use Lits\Data\ItemData;
use Lits\Exception\FailedCommandException;
use Lits\Exception\InvalidConfigException;

final class ItemStateCommand extends DatabaseCommand
{
    /**
     * @throws FailedCommandException
     * @throws InvalidConfigException
     */
    #[\Override]
    public function command(): void
    {
        $rows = ItemData::setState($this->settings, $this->database);

        Command::output(
            'Updated ' . \number_format($rows) .
            ' total rows' . \PHP_EOL,
        );
    }
}
