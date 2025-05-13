<?php

declare(strict_types=1);

namespace Lits\Action\Shelves;

use Latitude\QueryBuilder\Query\SelectQuery;
use Lits\Action\AuthDatabaseAction;
use Lits\Action\DatabaseFileTrait;
use Lits\Action\ItemsTrait;
use Slim\Exception\HttpInternalServerErrorException;

use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\on;

final class MissingShelvesAction extends AuthDatabaseAction
{
    use DatabaseFileTrait;
    use ItemsTrait;

    /** @throws HttpInternalServerErrorException */
    #[\Override]
    public function action(): void
    {
        try {
            $this->render($this->template(), $this->context());
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    protected function select(): SelectQuery
    {
        return $this->selectAll()
            ->leftJoin(
                'shelf',
                on('place.module', 'shelf.module')
                    ->and(on('place.side', 'shelf.side'))
                    ->and(on('place.section', 'shelf.section'))
                    ->and(on('place.shelf', 'shelf.shelf')),
            )
            ->where(field('shelf.tray_id')->isNull());
    }
}
