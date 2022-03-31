<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Database;
use Lits\Exception\DuplicateInsertException;
use Lits\Exception\InvalidDataException;
use Lits\Settings;

final class CatalogData extends DatabaseData
{
    public string $id;
    public string $name;
    public string $url;

    /**
     * @param mixed[] $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        $catalog = new static($settings, $database);

        if (
            !isset($row['id']) ||
            !\is_string($row['id']) ||
            $row['id'] === ''
        ) {
            throw new InvalidDataException('ID must be specified');
        }

        if (
            !isset($row['name']) ||
            !\is_string($row['name']) ||
            $row['name'] === ''
        ) {
            throw new InvalidDataException('Name must be specified');
        }

        if (
            !isset($row['url']) ||
            !\is_string($row['url']) ||
            $row['url'] === ''
        ) {
            throw new InvalidDataException('URL must be specified');
        }

        $catalog->id = \strtolower($row['id']);
        $catalog->name = $row['name'];
        $catalog->url = $row['url'];

        return $catalog;
    }

    /** @throws \PDOException */
    public function remove(): void
    {
        $this->database->delete('catalog', ['id' => $this->id]);
    }

    /**
     * @throws \PDOException
     * @throws DuplicateInsertException
     */
    public function save(): void
    {
        try {
            $this->database->insert('catalog', [
                'id' => $this->id,
                'name' => $this->name,
                'url' => $this->url,
            ]);
        } catch (DuplicateInsertException $exception) {
            $this->database->update(
                'catalog',
                [
                    'name' => $this->name,
                    'url' => $this->url,
                ],
                'id',
                $this->id,
            );
        }
    }

    /** @return array<string, self> */
    public static function all(Settings $settings, Database $database): array
    {
        $statement = $database->execute(
            $database->query
                ->select()
                ->from('catalog')
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
}
