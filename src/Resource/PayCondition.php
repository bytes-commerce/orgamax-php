<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class PayCondition
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `GET /setting/payCondition` — getPayConditions.
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
            $this->requestBuilder->buildGet('/setting/payCondition', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /setting/payCondition/{id}` — getPayCondition.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'PayCondition id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/setting/payCondition/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `POST /setting/payCondition/` — createPayCondition.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/setting/payCondition/', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /setting/payCondition/` — updatePayCondition.
     *
     * Note: the OpenAPI spec routes PUT without an id segment; callers supply
     * the target id inside the payload.
     *
     * @param array<string, mixed> $payload
     */
    public function update(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/setting/payCondition/', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
