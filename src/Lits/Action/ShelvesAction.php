<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Data\ShelfData;
use Lits\Data\TrayData;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use stdClass;

use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\func;
use function Latitude\QueryBuilder\group;
use function Latitude\QueryBuilder\on;

final class ShelvesAction extends AuthAction
{
    /**
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    public function action(): void
    {
        $context = [
            'modules' => $this->modules(),
        ];

        if (isset($this->data['module'])) {
            $context['module'] = $this->module(
                $this->data['module'],
                $context['modules']
            );

            $context['sides'] = $this->sides($context['module']);

            if (isset($this->data['side'])) {
                $context['side'] = $this->side(
                    $this->data['side'],
                    $context['module'],
                    $context['sides']
                );

                $context['sections'] = $this->sections(
                    $context['module'],
                    $context['side']
                );

                $context['shelves'] = $this->shelves(
                    $context['module'],
                    $context['side']
                );

                $context['missing'] = $this->missing(
                    $context['module'],
                    $context['side']
                );

                $context['trays'] = TrayData::all(
                    $this->settings,
                    $this->database
                );
            }
        } elseif (\count($context['modules']) === 1) {
            $this->redirectModule((string) \reset($context['modules']));

            return;
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
     * @param array<string, string> $data
     * @throws HttpInternalServerErrorException
     */
    public function post(
        ServerRequest $request,
        Response $response,
        array $data
    ): Response {
        $this->setup($request, $response, $data);

        $post = $this->request->getParsedBody();

        if (!\is_array($post)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        try {
            $shelf = ShelfData::fromRow(
                $post,
                $this->settings,
                $this->database
            );

            $shelf->save();

            $name = 'Section ' . $shelf->section . ' Shelf ' . $shelf->shelf;

            if (\is_null($shelf->tray_id)) {
                $this->message('success', 'Removed ' . $name);
            } else {
                $this->message(
                    'success',
                    'Updated ' . $name . ' to Tray Type ' . $shelf->tray_id
                );
            }

            $this->redirect(
                $this->routeCollector->getRouteParser()->urlFor('shelves', [
                    'module' => $shelf->module,
                    'side' => $shelf->side,
                ])
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }

        return $this->response;
    }

    /** @return mixed[] */
    private function modules(): array
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select('module')
                ->distinct()
                ->from('shelf')
                ->orderBy('module', 'ASC')
        );

        return self::column($statement);
    }

    /**
     * @param mixed[] $modules
     * @throws HttpNotFoundException
     */
    private function module(string $module, array $modules): string
    {
        if (!\in_array($module, $modules, true)) {
            throw new HttpNotFoundException(
                $this->request,
                'Module ' . $module . 'could not be found'
            );
        }

        return $module;
    }

    /** @return mixed[] */
    private function sides(string $module): array
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select('side')
                ->distinct()
                ->from('shelf')
                ->where(field('module')->eq($module))
                ->orderBy('side', 'ASC')
        );

        return self::column($statement);
    }

    /**
     * @param mixed[] $sides
     * @throws HttpNotFoundException
     */
    private function side(string $side, string $module, array $sides): string
    {
        if (!\in_array($side, $sides, true)) {
            throw new HttpNotFoundException(
                $this->request,
                'Side ' . $side . ' could not be found in Module ' . $module
            );
        }

        return $side;
    }

    /** @return mixed[] */
    private function sections(string $module, string $side): array
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select('section')
                ->distinct()
                ->from('shelf')
                ->where(field('module')->eq($module))
                ->andWhere(field('side')->eq($side))
                ->orderBy('section', 'ASC')
        );

        return self::column($statement);
    }

    /** @return string[][] */
    private function shelves(string $module, string $side): array
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select()
                ->from('shelf')
                ->where(field('module')->eq($module))
                ->andWhere(field('side')->eq($side))
                ->orderBy('shelf', 'ASC')
                ->orderBy('section', 'ASC')
        );

        $statement->setFetchMode(
            \PDO::FETCH_CLASS,
            ShelfData::class,
            [$this->settings, $this->database]
        );

        $result = [];

        /** @var ShelfData $row */
        foreach ($statement as $row) {
            $result[$row->shelf][$row->section] = $row->tray_id ?? '';
        }

        return $result;
    }

    /** @return int[][] */
    private function missing(string $module, string $side): array
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select(
                    'place.section',
                    'place.shelf',
                    alias(func('COUNT', '*'), 'total')
                )
                ->from('item')
                ->join('place', on('item.id', 'place.item_id'))
                ->where(field('item.newest')->eq(true))
                ->andWhere(group(field('item.state')->isNull()->or(
                    field('item.state')->notEq('deaccession')
                )))
                ->andWhere(field('place.module')->eq($module))
                ->andWhere(field('place.side')->eq($side))
                ->andWhere(field('place.tray_id')->isNull())
                ->groupBy('place.section', 'place.shelf')
                ->orderBy('place.section', 'ASC')
                ->orderBy('place.shelf', 'ASC')
        );

        $result = [];

        $rows = $statement->fetchAll(\PDO::FETCH_OBJ);

        if (\is_array($rows)) {
            /** @var stdClass $row */
            foreach ($rows as $row) {
                $result[(string) $row->shelf][(string) $row->section] =
                    (int) $row->total;
            }
        }

        return $result;
    }

    /** @throws HttpInternalServerErrorException */
    private function redirectModule(string $module): void
    {
        try {
            $this->redirect(
                $this->routeCollector->getRouteParser()->urlFor(
                    'shelves',
                    ['module' => $module]
                )
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }
    }

    /** @return mixed[] */
    private static function column(\PDOStatement $statement): array
    {
        $result = $statement->fetchAll(
            \PDO::FETCH_COLUMN
        );

        if (\is_array($result)) {
            return $result;
        }

        return [];
    }
}
