<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Config\DepoConfig;
use Lits\Exception\InvalidDataException;
use Safe\DateTimeImmutable;
use Safe\Exceptions\DatetimeException;
use Slim\Exception\HttpBadRequestException;

use function Latitude\QueryBuilder\func;

trait DateTrait
{
    private ?DateTimeImmutable $date = null;
    private ?DateTimeImmutable $date_first = null;
    private ?DateTimeImmutable $date_previous = null;
    private ?DateTimeImmutable $date_next = null;
    private ?DateTimeImmutable $date_last = null;

    /** @throws HttpBadRequestException */
    public function date(
        string $table = 'item',
        string $field = 'updated',
    ): DateTimeImmutable {
        if (\is_null($this->date)) {
            $this->date_first = $this->findDateFirst($table, $field);
            $this->date_last = $this->findDateLast($table, $field);
            $this->date = $this->findDate();

            if (\is_null($this->date)) {
                throw new HttpBadRequestException(
                    $this->request,
                    'Invalid date requested',
                );
            }

            try {
                $this->date_previous = $this->date->modify('previous month');
                $this->date_next = $this->date->modify('next month');
            } catch (DatetimeException) {
                // @ignoreException
            }
        }

        return $this->date;
    }

    /**
     * @return array<string, DateTimeImmutable|null>
     * @throws HttpBadRequestException
     */
    public function dateContext(
        string $table = 'item',
        string $field = 'updated',
    ): array {
        return [
            'date' => $this->date($table, $field),
            'date_first' => $this->date_first,
            'date_previous' => $this->date_previous,
            'date_next' => $this->date_next,
            'date_last' => $this->date_last,
        ];
    }

    private function findDateFirst(
        string $table,
        string $field,
    ): ?DateTimeImmutable {
        if ($table === 'item' && $field === 'updated') {
            \assert($this->settings['depo'] instanceof DepoConfig);

            return $this->settings['depo']->start;
        }

        $statement = $this->database->execute(
            $this->database->query
                ->select(func('MIN', $field))
                ->from($table),
        );

        try {
            return self::firstDayOfMonth(
                (string) $statement->fetchColumn(),
            );
        } catch (InvalidDataException) {
            // @ignoreException
        }

        return null;
    }

    private function findDateLast(
        string $table,
        string $field,
    ): ?DateTimeImmutable {
        $statement = $this->database->execute(
            $this->database->query
                ->select(func('MAX', $field))
                ->from($table),
        );

        try {
            return self::firstDayOfMonth(
                (string) $statement->fetchColumn(),
            );
        } catch (InvalidDataException) {
            // @ignoreException
        }

        return null;
    }

    private function findDate(): ?DateTimeImmutable
    {
        $date = null;

        try {
            /** @var ?string $param */
            $param = $this->request->getQueryParam('date');

            $date = self::firstDayOfMonth($param);
        } catch (InvalidDataException) {
            // @ignoreException
        }

        if (\is_null($date) || $date > $this->date_last) {
            return $this->date_last;
        }

        if ($date < $this->date_first) {
            return $this->date_first;
        }

        return $date;
    }

    /** @throws InvalidDataException */
    private static function firstDayOfMonth(?string $date): ?DateTimeImmutable
    {
        if (\is_null($date)) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($date);

            return $date->modify('first day of this month');
        } catch (\Throwable $exception) {
            throw new InvalidDataException(
                'Could not determine first day of month',
                0,
                $exception,
            );
        }
    }
}
