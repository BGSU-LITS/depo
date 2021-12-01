<?php

declare(strict_types=1);

namespace Lits\Action;

use Slim\Exception\HttpInternalServerErrorException;

use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\func;
use function Latitude\QueryBuilder\group;

final class IndexAction extends AuthAction
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
            'total_location' => [],
            'total_catalog' => [],
        ];

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
                            field('state')->notEq('deaccessioned')
                        ))
                    )
                )
                ->groupBy('catalog_id', 'location')
        );

        /** @var string[] $row */
        foreach ($statement as $row) {
            if (
                !isset($row['catalog_id']) ||
                !isset($row['location']) ||
                !isset($row['total'])
            ) {
                continue;
            }

            if (!isset($context['total_location'][$row['catalog_id']])) {
                $context['total_location'][$row['catalog_id']] = [];
            }

            $context['total_location'][$row['catalog_id']][$row['location']] =
                (int) $row['total'];
        }

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
}
