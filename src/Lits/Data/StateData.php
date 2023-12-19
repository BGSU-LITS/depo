<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Database;
use Lits\Exception\DuplicateInsertException;
use Lits\Exception\InvalidDataException;
use Lits\Settings;

final class StateData extends DatabaseData
{
    public string $catalog_id;
    public string $state;
    public string $field;
    public string $value;

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database,
    ): self {
        $state = new static($settings, $database);

        $value = self::findRowString($row, 'catalog_id');

        if (\is_null($value)) {
            throw new InvalidDataException('Catalog ID must be specified');
        }

        $state->catalog_id = $value;

        $value = self::findRowString($row, 'state');

        if (\is_null($value)) {
            throw new InvalidDataException('State must be specified');
        }

        $state->state = $value;

        $value = self::findRowString($row, 'field');

        if (\is_null($value)) {
            throw new InvalidDataException('Field must be specified');
        }

        $state->field = $value;

        $value = self::findRowString($row, 'value');

        if (\is_null($value)) {
            throw new InvalidDataException('Value must be specified');
        }

        $state->value = $value;

        return $state;
    }

    /** @throws \PDOException */
    public function remove(): void
    {
        $this->database->delete('state', [
            'catalog_id' => $this->catalog_id,
            'state' => $this->state,
        ]);
    }

    /**
     * @throws \PDOException
     * @throws DuplicateInsertException
     */
    public function save(): void
    {
        $this->remove();

        $this->database->insert('state', [
            'catalog_id' => $this->catalog_id,
            'state' => $this->state,
            'field' => $this->field,
            'value' => $this->value,
        ]);
    }

    /** @return array<self> */
    public static function all(Settings $settings, Database $database): array
    {
        $statement = $database->execute(
            $database->query
                ->select()
                ->from('state')
                ->orderBy('catalog_id', 'ASC')
                ->orderBy('state', 'ASC'),
        );

        $result = [];

        /** @var array<string, string|null> $row */
        foreach ($statement as $row) {
            $object = self::fromRow($row, $settings, $database);

            $result[] = $object;
        }

        return $result;
    }
}
