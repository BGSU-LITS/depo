<?php

declare(strict_types=1);

namespace Lits\Action;

use Latitude\QueryBuilder\Query\SelectQuery;
use Lits\Data\PlaceData;
use Lits\Exception\InvalidDataException;
use Slim\Exception\HttpInternalServerErrorException;

use function Latitude\QueryBuilder\field;

final class ItemsAction extends AuthAction
{
    use DatabaseFileTrait;
    use ItemsTrait;

    /** @throws HttpInternalServerErrorException */
    public function action(): void
    {
        $context = [];

        if (isset($this->data['barcode'])) {
            try {
                $context['barcode'] = $this->data['barcode'];
                $context['place'] = $this->place();
                $context += $this->context();
            } catch (InvalidDataException $exception) {
                $this->messages[] = [
                    'level' => 'failure',
                    'message' => \rtrim($exception->getMessage(), '.') . '.',
                ];
            }
        }

        try {
            $barcode = (string) $this->request->getParam('barcode');

            if ($barcode !== '') {
                $this->redirect(
                    $this->routeCollector->getRouteParser()->urlFor(
                        'items',
                        ['barcode' => $barcode]
                    )
                );

                return;
            }

            $this->render($this->template(), $context);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }
    }

    /** @throws InvalidDataException */
    protected function select(): SelectQuery
    {
        $place = $this->place();

        $select = $this->selectAll()
            ->andWhere(field('place.module')->eq($place->module))
            ->andWhere(field('place.side')->eq($place->side));

        if ($place->section !== '00') {
            $select = $select->andWhere(
                field('place.section')->eq($place->section)
            );
        }

        if ($place->shelf !== '00') {
            $select = $select->andWhere(
                field('place.shelf')->eq($place->shelf)
            );
        }

        if ($place->tray !== '00') {
            $select = $select->andWhere(
                field('place.tray')->eq($place->tray)
            );
        }

        if ($place->item !== '00') {
            $select = $select->andWhere(
                field('place.item')->eq($place->item)
            );
        }

        return $select;
    }

    /** @throws InvalidDataException */
    private function place(): PlaceData
    {
        if (isset($this->data['barcode'])) {
            $place = PlaceData::fromBarcode(
                \str_pad($this->data['barcode'], 12, '0'),
                $this->settings,
                $this->database
            );

            if (
                !\is_null($place) &&
                $place->module !== '0' &&
                $place->side !== '00'
            ) {
                return $place;
            }
        }

        throw new InvalidDataException(
            'A partial barcode must be specified including module and side'
        );
    }
}
