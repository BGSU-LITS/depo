<?php

declare(strict_types=1);

namespace Lits\Action;

use Slim\Exception\HttpInternalServerErrorException;

use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\func;
use function Latitude\QueryBuilder\group;

final class IndexAction extends AuthDatabaseAction
{
    /** @throws HttpInternalServerErrorException */
    public function action(): void
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select(func('MAX', 'updated'))
                ->from('item')
        );

        $context = [
            'updated' => $statement->fetchColumn(),
            'total' => null,
            'total_location' => $this->total(),
            'total_catalog' => [],
        ];

        foreach ($context['total_location'] as $catalog => $totals) {
            $context['total_catalog'][$catalog] = \array_sum($totals);
        }

        $context['total'] = \array_sum($context['total_catalog']);

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

    /** @return array<string, array<string, int>> */
    private function total(): array
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select(
                    'catalog_id',
                    'location',
                    alias(func('COUNT', '*'), 'total')
                )
                ->from('item')
                ->where(
                    field('newest')->eq(true)->and(
                        group(field('state')->isNull()->or(
                            field('state')->notEq('deaccession')
                        ))
                    )
                )
                ->groupBy('catalog_id', 'location')
        );

        $total = [];

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
            isset($row['location']) &&
            isset($row['total'])
        ) {
            if (!isset($total[$row['catalog_id']])) {
                $total[$row['catalog_id']] = [];
            }

            $total[$row['catalog_id']][$row['location']] = (int) $row['total'];
        }

        return $total;
    }
}
