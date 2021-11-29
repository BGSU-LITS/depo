<?php

declare(strict_types=1);

namespace Lits\Data;

use League\Csv\Exception as CsvException;
use League\Csv\Reader;
use Lits\Command;
use Lits\Config\CsvConfig;
use Lits\Exception\InvalidConfigException;
use Lits\Exception\InvalidDataException;

final class CsvData extends DatabaseData
{
    /** @var list<ItemData> */
    public array $items = [];

    /** @throws InvalidConfigException */
    public function load(string $catalog, string $file): void
    {
        \assert($this->settings['csv'] instanceof CsvConfig);

        $csv = Reader::createFromPath($file);

        try {
            $csv->setDelimiter($this->settings['csv']->delimiter);
        } catch (CsvException $exception) {
            throw new InvalidConfigException(
                'The CSV delimiter setting is invalid',
                0,
                $exception
            );
        }

        if (!\is_null($this->settings['csv']->header)) {
            try {
                $csv->setHeaderOffset($this->settings['csv']->header);
            } catch (CsvException $exception) {
                throw new InvalidConfigException(
                    'The CSV header setting is invalid',
                    0,
                    $exception
                );
            }
        }

        $columns = $this->settings['csv']->columns;
        $biblios = $this->settings['csv']->biblios;
        $biblios_column = \end($columns);

        if (!\is_string($biblios_column)) {
            throw new InvalidConfigException(
                'The CSV columns setting is invalid'
            );
        }

        for ($count = 1; $count < $biblios; $count++) {
            $columns[] = $biblios_column . (string) $count;
        }

        $item_count = 0;

        /** @var array<string, ?string> $row */
        foreach ($csv->getRecords($columns) as $row) {
            Command::output('Saving row #' . \number_format($item_count));
            $item_count++;

            try {
                $item = ItemData::fromRow(
                    $row,
                    $this->settings,
                    $this->database
                );
            } catch (InvalidDataException $exception) {
                Command::output(\PHP_EOL . (string) $exception . \PHP_EOL);

                continue;
            }

            $item->catalog_id = $catalog;

            for ($count = 1; $count < $biblios; $count++) {
                $key = $biblios_column . (string) $count;

                if (!\is_null($row[$key])) {
                    $item->biblios[] = $row[$key];
                }
            }

            $item->save();

            Command::output("\r");
        }

        Command::output(
            'Saved ' . \number_format($item_count) .
            ' total rows' . \PHP_EOL
        );
    }
}
