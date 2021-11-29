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
    public ?int $total = null;
    public ?string $color = null;

    /**
     * @param mixed[] $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        $tray = new static($settings, $database);

        if (
            !isset($row['id']) ||
            !\is_string($row['id']) ||
            $row['id'] === ''
        ) {
            throw new InvalidDataException('ID must be specified');
        }

        $tray->id = \strtoupper($row['id']);

        if (isset($row['name'])) {
            $tray->name = (string) $row['name'];
        }

        if (
            isset($row['length']) &&
            !\is_null($row['length']) &&
            $row['length'] > 0
        ) {
            $tray->length = (float) $row['length'];
        }

        if (
            isset($row['width']) &&
            !\is_null($row['width']) &&
            $row['width'] > 0
        ) {
            $tray->width = (float) $row['width'];
        }

        if (
            isset($row['height']) &&
            !\is_null($row['height']) &&
            $row['height'] > 0
        ) {
            $tray->height = (float) $row['height'];
        }

        if (
            isset($row['per_shelf']) &&
            !\is_null($row['per_shelf']) &&
            $row['per_shelf'] > 0
        ) {
            $tray->per_shelf = (int) $row['per_shelf'];
        }

        if (
            isset($row['total']) &&
            !\is_null($row['total']) &&
            $row['total'] > 0
        ) {
            $tray->total = (int) $row['total'];
        }

        if (isset($row['color']) && !\is_null($row['color'])) {
            $tray->color = (string) $row['color'];
        }

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

        $result = $this->fractionLength() .
            ' × ' . $this->fractionWidth() .
            ' × ' . $this->fractionHeight();

        if ($this->id === 'M') {
            return $result . ' (' . $this->name . ')';
        }

        return $result;
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
     */
    public function save(): void
    {
        $this->remove();

        $this->database->insert('tray', [
            'id' => $this->id,
            'name' => $this->name,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'per_shelf' => $this->per_shelf,
            'total' => $this->total,
            'color' => $this->color,
        ]);
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

        /** @var mixed[] $row */
        foreach ($statement as $row) {
            $object = self::fromRow($row, $settings, $database);

            $result[$object->id] = $object;
        }

        return $result;
    }

    public static function fraction(float $number): string
    {
        $whole = \floor($number);
        $decimal = $number - $whole;

        if ($decimal === \floatval(0.0)) {
            return (string) $whole;
        }

        if ($decimal === \floatval(0.125)) {
            return (string) $whole . '⅛';
        }

        if ($decimal === \floatval(0.25)) {
            return (string) $whole . '¼';
        }

        if ($decimal === \floatval(0.375)) {
            return (string) $whole . '⅜';
        }

        if ($decimal === \floatval(0.5)) {
            return (string) $whole . '½';
        }

        if ($decimal === \floatval(0.625)) {
            return (string) $whole . '⅝';
        }

        if ($decimal === \floatval(0.75)) {
            return (string) $whole . '¾';
        }

        if ($decimal === \floatval(0.875)) {
            return (string) $whole . '⅞';
        }

        return (string) $number;
    }
}
