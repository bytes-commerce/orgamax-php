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

final readonly class Invoice
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `POST /invoice/{id}/payment` — addPayment.
     *
     * @param array<string, mixed> $payload
     */
    public function addPayment(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Invoice id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/invoice/' . $id . '/payment', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /invoice/document/{id}` — getinvoiceDocument.
     */
    public function downloadDocument(string $id): BinaryResponse
    {
        Assert::stringNotEmpty($id, 'Invoice id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/invoice/document/' . $id, []),
            expectBody: false,
        );

        return BinaryResponse::fromPsrResponse($response);
    }

    /**
     * `GET /invoice/{id}/download` — download File (deprecated, prefer {@see self::downloadDocument()}).
     */
    public function download(string $id): BinaryResponse
    {
        Assert::stringNotEmpty($id, 'Invoice id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/invoice/' . $id . '/download', []),
            expectBody: false,
        );

        return BinaryResponse::fromPsrResponse($response);
    }

    /**
     * `GET /invoice/{id}` — getInvoice.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Invoice id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/invoice/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /invoice` — getInvoices.
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
            $this->requestBuilder->buildGet('/invoice', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /invoice/{id}/lock` — lockInvoice.
     */
    public function lock(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Invoice id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/invoice/' . $id . '/lock', []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `POST /invoice/{id}/send` — sendInvoice.
     *
     * @param array<string, mixed> $payload
     */
    public function send(string $id, array $payload = []): Response
    {
        Assert::stringNotEmpty($id, 'Invoice id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/invoice/' . $id . '/send', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
