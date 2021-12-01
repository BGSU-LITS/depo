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
     * @param mixed[] $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        $state = new static($settings, $database);

        if (
            !isset($row['catalog_id']) ||
            !\is_string($row['catalog_id']) ||
            $row['catalog_id'] === ''
        ) {
            throw new InvalidDataException('Catalog ID must be specified');
        }

        if (
            !isset($row['state']) ||
            !\is_string($row['state']) ||
            $row['state'] === ''
        ) {
            throw new InvalidDataException('State must be specified');
        }

        if (
            !isset($row['field']) ||
            !\is_string($row['field']) ||
            $row['field'] === ''
        ) {
            throw new InvalidDataException('Field must be specified');
        }

        if (
            !isset($row['value']) ||
            !\is_string($row['value']) ||
            $row['value'] === ''
        ) {
            throw new InvalidDataException('Value must be specified');
        }

        $state->catalog_id = $row['catalog_id'];
        $state->state = $row['state'];
        $state->field = $row['field'];
        $state->value = $row['value'];

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

    /** @return self[] */
    public static function all(Settings $settings, Database $database): array
    {
        $statement = $database->execute(
            $database->query
                ->select()
                ->from('state')
                ->orderBy('catalog_id', 'ASC')
                ->orderBy('state', 'ASC')
        );

        $result = [];

        /** @var mixed[] $row */
        foreach ($statement as $row) {
            $object = self::fromRow($row, $settings, $database);

            $result[] = $object;
        }

        return $result;
    }
}
