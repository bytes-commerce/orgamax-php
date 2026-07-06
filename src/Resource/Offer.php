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

final readonly class Offer
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `GET /offer/document/{id}` — getofferDocument.
     */
    public function downloadDocument(string $id): BinaryResponse
    {
        Assert::stringNotEmpty($id, 'Offer id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/offer/document/' . $id, []),
            expectBody: false,
        );

        return BinaryResponse::fromPsrResponse($response);
    }

    /**
     * `GET /offer/{id}` — getOffer.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Offer id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/offer/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /offer` — getOffers.
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
            $this->requestBuilder->buildGet('/offer', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }
}
