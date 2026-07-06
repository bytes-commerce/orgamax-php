<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class DeliveryCondition
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `GET /setting/deliveryCondition` — getDeliveryConditions.
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
            $this->requestBuilder->buildGet('/setting/deliveryCondition', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /setting/deliveryCondition/{id}` — getDeliveryCondition.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'DeliveryCondition id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/setting/deliveryCondition/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /setting/deliveryCondition/{id}` — updateDeliveryCondition.
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'DeliveryCondition id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/setting/deliveryCondition/' . $id, $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `POST /setting/deliveryCondition/` — createDeliveryCondition.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/setting/deliveryCondition/', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
