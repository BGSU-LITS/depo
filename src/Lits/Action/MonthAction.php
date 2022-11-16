<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Data\CatalogData;
use Safe\DateTimeImmutable;
use Safe\Exceptions\DatetimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;

use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\func;

final class MonthAction extends AuthAction
{
    use DateTrait;

    /**
     * @throws HttpInternalServerErrorException
     * @throws HttpBadRequestException
     */
    public function action(): void
    {
        $context = $this->context();

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

    /**
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     * @return array<string, mixed>
     */
    private function context(): array
    {
        $date = $this->date();

        try {
            $total = $this->total($date);
        } catch (\Throwable $exception) {
            throw new HttpBadRequestException(
                $this->request,
                'Could not retrieve totals',
                $exception
            );
        }

        return $this->dateContext() + [
            'catalogs' => CatalogData::all(
                $this->settings,
                $this->database
            ),
            'total' => $total,
        ];
    }

    /**
     * @throws DatetimeException
     * @return array<string, array<string, int>>
     */
    private function total(DateTimeImmutable $date): array
    {
        $total = [];

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

        /** @var array<string, ?string> $row */
        foreach ($statement as $row) {
            $total = self::rowTotal($row, $total);
        }

        return $total;
    }

    /**
     * @param array<string, ?string> $row
     * @param array<string, array<string, int>> $total
     * @return array<string, array<string, int>>
     */
    private static function rowTotal(array $row, array $total): array
    {
        if (
            isset($row['catalog_id']) &&
            isset($row['state']) &&
            isset($row['total'])
        ) {
            if (!isset($total[$row['catalog_id']])) {
                $total[$row['catalog_id']] = [];
            }

            $total[$row['catalog_id']][$row['state']] = (int) $row['total'];
        }

        return $total;
    }
}
