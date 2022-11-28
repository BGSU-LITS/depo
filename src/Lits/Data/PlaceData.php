<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Database;
use Lits\Exception\InvalidDataException;
use Lits\Settings;
use Safe\Exceptions\PcreException;

use function Safe\preg_match;

final class PlaceData extends DatabaseData
{
    public const PATTERN_BARCODE = '
        /^
        8                               # All barcodes begin with eight
        (?<module>      \d{1})
        (?<side>        \d{2})
        (?<section>     \d{2})
        (?<shelf>       \d{2})
        (?<tray>        \d{2})
        (?:                             # Item is an optional component
            (?<item>    \d{2})
            .?                          # Items may have a check character
        )?
        $/x
    ';

    public const PATTERN_BARCODE_MYSQL = '^8[0-9]{9}([0-9]{2}.?)?$';

    public ?int $item_id = null;
    public string $module;
    public string $side;
    public string $section;
    public string $shelf;
    public string $tray;
    public ?string $item = null;

    /** @throws InvalidDataException */
    public static function fromBarcode(
        string $barcode,
        Settings $settings,
        Database $database
    ): ?self {
        try {
            if (preg_match(self::PATTERN_BARCODE, $barcode, $matches) === 1) {
                $place = new static($settings, $database);

                $place->module = (string) ($matches['module'] ?? '');
                $place->side = (string) ($matches['side'] ?? '');
                $place->section = (string) ($matches['section'] ?? '');
                $place->shelf = (string) ($matches['shelf'] ?? '');
                $place->tray = (string) ($matches['tray'] ?? '');

                if (isset($matches['item'])) {
                    $place->item = (string) $matches['item'];
                }

                return $place;
            }
        } catch (PcreException $exception) {
            throw new InvalidDataException(
                'Could not parse barcode',
                0,
                $exception
            );
        }

        return null;
    }

    public static function fromItem(ItemData $item): ?self
    {
        foreach ($item->barcodes as $barcode) {
            $place = self::fromBarcode(
                $barcode,
                $item->settings,
                $item->database
            );

            if ($place instanceof PlaceData) {
                $place->item_id = $item->id;

                return $place;
            }
        }

        return null;
    }

    /**
     * @throws \PDOException
     * @throws InvalidDataException
     */
    public function save(): void
    {
        if (\is_null($this->item_id)) {
            throw new InvalidDataException(
                'An item_id has not been set for the place data'
            );
        }

        $this->database->insertOrUpdate(
            'place',
            [
                'item_id' => $this->item_id,
            ],
            [
                'module' => $this->module,
                'side' => $this->side,
                'section' => $this->section,
                'shelf' => $this->shelf,
                'tray' => $this->tray,
                'item' => $this->item,
            ],
            'item_id'
        );
    }
}
