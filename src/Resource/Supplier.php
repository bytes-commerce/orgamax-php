<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class Supplier
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `POST /supplier` — postSuppliers.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/supplier', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * Create-or-update a supplier in a single call.
     *
     * - When `$id` is an empty string (the default), the request becomes
     *   `POST /supplier` (create).
     * - When `$id` is supplied, the request becomes `PUT /supplier/{id}`
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
     * `GET /supplier` — getSuppliers.
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
            $this->requestBuilder->buildGet('/supplier', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `DELETE /supplier/{id}` — deleteSupplier.
     */
    public function delete(string $id): void
    {
        Assert::stringNotEmpty($id, 'Supplier id must not be empty.');

        $this->responseHandler->send(
            $this->requestBuilder->buildDelete('/supplier/' . $id),
            expectBody: false,
        );
    }

    /**
     * `PUT /supplier/{id}` — putSupplier.
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Supplier id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/supplier/' . $id, $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /supplier/{id}` — getSupplier.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Supplier id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/supplier/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
