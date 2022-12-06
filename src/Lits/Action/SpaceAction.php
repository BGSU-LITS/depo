<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Data\SpaceData;
use Lits\Data\TrayData;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class SpaceAction extends AuthDatabaseAction
{
    use DateTrait;

    /**
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    public function action(): void
    {
        $context = $this->dateContext('space');

        try {
            $context['spaces'] = SpaceData::all(
                $this->settings,
                $this->database,
                $this->date('space')
            );

            $context['total'] = [
                'usedSpace' => \array_reduce(
                    $context['spaces'],
                    fn (?float $total, SpaceData $space): float =>
                        (float) $total + (float) $space->usedSpace()
                ),
                'freeSpace' => \array_reduce(
                    $context['spaces'],
                    fn (?float $total, SpaceData $space): float =>
                        (float) $total + (float) $space->freeSpace()
                ),
                'totalSpace' => \array_reduce(
                    $context['spaces'],
                    fn (?float $total, SpaceData $space): float =>
                        (float) $total + (float) $space->totalSpace()
                ),
            ];

            $context['trays'] = TrayData::all(
                $this->settings,
                $this->database
            );

            $this->render($this->template(), $context);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }
    }
}
