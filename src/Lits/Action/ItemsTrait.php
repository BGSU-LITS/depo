<?php

declare(strict_types=1);

namespace Lits\Action;

use Latitude\QueryBuilder\Query\SelectQuery;
use Lits\Data\PlaceData;
use Lits\Exception\InvalidDataException;
use Slim\Exception\HttpInternalServerErrorException;

use function Latitude\QueryBuilder\criteria;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\group;
use function Latitude\QueryBuilder\identify;
use function Latitude\QueryBuilder\on;

trait ItemsTrait
{
    /** @var array<string> */
    private array $table_headers = [
        'Barcode',
        'Module',
        'Side',
        'Section',
        'Shelf',
        'Tray',
        'Item',
        'Catalog',
        'Location',
        'Record ID',
        'Created',
        'Updated',
        'Revision',
        'Status',
        'Message',
    ];

    /**
     * @return array<string, mixed>
     * @throws HttpInternalServerErrorException
     * @throws InvalidDataException
     */
    protected function context(): array
    {
        $context = [
            'pagination' => $this->database->paginate($this->select()),
            'pagination_rows' => 'Items',
            'pagination_sort' => 'sorted by Barcode',
            'pagination_file' => 'xlsx',
            'table_headers' => $this->table_headers,
        ];

        $page = (int) $this->request->getQueryParam('page', 1);

        try {
            $context['pagination']->setNormalizeOutOfRangePages(true);
            $context['pagination']->setMaxPerPage(20);

            if ($page > 0) {
                $context['pagination']->setCurrentPage($page);
            }
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Invalid page range specified',
                $exception,
            );
        }

        return $context;
    }

    /**
     * @return array<array<mixed>>
     * @throws HttpInternalServerErrorException
     */
    protected function file(): array
    {
        try {
            $statement = $this->database->execute($this->select());
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not fetch data from database',
                $exception,
            );
        }

        if ($rows === []) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not fetch data from database',
            );
        }

        return [$this->table_headers] + $rows;
    }

    protected function selectAll(): SelectQuery
    {
        return $this->database->query
            ->select(
                'barcode.barcode',
                'place.module',
                'place.side',
                'place.section',
                'place.shelf',
                'place.tray',
                'place.item',
                'item.catalog_id',
                'item.location',
                'item.record',
                'item.created',
                'item.updated',
                'item.revision',
                'item.status',
                'item.message',
            )
            ->from('place')
            ->join(
                'item',
                on('place.item_id', 'item.id')
                    ->and(field('item.newest')->eq(true))
                    ->and(group(field('item.state')->isNull()->or(
                        field('item.state')->notEq('deaccession'),
                    ))),
            )
            ->join(
                'barcode',
                on('place.item_id', 'barcode.item_id')
                    ->and(criteria(
                        '%s REGEXP %s',
                        identify('barcode.barcode'),
                        PlaceData::PATTERN_BARCODE_MYSQL,
                    )),
            )
            ->orderBy('barcode.barcode');
    }
}
