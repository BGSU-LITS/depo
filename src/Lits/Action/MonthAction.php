<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Config\DepoConfig;
use Lits\Data\CatalogData;
use Safe\DateTimeImmutable;
use Safe\Exceptions\DatetimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Throwable;

use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\func;

final class MonthAction extends AuthAction
{
    /**
     * @throws HttpInternalServerErrorException
     * @throws HttpBadRequestException
     */
    public function action(): void
    {
        \assert($this->settings['depo'] instanceof DepoConfig);

        $date_first = $this->settings['depo']->start;

        $statement = $this->database->execute(
            $this->database->query
                ->select(func('MAX', 'updated'))
                ->from('item')
        );

        $date_last = (string) $statement->fetchColumn();

        try {
            $date_last = new DateTimeImmutable($date_last);
            $date_last = $date_last->modify('first day of this month');

            /** @var ?string $date */
            $date = $this->request->getQueryParam('date');

            if (!\is_string($date)) {
                $date = $date_last;
            } else {
                $date = new DateTimeImmutable($date);
                $date = $date->modify('first day of this month');
            }

            $date_previous = $date->modify('previous month');
            $date_next = $date->modify('next month');
        } catch (Throwable $exception) {
            throw new HttpBadRequestException(
                $this->request,
                'Could not determine date range',
                $exception
            );
        }

        if ($date < $date_first || $date > $date_last) {
            throw new HttpBadRequestException(
                $this->request,
                'Invalid date requested'
            );
        }

        $context = [
            'catalogs' => CatalogData::all($this->settings, $this->database),
            'date' => $date,
            'date_first' => $date_first,
            'date_previous' => $date_previous,
            'date_next' => $date_next,
            'date_last' => $date_last,
            'total' => [],
        ];

        try {
            $statement = $this->database->execute(
                $this->database->query
                    ->select(
                        'catalog_id',
                        'state',
                        alias(func('COUNT', '*'), 'total')
                    )
                    ->from('item')
                    ->where(field('updated')->between(
                        $date->modify('first day of this month')
                            ->format('Y-m-d'),
                        $date->modify('last day of this month')
                            ->format('Y-m-d')
                    ))
                    ->andWhere(field('state')->isNotNull())
                    ->groupBy('catalog_id', 'state')
            );
        } catch (DatetimeException $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not determine date range',
                $exception
            );
        }

        /** @var string[] $row */
        foreach ($statement as $row) {
            if (
                !isset($row['catalog_id']) ||
                !isset($row['state']) ||
                !isset($row['total'])
            ) {
                continue;
            }

            if (!isset($context['total'][$row['catalog_id']])) {
                $context['total'][$row['catalog_id']] = [];
            }

            $context['total'][$row['catalog_id']][$row['state']] =
                (int) $row['total'];
        }

        try {
            $this->render($this->template(), $context);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }
    }
}
