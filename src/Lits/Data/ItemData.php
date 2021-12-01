<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Database;
use Lits\Exception\InvalidDataException;
use Lits\Settings;
use PDOException;
use Safe\DateTimeImmutable;

use function Safe\preg_replace;

final class ItemData extends DatabaseData
{
    public ?int $id = null;
    public ?string $catalog_id = null;
    public string $location;
    public string $record;
    public \DateTimeInterface $created;
    public \DateTimeInterface $updated;
    public ?int $revision = null;
    public ?string $status = null;
    public ?string $message = null;
    public bool $newest = false;
    public ?string $state = null;

    /** @var string[] */
    public array $barcodes = [];

    /** @var string[] */
    public array $biblios = [];

    public ?PlaceData $place = null;

    /**
     * @param array<string, ?string> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        $item = new static($settings, $database);

        if (
            !isset($row['location']) ||
            !isset($row['record']) ||
            !isset($row['created']) ||
            !isset($row['updated'])
        ) {
            throw new InvalidDataException('Row does not contain valid data');
        }

        $item->location = \trim($row['location']);
        $item->record = \trim($row['record']);
        $item->created = self::parseDate($row['created']);
        $item->updated = self::parseDate($row['updated']);

        if (isset($row['revision'])) {
            $item->revision = (int) $row['revision'];
        }

        if (isset($row['status'])) {
            $item->status = self::parseCode($row['status']);
        }

        if (isset($row['message'])) {
            $item->message = self::parseCode($row['message']);
        }

        if (isset($row['newest'])) {
            $item->newest = (bool) $row['newest'];
        }

        if (isset($row['state'])) {
            $item->state = \trim($row['state']);
        }

        if (isset($row['barcodes'])) {
            $item->barcodes = self::parseList($row['barcodes']);
        }

        if (isset($row['biblios'])) {
            $item->biblios = self::parseList($row['biblios']);
        }

        $item->place = PlaceData::fromItem($item);

        return $item;
    }

    /**
     * @throws PDOException
     * @throws InvalidDataException
     */
    public function save(): void
    {
        $this->id = $this->database->insertOrUpdate(
            'item',
            [
                'catalog_id' => $this->catalog_id,
                'location' => $this->location,
                'record' => $this->record,
                'created' => $this->created->format('Y-m-d'),
                'updated' => $this->updated->format('Y-m-d'),
            ],
            [
                'revision' => $this->revision,
                'status' => $this->status,
                'message' => $this->message,
                'newest' => $this->newest,
                'state' => $this->state,
            ],
            'id'
        );

        $this->database->delete('barcode', ['item_id' => $this->id]);

        foreach ($this->barcodes as $barcode) {
            $this->database->insertIgnore(
                'barcode',
                [
                    'item_id' => $this->id,
                    'barcode' => $barcode,
                ]
            );
        }

        $this->database->delete('biblio', ['item_id' => $this->id]);

        foreach ($this->biblios as $biblio) {
            $this->database->insertIgnore(
                'biblio',
                [
                    'item_id' => $this->id,
                    'biblio' => $biblio,
                ]
            );
        }

        if (\is_null($this->place)) {
            return;
        }

        $this->place->item_id = $this->id;
        $this->place->save();
    }

    public static function setNewest(Database $database): int
    {
        $database->pdo->exec('
            create temporary table
                newest
            select
                catalog_id,
                record,
                max(updated) as updated
            from
                item
            group by
                catalog_id,
                record;
        ');

        return (int) $database->pdo->exec('
            update
                item,
                newest
            set
                item.newest = (item.updated = newest.updated)
            where
                item.catalog_id = newest.catalog_id and
                item.record = newest.record;
        ');
    }

    public static function setState(
        Settings $settings,
        Database $database
    ): int {
        $states = StateData::all($settings, $database);
        $clauses = [];

        foreach ($states as $state) {
            $clauses[] = 'if (catalog_id = "' .
                $state->catalog_id . '" and ' .
                $state->field . ' = "' .
                $state->value . '", "' .
                $state->state . '"';
        }

        return (int) $database->pdo->exec('
            update
                item
            set
                item.state = ' .
                    \implode(', ', $clauses) . ', null' .
                    \str_repeat(')', \count($clauses)) . '
        ');
    }

    private static function parseCode(string $code): ?string
    {
        $code = \trim($code);

        if ($code === '-') {
            return null;
        }

        return $code;
    }

    /**
     * @param \DateTimeInterface|string $date
     * @throws InvalidDataException
     */
    private static function parseDate($date): \DateTimeInterface
    {
        if (\is_string($date)) {
            try {
                /** @var string */
                $date = preg_replace(
                    [
                        '/^(\d{2})-(\d{2})-(\d{2})$/',
                        '/^(\d{2})-(\d{2})-(\d{4})$/',
                    ],
                    [
                        '19$3-$1-$2',
                        '$3-$1-$2',
                    ],
                    $date
                );

                $date = new DateTimeImmutable($date);
            } catch (\Throwable $exception) {
                throw new InvalidDataException(
                    'Could not parse date',
                    0,
                    $exception
                );
            }
        }

        return $date;
    }

    /**
     * @param string|string[]|null $list
     * @return string[]
     */
    private static function parseList($list): array
    {
        if (\is_null($list)) {
            return [];
        }

        if (\is_string($list)) {
            $list = \explode(';', $list);
        }

        foreach ($list as $key => $value) {
            $list[$key] = \trim($value, '"');
        }

        return $list;
    }
}
