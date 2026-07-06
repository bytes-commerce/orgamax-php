<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\BinaryResponse;
use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class Order
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `POST /order/{id}/invoice` — postorderInvoice (DRAFT AN INVOICE FROM AN ORDER).
     *
     * This is the primary entry point for turning an existing OrgaMax order
     * into an invoice. The optional payload can carry invoice adjustments
     * (date, due-date, customer-vat-id, etc.).
     *
     * @param array<string, mixed> $payload
     */
    public function createInvoice(string $orderId, array $payload = []): Response
    {
        Assert::stringNotEmpty($orderId, 'Order id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/order/' . $orderId . '/invoice', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `POST /order/` — postorder (create a new order).
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/order/', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /order/document/{id}` — getOrderDocument.
     */
    public function downloadDocument(string $id): BinaryResponse
    {
        Assert::stringNotEmpty($id, 'Order id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/order/document/' . $id, []),
            expectBody: false,
        );

        return BinaryResponse::fromPsrResponse($response);
    }

    /**
     * `GET /order/{id}` — getOrder.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Order id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/order/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /order` — getOrders.
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
            $this->requestBuilder->buildGet('/order', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }
}
