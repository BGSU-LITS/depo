<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Data\CatalogData;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class CatalogsAction extends AuthDatabaseAction
{
    /**
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    public function action(): void
    {
        $context = [
            'catalogs' => CatalogData::all($this->settings, $this->database),
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
            $catalog = CatalogData::fromRow(
                $post,
                $this->settings,
                $this->database,
            );

            if (isset($post['remove'])) {
                $catalog->remove();

                $this->message(
                    'success',
                    'Removed Catalog ' . $catalog->id,
                );
            } else {
                $catalog->save();

                $this->message(
                    'success',
                    'Updated Catalog ' . $catalog->id,
                );
            }

            $this->redirect(
                $this->routeCollector->getRouteParser()->urlFor('catalogs'),
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
