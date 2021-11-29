<?php

declare(strict_types=1);

namespace Lits\Command;

use Lits\Command;
use Lits\Data\PlaceData;
use Lits\Exception\FailedCommandException;
use Lits\Exception\InvalidConfigException;

final class TrayIdsCommand extends DatabaseCommand
{
    /**
     * @throws FailedCommandException
     * @throws InvalidConfigException
     */
    public function command(): void
    {
        $rows = PlaceData::setTrayIds($this->database);

        Command::output(
            'Updated ' . \number_format($rows) .
            ' total rows' . \PHP_EOL
        );
    }
}
