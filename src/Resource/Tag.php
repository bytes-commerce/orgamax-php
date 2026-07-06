<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;

final readonly class Tag
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `GET /tags` — gettags.
     */
    public function list(): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/tags', []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
