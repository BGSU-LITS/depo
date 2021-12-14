<?php

declare(strict_types=1);

namespace Lits\Action\Shelves;

use Latitude\QueryBuilder\Query\SelectQuery;
use Lits\Action\AuthAction;
use Lits\Action\DatabaseFileTrait;
use Lits\Data\PlaceData;
use Slim\Exception\HttpInternalServerErrorException;

use function Latitude\QueryBuilder\criteria;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\group;
use function Latitude\QueryBuilder\identify;
use function Latitude\QueryBuilder\on;

final class MissingShelvesAction extends AuthAction
{
    use DatabaseFileTrait;

    public const TABLE_HEADERS = [
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

    /** @throws HttpInternalServerErrorException */
    public function action(): void
    {
        $context = [
            'pagination' => $this->database->paginate($this->select()),
            'pagination_sort' => 'sorted by Barcode',
            'pagination_file' => 'xlsx',
            'table_headers' => self::TABLE_HEADERS,
        ];

        $context['pagination']->setNormalizeOutOfRangePages(true);
        $context['pagination']->setMaxPerPage(20);

        $page = (int) $this->request->getQueryParam('page', 1);

        if ($page > 0) {
            $context['pagination']->setCurrentPage($page);
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

    /**
     * @return mixed[][]
     * @throws HttpInternalServerErrorException
     */
    protected function file(): array
    {
        try {
            $statement = $this->database->execute($this->select());

            /** @var mixed[][]|false $rows */
            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not fetch data from database',
                $exception
            );
        }

        if ($rows === false) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not fetch data from database'
            );
        }

        return [self::TABLE_HEADERS] + $rows;
    }

    private function select(): SelectQuery
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
            ->from('item')
            ->join('barcode', on('item.id', 'barcode.item_id'))
            ->join('place', on('item.id', 'place.item_id'))
            ->where(field('item.newest')->eq(true))
            ->andWhere(group(field('item.state')->isNull()->or(
                field('item.state')->notEq('deaccession')
            )))
            ->andWhere(criteria(
                '%s REGEXP %s',
                identify('barcode.barcode'),
                PlaceData::PATTERN_BARCODE_MYSQL
            ))
            ->andWhere(field('place.tray_id')->isNull())
            ->groupBy('item.id')
            ->orderBy('barcode.barcode');
    }
}
