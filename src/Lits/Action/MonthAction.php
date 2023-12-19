<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Slim\Exception\HttpInternalServerErrorException;

final class MonthAction extends Action
{
    /** @throws HttpInternalServerErrorException */
    public function action(): void
    {
        $params = [];

        /** @var ?string $date */
        $date = $this->request->getQueryParam('date');

        if (!\is_null($date)) {
            $params['date'] = $date;
        }

        try {
            $this->redirect(
                $this->routeCollector->getRouteParser()->urlFor(
                    'changes',
                    [],
                    $params,
                ),
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }
}
