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
                        find.shelf,
                        if(
                            tray.level = "tray",
                            find.tray,
                            find.item
                        )
                    )
                ) as used
            from
                shelf
            left join (
                select
                    place.module,
                    place.side,
                    place.section,
                    place.shelf,
                    place.tray,
                    place.item
                from
                    place
                join
                    item
                        on place.item_id = item.id
                            and item.newest = 1
                            and (
                                item.state is null
                                    or item.state != "deaccession"
                            )
            ) as find
                using (module, side, section, shelf)
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
            ' total rows' . \PHP_EOL,
        );
    }
}
