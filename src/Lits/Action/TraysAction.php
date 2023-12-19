<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Data\TrayData;
use Lits\Exception\InvalidDataException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class TraysAction extends AuthDatabaseAction
{
    /**
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    public function action(): void
    {
        $context = [
            'trays' => TrayData::all($this->settings, $this->database),
        ];

        try {
            $this->render($this->template(), $context);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /**
     * @param array<string, string> $data
     * @throws HttpInternalServerErrorException
     */
    public function post(
        ServerRequest $request,
        Response $response,
        array $data,
    ): Response {
        $this->setup($request, $response, $data);

        /** @var array<string, string|null>|null $post */
        $post = $this->request->getParsedBody();

        if (!\is_array($post)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        try {
            $this->redirect(
                $this->routeCollector->getRouteParser()->urlFor('trays'),
            );

            $tray = TrayData::fromRow(
                $post,
                $this->settings,
                $this->database,
            );

            if (isset($post['remove'])) {
                $tray->remove();

                $this->message(
                    'success',
                    'Removed Tray Type ' . $tray->id,
                );
            } else {
                $tray->save();

                $this->message(
                    'success',
                    'Updated Tray Type ' . $tray->id,
                );
            }
        } catch (InvalidDataException $exception) {
            $this->message(
                'failure',
                \rtrim($exception->getMessage(), '.') . '.',
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }

        return $this->response;
    }
}
