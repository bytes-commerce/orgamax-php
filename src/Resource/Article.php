<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class Article
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `POST /article/` — createArticle.
     *
     * @param array<string, mixed> $payload The full request body.
     */
    public function create(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/article/', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * Create-or-update an article in a single call.
     *
     * - When `$id` is an empty string (the default), the request becomes
     *   `POST /article/` (create).
     * - When `$id` is supplied, the request becomes `PUT /article/{id}`
     *   (update).
     *
     * @param array<string, mixed> $payload
     */
    public function upsert(array $payload, string $id = ''): Response
    {
        if ($id !== '') {
            return $this->update($id, $payload);
        }

        return $this->create($payload);
    }

    /**
     * `GET /article/` — getArticles.
     */
    public function list(int $limit = 50, int $offset = 0, string $filter = ''): ListResponse
    {
        $query = [
            'limit' => $limit,
            'offset' => $offset,
        ];
        if ($filter !== '') {
            $query['filter'] = $filter;
        }

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/article/', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /article/{id}` — getArticle.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Article id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/article/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /article/{id}` — putArticle.
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Article id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/article/' . $id, $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `DELETE /article/{id}` — deleteArticle.
     */
    public function delete(string $id): void
    {
        Assert::stringNotEmpty($id, 'Article id must not be empty.');

        $this->responseHandler->send(
            $this->requestBuilder->buildDelete('/article/' . $id),
            expectBody: false,
        );
    }

    /**
     * `POST /setting/article` — createArticleSetting.
     *
     * @param array<string, mixed> $payload
     */
    public function createSetting(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/setting/article', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
