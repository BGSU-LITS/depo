<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Database;
use Lits\Exception\InvalidDataException;
use Lits\Settings;
use Safe\DateTimeImmutable;
use Safe\Exceptions\DatetimeException;

use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\func;
use function Latitude\QueryBuilder\on;

final class SpaceData extends DatabaseData
{
    public TrayData $tray;
    public DateTimeImmutable $updated;
    public int $used;
    public int $shelves;

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        $space = new static($settings, $database);
        $space->tray = TrayData::fromRow($row, $settings, $database);

        $data = self::findRowDatetime($row, 'updated');

        if (\is_null($data)) {
            throw new InvalidDataException('Updated must be specified');
        }

        $space->updated = $data;

        $data = self::findRowInt($row, 'used');

        if (\is_null($data)) {
            throw new InvalidDataException('Used must be specified');
        }

        $space->used = $data;

        $data = self::findRowInt($row, 'shelves');

        if (\is_null($data)) {
            throw new InvalidDataException('Shelves must be specified');
        }

        $space->shelves = $data;

        return $space;
    }

    /**
     * @throws DatetimeException
     * @throws InvalidDataException
     */
    public static function fromTrayId(
        string $tray_id,
        Settings $settings,
        Database $database
    ): self {
        $statement = $database->execute(
            $database->query
                ->select(func('MAX', 'updated'))
                ->from('space')
        );

        try {
            $date = new DateTimeImmutable((string) $statement->fetchColumn());
        } catch (\Throwable $exception) {
            throw new InvalidDataException(
                'Could not determine updated date',
                0,
                $exception
            );
        }

        $statement = $database->execute(
            $database->query
                ->select(
                    'tray.*',
                    alias(func('MAX', 'space.updated'), 'updated'),
                    alias(func('SUM', 'space.used'), 'used'),
                    alias(func('COUNT', '*'), 'shelves')
                )
                ->from('space')
                ->join('tray', on('space.tray_id', 'tray.id'))
                ->where(field('space.tray_id')->eq($tray_id))
                ->andWhere(field('space.updated')->eq($date->format('Y-m-d')))
        );

        /** @var array<string, string|null>|false $row */
        $row = $statement->fetch();

        if (!\is_array($row)) {
            throw new InvalidDataException('Invalid Tray ID specified');
        }

        return self::fromRow($row, $settings, $database);
    }

    public function cubicFeet(int $count): ?float
    {
        if (
            \is_null($this->tray->length) ||
            \is_null($this->tray->width) ||
            \is_null($this->tray->height)
        ) {
            return null;
        }

        return (float) $count *
            self::toFeet($this->tray->length) *
            self::toFeet($this->tray->width) *
            self::toFeet($this->tray->height);
    }

    public function levelName(int $total = 1): string
    {
        if (\is_string($this->tray->level)) {
            return \ucwords($this->tray->level) . ($total === 1 ? '' : 's');
        }

        return $total === 1 ? 'Shelf' : 'Shelves';
    }

    public function usedSpace(): ?float
    {
        return $this->cubicFeet($this->used);
    }

    public function free(): int
    {
        return $this->total() - $this->used;
    }

    public function freeSpace(): ?float
    {
        return $this->cubicFeet($this->free());
    }

    public function total(): int
    {
        if (\is_null($this->tray->per_shelf)) {
            return $this->shelves;
        }

        return $this->shelves * $this->tray->per_shelf;
    }

    public function totalSpace(): ?float
    {
        return $this->cubicFeet($this->total());
    }

    /**
     * @return array<string, self>
     * @throws DatetimeException
     */
    public static function all(
        Settings $settings,
        Database $database,
        DateTimeImmutable $date
    ): array {
        $statement = $database->execute(
            $database->query
                ->select(
                    'tray.*',
                    alias(func('MAX', 'space.updated'), 'updated'),
                    alias(func('SUM', 'space.used'), 'used'),
                    alias(func('COUNT', '*'), 'shelves')
                )
                ->from('space')
                ->join('tray', on('space.tray_id', 'tray.id'))
                ->where(field('space.updated')->between(
                    $date->modify('first day of this month')
                        ->format('Y-m-d'),
                    $date->modify('last day of this month')
                        ->format('Y-m-d')
                ))
                ->groupBy('tray.id')
                ->orderBy('tray.id')
        );

        $result = [];

        /** @var array<string, string|null> $row */
        foreach ($statement as $row) {
            $object = self::fromRow($row, $settings, $database);

            $result[$object->tray->id] = $object;
        }

        return $result;
    }

    public static function toFeet(float $inches): float
    {
        return $inches / 12.0;
    }
}
