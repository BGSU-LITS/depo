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
            $context['module'] = $this->data['module'];

            if (!\in_array($context['module'], $context['modules'], true)) {
                throw new HttpNotFoundException(
                    $this->request,
                    'Module ' . $context['module'] . 'could not be found'
                );
            }

            $context['sides'] = $this->sides($context['module']);

            if (isset($this->data['side'])) {
                $context['side'] = $this->data['side'];

                if (!\in_array($context['side'], $context['sides'], true)) {
                    throw new HttpNotFoundException(
                        $this->request,
                        'Side ' . $context['side'] .
                        ' could not be found in Module ' . $context['module']
                    );
                }

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
            try {
                $this->redirect(
                    $this->routeCollector->getRouteParser()->urlFor(
                        'shelves',
                        ['module' => (string) \reset($context['modules'])]
                    )
                );
            } catch (\Throwable $exception) {
                throw new HttpInternalServerErrorException(
                    $this->request,
                    null,
                    $exception
                );
            }

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
                    'section',
                    'shelf',
                    alias(func('COUNT', '*'), 'total')
                )
                ->from('place')
                ->where(field('module')->eq($module))
                ->andWhere(field('side')->eq($side))
                ->andWhere(field('tray_id')->isNull())
                ->groupBy('section', 'shelf')
                ->orderBy('shelf', 'ASC')
                ->orderBy('section', 'ASC')
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