<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Database;
use Lits\Exception\DuplicateInsertException;
use Lits\Exception\InvalidDataException;
use Lits\Settings;

final class ShelfData extends DatabaseData
{
    public string $module;
    public string $side;
    public string $section;
    public string $shelf;
    public ?string $tray_id = null;

    /**
     * @param array<mixed> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database,
    ): self {
        $shelf = new static($settings, $database);

        $shelf->module = self::checkString($row, 'module');
        $shelf->side = self::checkString($row, 'side');
        $shelf->section = self::checkString($row, 'section');
        $shelf->shelf = self::checkString($row, 'shelf');

        if (
            isset($row['tray_id']) &&
            \is_string($row['tray_id']) &&
            $row['tray_id'] !== ''
        ) {
            $shelf->tray_id = $row['tray_id'];
        }

        return $shelf;
    }

    /**
     * @throws \PDOException
     * @throws DuplicateInsertException
     */
    public function save(): void
    {
        $this->database->delete('shelf', [
            'module' => $this->module,
            'side' => $this->side,
            'section' => $this->section,
            'shelf' => $this->shelf,
        ]);

        if (\is_null($this->tray_id)) {
            return;
        }

        $this->database->insert('shelf', [
            'module' => $this->module,
            'side' => $this->side,
            'section' => $this->section,
            'shelf' => $this->shelf,
            'tray_id' => $this->tray_id,
        ]);
    }

    /**
     * @param array<mixed> $row
     * @throws InvalidDataException
     */
    public static function checkString(array $row, string $key): string
    {
        if (
            !isset($row[$key]) ||
            !\is_string($row[$key]) ||
            $row[$key] === ''
        ) {
            throw new InvalidDataException(
                'The ' . $key . ' must be specified',
            );
        }

        return $row[$key];
    }
}
