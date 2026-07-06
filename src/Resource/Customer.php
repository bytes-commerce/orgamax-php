<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class Customer
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `POST /customer/` — createCustomer.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/customer/', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /customer/{id}` — putCustomer.
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Customer id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/customer/' . $id, $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * Create-or-update a customer in a single call.
     *
     * - When `$id` is an empty string (the default), the request becomes
     *   `POST /customer/` (create).
     * - When `$id` is supplied, the request becomes `PUT /customer/{id}`
     *   (update).
     *
     * Convenient when the caller already has a record id and wants to
     * "save" a record without branching on create vs. update themselves.
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
     * `GET /customer/{id}` — getCustomer.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Customer id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/customer/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /customer` — getCustomers.
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
            $this->requestBuilder->buildGet('/customer', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }
}
