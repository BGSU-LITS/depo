<?php

declare(strict_types=1);

namespace Lits\Action;

use Latitude\QueryBuilder\ExpressionInterface;
use Lits\Data\CatalogData;
use Safe\DateTimeImmutable;
use Safe\Exceptions\DatetimeException;
use Safe\Exceptions\StringsException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;

use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\express;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\func;
use function Safe\substr;

final class ChangesAction extends AuthDatabaseAction
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
        $context = $this->dateContext();

        $context['catalogs'] = CatalogData::all(
            $this->settings,
            $this->database
        );

        try {
            $context['date_start'] = $this->dateStart();
            $context['date_end'] = $this->dateEnd();
            $context['date_days'] = (int) $context['date_start']
                ->diff($context['date_end'])
                ->format('%a');

            $context['total'] = $this->total(
                $context['date_start'],
                $context['date_end']
            );

            $context['chart'] = $this->chart(
                $context['date_start'],
                $context['date_end'],
                $context['date_days']
            );

            $context['chart_dates'] = self::chartDates(
                $context['date_start'],
                $context['date_end'],
                $context['date_days']
            );
        } catch (DatetimeException $exception) {
            throw new HttpBadRequestException(
                $this->request,
                'Could not retrieve totals',
                $exception
            );
        }

        return $context;
    }

    /**
     * @throws HttpBadRequestException
     * @throws DatetimeException
     */
    private function dateStart(): DateTimeImmutable
    {
        /** @var ?string $param */
        $param = $this->request->getQueryParam('start');

        if (!\is_null($param)) {
            try {
                return new DateTimeImmutable($param);
            } catch (\Throwable $exception) {
                throw new HttpBadRequestException(
                    $this->request,
                    'Invalid start date specified',
                    $exception
                );
            }
        }

        return $this->date()->modify('first day of this month');
    }

    /**
     * @throws HttpBadRequestException
     * @throws DatetimeException
     */
    private function dateEnd(): DateTimeImmutable
    {
        /** @var ?string $param */
        $param = $this->request->getQueryParam('end');

        if (!\is_null($param)) {
            try {
                return new DateTimeImmutable($param);
            } catch (\Throwable $exception) {
                throw new HttpBadRequestException(
                    $this->request,
                    'Invalid end date specified',
                    $exception
                );
            }
        }

        return $this->date()->modify('last day of this month');
    }

    /**
     * @throws DatetimeException
     * @return array<string, array<string, int>>
     */
    private function total(
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): array {
        $total = [];

        $statement = $this->statement($start, $end, ['catalog_id', 'state']);

        /** @var array<string, ?string> $row */
        foreach ($statement as $row) {
            $total = self::rowTotal($row, $total);
        }

        return $total;
    }

    /**
     * @throws DatetimeException
     * @return array<string, array<string, array<string, int>>>
     */
    private function chart(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $days
    ): array {
        $chart = [];

        $group_by = ['catalog_id', 'state', express('YEAR(updated)')];

        if ($days <= 365) {
            $group_by[] = express('MONTH(updated)');
        }

        if ($days <= 31) {
            $group_by[] = express('DAY(updated)');
        }

        $statement = $this->statement($start, $end, $group_by);

        /** @var array<string, ?string> $row */
        foreach ($statement as $row) {
            $chart = self::rowChart($row, $chart, $days);
        }

        return $chart;
    }

    /**
     * @param list<string|ExpressionInterface> $group_by
     * @throws DatetimeException
     */
    private function statement(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        array $group_by
    ): \PDOStatement {
        return $this->database->execute(
            $this->database->query
                ->select(
                    'catalog_id',
                    'state',
                    'updated',
                    alias(func('COUNT', '*'), 'total')
                )
                ->from('item')
                ->where(field('updated')->between(
                    $start->format('Y-m-d'),
                    $end->format('Y-m-d')
                ))
                ->andWhere(field('state')->isNotNull())
                ->groupBy(...$group_by)
        );
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
            $total[$row['catalog_id']][$row['state']] = (int) $row['total'];
        }

        return $total;
    }

    /**
     * @param array<string, ?string> $row
     * @param array<string, array<string, array<string, int>>> $chart
     * @return array<string, array<string, array<string, int>>>
     */
    private static function rowChart(
        array $row,
        array $chart,
        int $days
    ): array {
        if (
            isset($row['catalog_id']) &&
            isset($row['state']) &&
            isset($row['updated']) &&
            isset($row['total'])
        ) {
            $updated = self::updated($row['updated'], $days);

            $chart[$row['catalog_id']][$row['state']][$updated] =
                (int) $row['total'];
        }

        return $chart;
    }

    private static function updated(string $updated, int $days): string
    {
        try {
            return substr(
                $updated,
                0,
                $days > 365 ? 4 : ($days > 31 ? 7 : 10)
            );
        } catch (StringsException $exception) {
            return $updated;
        }
    }

    /**
     * @return list<string>
     * @throws DatetimeException
     */
    private static function chartDates(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        int $days
    ): array {
        $dates = [];
        $format = 'Y-m-d';
        $offset = '+1 day';

        if ($days > 31) {
            $format = 'Y-m';
            $offset = '+1 month';
        }

        if ($days > 365) {
            $format = 'Y';
            $offset = '+1 year';
        }

        while ($start->format($format) < $end->format($format)) {
            $dates[] = $start->format($format);

            $start = $start->modify($offset);
        }

        $dates[] = $end->format($format);

        return $dates;
    }
}
