<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Database;
use Lits\Exception\InvalidDataException;
use Lits\Settings;
use Safe\DateTimeImmutable;
use Safe\Exceptions\DatetimeException;

use function Safe\preg_replace;

final class ItemData extends DatabaseData
{
    public ?int $id = null;
    public ?string $catalog_id = null;
    public string $location;
    public string $record;
    public DateTimeImmutable $created;
    public DateTimeImmutable $updated;
    public ?int $revision = null;
    public ?string $status = null;
    public ?string $message = null;
    public bool $newest = false;
    public ?string $state = null;

    /** @var array<string> */
    public array $barcodes = [];

    /** @var array<string> */
    public array $biblios = [];

    public ?PlaceData $place = null;

    /**
     * @param array<string, ?string> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database,
    ): self {
        $item = new static($settings, $database);

        $data = self::findRowString($row, 'location');

        if (\is_null($data)) {
            throw new InvalidDataException('Location must be specified');
        }

        $item->location = $data;

        $data = self::findRowString($row, 'record');

        if (\is_null($data)) {
            throw new InvalidDataException('Record must be specified');
        }

        $item->record = $data;

        $data = self::findRowString($row, 'record');

        if (\is_null($data)) {
            throw new InvalidDataException('Record must be specified');
        }

        $item->record = $data;

        $data = self::findRowDatetime($row, 'created');

        if (\is_null($data)) {
            throw new InvalidDataException('Created must be specified');
        }

        $item->created = $data;

        $data = self::findRowDatetime($row, 'updated');

        if (\is_null($data)) {
            throw new InvalidDataException('Updated must be specified');
        }

        $item->updated = $data;
        $item->revision = self::findRowInt($row, 'revision');
        $item->status = self::findRowCode($row, 'status');
        $item->message = self::findRowCode($row, 'message');
        $item->newest = self::findRowBool($row, 'newest');
        $item->state = self::findRowString($row, 'state');
        $item->barcodes = self::findRowList($row, 'barcodes');
        $item->biblios = self::findRowList($row, 'biblios');
        $item->place = PlaceData::fromItem($item);

        return $item;
    }

    /**
     * @throws \PDOException
     * @throws InvalidDataException
     * @throws DatetimeException
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
            'id',
        );

        $this->database->delete('barcode', ['item_id' => $this->id]);

        foreach ($this->barcodes as $barcode) {
            $this->database->insertIgnore(
                'barcode',
                [
                    'item_id' => $this->id,
                    'barcode' => $barcode,
                ],
            );
        }

        $this->database->delete('biblio', ['item_id' => $this->id]);

        foreach ($this->biblios as $biblio) {
            $this->database->insertIgnore(
                'biblio',
                [
                    'item_id' => $this->id,
                    'biblio' => $biblio,
                ],
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
        Database $database,
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

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    protected static function findRowDatetime(
        array $row,
        string $key,
    ): ?DateTimeImmutable {
        if (isset($row[$key])) {
            try {
                /** @psalm-var string $date */
                $date = preg_replace(
                    [
                        '/^(\d{2})-(\d{2})-(\d{2})$/',
                        '/^(\d{2})-(\d{2})-(\d{4})$/',
                    ],
                    [
                        '19$3-$1-$2',
                        '$3-$1-$2',
                    ],
                    $row[$key],
                );

                return new DateTimeImmutable($date);
            } catch (\Throwable $exception) {
                throw new InvalidDataException(
                    'The string could not be parsed into a datetime',
                    0,
                    $exception,
                );
            }
        }

        return null;
    }

    /** @param array<string, string|null> $row */
    private static function findRowCode(array $row, string $key): ?string
    {
        if (isset($row[$key])) {
            $code = \trim($row[$key]);

            if ($code !== '-') {
                return $code;
            }
        }

        return null;
    }

    /**
     * @param array<string, string|null> $row
     * @return array<string>
     */
    private static function findRowList(array $row, string $key): array
    {
        if (\is_null($row[$key])) {
            return [];
        }

        $list = \explode(';', $row[$key]);

        foreach ($list as $list_key => $list_value) {
            $list[$list_key] = \trim($list_value, '"');
        }

        return $list;
    }
}
