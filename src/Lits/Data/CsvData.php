<?php

declare(strict_types=1);

namespace Lits\Data;

use League\Csv\Exception as CsvException;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use Lits\Command;
use Lits\Config\CsvConfig;
use Lits\Exception\InvalidConfigException;
use Lits\Exception\InvalidDataException;
use Safe\Exceptions\DatetimeException;

/* @tempalte TValue array<string, ?string> */
final class CsvData extends DatabaseData
{
    /** @var list<ItemData> */
    public array $items = [];

    /** @var array<string> */
    private array $columns;

    private int $biblios;
    private string $biblios_column;

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    public function load(string $catalog, string $file): void
    {
        $csv = $this->csvReader($file);

        $this->setupColumns();

        $item_count = 0;

        try {
            foreach ($csv->getRecords($this->columns) as $row) {
                Command::output('Saving row #' . \number_format($item_count));
                $item_count++;

                try {
                    $this->saveItem($catalog, $row);
                } catch (InvalidDataException | \PDOException $exception) {
                    Command::output(\PHP_EOL . (string) $exception . \PHP_EOL);

                    continue;
                }

                Command::output("\r");
            }
        } catch (CsvException $exception) {
            throw new InvalidDataException(
                'The CSV file could not be read',
                0,
                $exception,
            );
        }

        Command::output(
            'Saved ' . \number_format($item_count) .
            ' total rows' . \PHP_EOL,
        );
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     * @return Reader<array<string, ?string>>
     */
    private function csvReader(string $file): Reader
    {
        \assert($this->settings['csv'] instanceof CsvConfig);

        try {
            /** @var Reader<array<string, ?string>> $csv */
            $csv = Reader::createFromPath($file);
        } catch (UnavailableStream $exception) {
            throw new InvalidDataException(
                'The CSV file could not be read',
                0,
                $exception,
            );
        }

        try {
            $csv->setDelimiter($this->settings['csv']->delimiter);
        } catch (CsvException $exception) {
            throw new InvalidConfigException(
                'The CSV delimiter setting is invalid',
                0,
                $exception,
            );
        }

        if (!\is_null($this->settings['csv']->header)) {
            try {
                $csv->setHeaderOffset($this->settings['csv']->header);
            } catch (CsvException $exception) {
                throw new InvalidConfigException(
                    'The CSV header setting is invalid',
                    0,
                    $exception,
                );
            }
        }

        return $csv;
    }

    /**
     * @param array<string, ?string> $row
     * @throws DatetimeException
     * @throws InvalidDataException
     * @throws \PDOException
     */
    private function saveItem(string $catalog, array $row): void
    {
        $item = ItemData::fromRow(
            $row,
            $this->settings,
            $this->database,
        );

        $item->catalog_id = $catalog;

        for ($count = 1; $count < $this->biblios; $count++) {
            $key = $this->biblios_column . (string) $count;

            if (!\is_null($row[$key])) {
                $item->biblios[] = $row[$key];
            }
        }

        $item->save();
    }

    /** @throws InvalidConfigException */
    private function setupColumns(): void
    {
        \assert($this->settings['csv'] instanceof CsvConfig);

        $this->columns = $this->settings['csv']->columns;
        $this->biblios = $this->settings['csv']->biblios;
        $biblios_column = \end($this->columns);

        if (!\is_string($biblios_column)) {
            throw new InvalidConfigException(
                'The CSV columns setting is invalid',
            );
        }

        $this->biblios_column = $biblios_column;

        for ($count = 1; $count < $this->biblios; $count++) {
            $this->columns[] = $this->biblios_column . (string) $count;
        }
    }
}
