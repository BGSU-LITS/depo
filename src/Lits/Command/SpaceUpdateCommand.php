<?php

declare(strict_types=1);

namespace Lits\Command;

use Lits\Command;
use Lits\Exception\FailedCommandException;
use Lits\Exception\InvalidConfigException;

final class SpaceUpdateCommand extends DatabaseCommand
{
    /**
     * @throws FailedCommandException
     * @throws InvalidConfigException
     */
    public function command(): void
    {
        $rows = (int) $this->database->pdo->exec('
            insert into
                space
            select
                shelf.module,
                shelf.side,
                shelf.section,
                shelf.shelf,
                (select max(updated) from item) as updated,
                shelf.tray_id,
                count(
                    distinct if(
                        tray.level is null,
                        place.shelf,
                        if(
                            tray.level = "tray",
                            place.tray,
                            place.item
                        )
                    )
                ) as used
            from
                shelf
            left join
                place
                    using (module, side, section, shelf)
            left join
                item
                    on place.item_id = item.id
                        and item.newest = 1
                        and (item.state is null or item.state != "deaccession")
            left join
                tray
                    on shelf.tray_id = tray.id
            group by
                shelf.module,
                shelf.side,
                shelf.section,
                shelf.shelf
            order by
                shelf.module,
                shelf.side,
                shelf.section,
                shelf.shelf;
        ');

        Command::output(
            'Inserted ' . \number_format($rows) .
            ' total rows' . \PHP_EOL
        );
    }
}
