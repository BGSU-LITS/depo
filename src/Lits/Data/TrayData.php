<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Database;
use Lits\Exception\DuplicateInsertException;
use Lits\Exception\InvalidDataException;
use Lits\Settings;

final class TrayData extends DatabaseData
{
    public string $id;
    public string $name;
    public ?float $length = null;
    public ?float $width = null;
    public ?float $height = null;
    public ?int $per_shelf = null;
    public ?string $level = null;
    public ?string $color = null;

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        $tray = new static($settings, $database);

        $data = self::findRowString($row, 'id');

        if (\is_null($data)) {
            throw new InvalidDataException('ID must be specified');
        }

        $tray->id = \strtoupper($data);

        $data = self::findRowString($row, 'name');

        if (!\is_null($data)) {
            $tray->name = $data;
        }

        $tray->length = self::positiveFloat(
            self::findRowFloat($row, 'length')
        );

        $tray->width = self::positiveFloat(
            self::findRowFloat($row, 'width')
        );

        $tray->height = self::positiveFloat(
            self::findRowFloat($row, 'height')
        );

        $tray->per_shelf = self::positiveInt(
            self::findRowInt($row, 'per_shelf')
        );

        $tray->level = self::findRowString($row, 'level');
        $tray->color = self::findRowString($row, 'color');

        return $tray;
    }

    public function fractionName(): string
    {
        if (
            \is_null($this->length) ||
            \is_null($this->width) ||
            \is_null($this->height)
        ) {
            return $this->name;
        }

        return $this->fractionLength() .
            ' × ' . $this->fractionWidth() .
            ' × ' . $this->fractionHeight();
    }

    public function fractionLength(): string
    {
        return self::fraction($this->length ?? 0) . '″';
    }

    public function fractionWidth(): string
    {
        return self::fraction($this->width ?? 0) . '″';
    }

    public function fractionHeight(): string
    {
        return self::fraction($this->height ?? 0) . '″';
    }

    /** @throws \PDOException */
    public function remove(): void
    {
        $this->database->delete('tray', ['id' => $this->id]);
    }

    /**
     * @throws \PDOException
     * @throws DuplicateInsertException
     * @throws InvalidDataException
     */
    public function save(): void
    {
        if (\is_null($this->per_shelf) !== \is_null($this->level)) {
            throw new InvalidDataException(
                'Per Shelf and Level must be specified together'
            );
        }

        $map = [
            'id' => $this->id,
            'name' => $this->name,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'per_shelf' => $this->per_shelf,
            'level' => $this->level,
            'color' => $this->color,
        ];

        try {
            $this->database->insert('tray', $map);
        } catch (DuplicateInsertException $exception) {
            $this->database->update('tray', $map, 'id', $map['id']);
        }
    }

    /** @return array<string, self> */
    public static function all(Settings $settings, Database $database): array
    {
        $statement = $database->execute(
            $database->query
                ->select()
                ->from('tray')
                ->orderBy('id', 'ASC')
        );

        $result = [];

        /** @var array<string, string|null> $row */
        foreach ($statement as $row) {
            $object = self::fromRow($row, $settings, $database);

            $result[$object->id] = $object;
        }

        return $result;
    }

    public static function fraction(float $number): string
    {
        $whole = \floor($number);

        switch ($number - $whole) {
            case 0.0:
                return (string) $whole;

            case 0.125:
                return (string) $whole . '⅛';

            case 0.25:
                return (string) $whole . '¼';

            case 0.375:
                return (string) $whole . '⅜';

            case 0.5:
                return (string) $whole . '½';

            case 0.625:
                return (string) $whole . '⅝';

            case 0.75:
                return (string) $whole . '¾';

            case 0.875:
                return (string) $whole . '⅞';
        }

        return (string) $number;
    }

    public static function positiveFloat(?float $number): ?float
    {
        return \is_float($number) && $number > 0 ? $number : null;
    }

    public static function positiveInt(?int $number): ?int
    {
        return \is_int($number) && $number > 0 ? $number : null;
    }
}
