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
    public ?string $color1 = null;
    public ?string $color2 = null;

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        $catalog = new static($settings, $database);

        $data = self::findRowString($row, 'id');

        if (\is_null($data)) {
            throw new InvalidDataException('ID must be specified');
        }

        $catalog->id = \strtolower($data);

        $data = self::findRowString($row, 'name');

        if (\is_null($data)) {
            throw new InvalidDataException('Name must be specified');
        }

        $catalog->name = $data;

        $data = self::findRowString($row, 'url');

        if (\is_null($data)) {
            throw new InvalidDataException('URL must be specified ');
        }

        $catalog->url = $data;

        $catalog->color1 = self::findRowString($row, 'color1');
        $catalog->color2 = self::findRowString($row, 'color2');

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
                'color1' => $this->color1,
                'color2' => $this->color2,
            ]);
        } catch (DuplicateInsertException $exception) {
            $this->database->update(
                'catalog',
                [
                    'name' => $this->name,
                    'url' => $this->url,
                    'color1' => $this->color1,
                    'color2' => $this->color2,
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

        /** @var array<string, string|null> $row */
        foreach ($statement as $row) {
            $object = self::fromRow($row, $settings, $database);

            $result[$object->id] = $object;
        }

        return $result;
    }
}
