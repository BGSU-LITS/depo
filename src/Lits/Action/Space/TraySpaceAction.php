<?php

declare(strict_types=1);

namespace Lits\Action\Space;

use Lits\Action\AuthDatabaseAction;
use Lits\Data\ShelfData;
use Lits\Data\SpaceData;
use Lits\Data\TrayData;
use Safe\Exceptions\DatetimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

use function Latitude\QueryBuilder\field;

final class TraySpaceAction extends AuthDatabaseAction
{
    /**
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    public function action(): void
    {
        if (!isset($this->data['tray'])) {
            throw new HttpBadRequestException(
                $this->request,
                'Tray ID must be specified'
            );
        }

        $context = [
            'status' => $this->data['status'] ?? 'total',
            'trays' => TrayData::all($this->settings, $this->database),
        ];

        try {
            $context['space'] = SpaceData::fromTrayId(
                $this->data['tray'],
                $this->settings,
                $this->database
            );

            $context['modules'] = $this->modules(
                $context['space'],
                $context['status']
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not find most recent update',
                $exception
            );
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
     * @return string[][][][]
     * @throws DatetimeException
     */
    private function modules(SpaceData $space, ?string $status): array
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select('*')
                ->from('space')
                ->where(field('updated')->eq(
                    $space->updated->format('Y-m-d')
                ))
                ->orderBy('module', 'ASC')
                ->orderBy('side', 'ASC')
                ->orderBy('shelf', 'ASC')
                ->orderBy('section', 'ASC')
        );

        $result = [];
        $modules = [];

        /** @var array<string, string|null> $row */
        foreach ($statement as $row) {
            $shelf = ShelfData::fromRow(
                $row,
                $this->settings,
                $this->database
            );

            $used = isset($row['used']) ? (int) $row['used'] : 0;
            $count = self::count($space, $shelf, $used, $status);

            // phpcs:disable Squiz.Arrays.ArrayBracketSpacing
            $result
                [$shelf->module]
                [$shelf->side]
                [$shelf->section]
                [$shelf->shelf] = $count;
            // phpcs:enable

            if ($count !== '') {
                $modules[$shelf->module][$shelf->side] = true;
            }
        }

        return self::filterModules($result, $modules);
    }

    private static function count(
        SpaceData $space,
        ShelfData $shelf,
        int $used,
        ?string $status
    ): string {
        if ($shelf->tray_id !== $space->tray->id) {
            return '';
        }

        if ($status === 'used') {
            return self::countCheck($used);
        }

        if ($status === 'free') {
            return self::countCheck(($space->tray->per_shelf ?? 1) - $used);
        }

        return (string) $used;
    }

    private static function countCheck(int $count): string
    {
        if ($count > 0) {
            return (string) $count;
        }

        return '';
    }

    /**
     * @param string[][][][] $result
     * @param bool[][] $modules
     * @return string[][][][]
     */
    private static function filterModules(array $result, array $modules): array
    {
        foreach ($result as $module => $sides) {
            $result[$module] = \array_intersect_key($sides, $modules[$module]);
        }

        return $result;
    }
}
