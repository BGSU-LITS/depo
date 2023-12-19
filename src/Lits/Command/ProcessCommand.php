<?php

declare(strict_types=1);

namespace Lits\Command;

use GetOpt\Operand;
use Lits\Data\CsvData;
use Lits\Exception\FailedCommandException;
use Lits\Exception\InvalidConfigException;
use Lits\Exception\InvalidDataException;

final class ProcessCommand extends DatabaseCommand
{
    /**
     * @throws FailedCommandException
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    public function command(): void
    {
        $statement = $this->database->execute(
            $this->database->query
                ->select('id')
                ->from($this->database->prefix . 'catalog')
                ->orderBy('id', 'asc'),
        );

        /** @var array<string>|false $options */
        $options = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);

        if ($options === false || $options === []) {
            throw new FailedCommandException(
                'Catalog source options could not be retrieved',
            );
        }

        $this->getopt->addOperand(
            Operand::create('catalog', Operand::REQUIRED)
                ->setDescription(
                    'Catalog source: ' . \implode(', ', $options),
                )
                ->setValidation(
                    fn (string $value) => \in_array($value, $options, true),
                    'Operand catalog must be ' .
                    \implode(', ', \array_slice($options, 0, -1)) . ' or ' .
                    \end($options),
                ),
        );

        $this->getopt->addOperand(
            Operand::create('file', Operand::REQUIRED)
                ->setDescription('File to process')
                ->setValidation(
                    fn (string $value) => \file_exists($value),
                    'Operand file must specify a file that exists',
                ),
        );

        if (!$this->process()) {
            return;
        }

        $csv = new CsvData($this->settings, $this->database);

        $csv->load(
            (string) $this->getopt->getOperand('catalog'),
            (string) $this->getopt->getOperand('file'),
        );
    }
}
